<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\BackupTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\ConfigTrait;
use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Helpers\NamespaceHelper;
use Hirtz\Skeleton\Models\User;
use Override;
use Seld\CliPrompt\CliPrompt;
use Yii;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manages application migrations.
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    use BackupTrait;
    use ConfigTrait;

    /**
     * @var string|null
     */
    public $migrationPath = null;

    /**
     * @var list<string>
     */
    public $migrationNamespaces = [];

    /**
     * The actions that apply migrations, and therefore the only ones the history guard runs for.
     * @see MigrateController::checkHistory()
     */
    private const array MIGRATING_ACTIONS = ['up', 'down', 'to', 'redo', 'fresh'];

    public string $dbFile = '@root/config/db.php';

    /**
     * @var string a script an upgrade may leave in the project to repair a migration history this code cannot
     * resolve. Set to an empty string to refuse instead of repairing.
     * @see MigrateController::repairHistory()
     */
    public string $upgradeFile = '@root/upgrade/collapse.php';
    public $templateFile = '@skeleton/views/migration.php';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $dbConfig = null;

    /**
     * @var bool whether to skip database backup before applying or reverting migrations.
     */
    public bool $skipBackup = false;

    #[Override]
    public function init(): void
    {
        $this->migrationNamespaces = array_values(array_unique([
            ...$this->migrationNamespaces,
            ...Yii::$app->getMigrationNamespaces(),
        ]));

        if (!$this->skipBackup) {
            $this->skipBackup = !Yii::$app->getDb()->backupOnMigration;
        }

        $this->setMigrationNamespaceAliases();

        parent::init();
    }

    /**
     * `BaseMigrateController` resolves a namespace's directory through an alias of the namespace's own name, in a
     * private method — and Composer writes one into `vendor/yiisoft/extensions.php` for every installed extension
     * but never for the root package, so a project's own `App\Migrations` had nothing to resolve against. The
     * alias is registered here, from the autoloader and only for the namespaces that lack one, rather than
     * configured: nothing outside a migration run asks for it, and `Db\MigrationHistory` — which reads the same
     * namespaces from a web request — needs no alias at all. A namespace nothing can resolve is dropped, as the
     * history does: `App\Migrations` is registered for every installation, and a bundle installed on its own has
     * no `App\` prefix at all, where Yii would throw on the missing alias.
     */
    protected function setMigrationNamespaceAliases(): void
    {
        $this->migrationNamespaces = array_values(array_filter(
            $this->migrationNamespaces,
            static function (string $namespace): bool {
                $alias = '@' . str_replace('\\', '/', $namespace);

                if (Yii::getAlias($alias, false) !== false) {
                    return true;
                }

                if (($path = NamespaceHelper::getPath($namespace)) === null) {
                    return false;
                }

                Yii::setAlias($alias, $path);
                return true;
            },
        ));
    }

    #[Override]
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'skipBackup';

        return $options;
    }

    #[Override]
    public function beforeAction($action): bool
    {
        if (!Yii::$app->getDb()->dsn) {
            if (!$this->interactive) {
                $this->stderr('Database connection not configured.' . PHP_EOL, Console::FG_RED);
                return false;
            }

            $this->actionConfig(false);
        }

        if (!Yii::$app->getDb()->dsn) {
            return false;
        }

        return $this->checkHistory($action->id) && parent::beforeAction($action);
    }

    /**
     * A migration history naming classes that cannot be loaded means the database predates a rename of the
     * migration namespaces — it has not been upgraded. Yii would treat every migration as new and try to
     * build the schema again over populated tables, so the run stops here.
     *
     * The repair, if the project ships one, runs **before** `parent::beforeAction()`: the list of new
     * migrations is computed inside the action, so rewriting the history first is what makes the list right.
     *
     * Only the actions that apply migrations are guarded. Refusing `backup` because the history is unresolved
     * is backwards — it is the one thing worth doing first — and `repairHistory()` calls `actionBackup()`
     * itself, so guarding it would recurse. Reading the history table for `create` on a database that has
     * none is the other reason: it takes a metadata lock the caller never asked for.
     */
    protected function checkHistory(string $actionId): bool
    {
        if (!in_array($actionId, self::MIGRATING_ACTIONS, true)) {
            return true;
        }

        /** @var MigrationHistory $history */
        $history = Yii::createObject(MigrationHistory::class, [Yii::$app->getDb()]);
        $unresolved = $history->getUnresolved();

        if ($unresolved === []) {
            return true;
        }

        $this->stdout(Yii::t('skeleton', 'MIGRATE_UNRESOLVED_MESSAGE', [
            'count' => count($unresolved),
        ]) . PHP_EOL, Console::FG_YELLOW);

        foreach ($unresolved as $version) {
            $this->stdout("  $version" . PHP_EOL);
        }

        return $this->repairHistory($history);
    }

    /**
     * Runs the script an upgrade left in the project, then reads the history again. The file's presence is
     * the authorisation: it is generated, reviewed and committed by hand, and a deployment cannot be asked
     * to confirm anything.
     */
    protected function repairHistory(MigrationHistory $history): bool
    {
        $file = $this->upgradeFile === '' ? null : Yii::getAlias($this->upgradeFile, false);

        if (!is_string($file) || !is_file($file)) {
            $this->stderr(Yii::t('skeleton', 'MIGRATE_UNRESOLVED_ERROR') . PHP_EOL, Console::FG_RED);
            return false;
        }

        // Before the repair, not after: the backup otherwise captures a database the script has already
        // rewritten, and `migrateUp()` would take it too late to be worth anything.
        if (!$this->skipBackup) {
            $this->actionBackup();
            $this->skipBackup = true;
        }

        $this->stdout(Yii::t('skeleton', 'MIGRATE_UPGRADE_MESSAGE', ['file' => $file]) . PHP_EOL);

        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file), $status);

        if ($status !== ExitCode::OK) {
            return false;
        }

        $history->refresh();

        if ($history->getUnresolved() !== []) {
            $this->stderr(Yii::t('skeleton', 'MIGRATE_UNRESOLVED_ERROR') . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
    }

    /**
     * Applies new migrations.
     *
     * On an installation without users, it offers to create the owner's account afterwards.
     */
    #[Override]
    public function actionUp($limit = 0): int
    {
        $result = parent::actionUp($limit);

        if (
            $result === ExitCode::OK
            && $this->interactive
            && !User::find()->exists()
            && $this->confirm('Create owner user account?', true)
        ) {
            return $this->run('user/create');
        }

        return $result;
    }

    #[Override]
    protected function migrateUp($class): bool
    {
        if (!$this->skipBackup) {
            $this->actionBackup();
            $this->skipBackup = true;
        }

        return parent::migrateUp($class);
    }

    #[Override]
    protected function migrateDown($class): bool
    {
        if (!$this->skipBackup) {
            $this->actionBackup();
            $this->skipBackup = true;
        }

        return parent::migrateDown($class);
    }

    /**
     * Creates database connection credentials.
     */
    public function actionConfig(bool $replace = true): void
    {
        $db = $this->getDbConfig();
        $found = !empty($db);

        if (!$found || $replace) {
            if ($this->confirm($found ? 'Override existing database connection credentials?' : 'Generate database connection credentials?', !$found)) {
                $dsn = [];
                $db['dsn'] = '';

                $dsn['mysql:host'] = $this->prompt('Enter database host:', ['default' => 'localhost']);
                $dsn['port'] = $this->prompt('Enter port or leave empty:');
                $dsn['dbname'] = $this->prompt('Enter database name:', ['required' => true]);

                foreach ($dsn as $name => $value) {
                    if ($value) {
                        $db['dsn'] .= ";$name=$value";
                    }
                }

                $db['dsn'] = trim($db['dsn'], ';');

                $db['username'] = $this->prompt('Enter username:', ['default' => $dsn['dbname'], 'required' => true]);

                $this->stdout('Enter password: ');
                $db['password'] = $this->hiddenPasswordPrompt();

                $this->setConfig($this->dbFile, $db, 'Database connection credentials saved.');
                $this->dbConfig = $db;

                Yii::$app->setComponents([
                    'db' => [...Yii::$app->getComponents()['db'], ...$db],
                ]);
            }
        }
    }

    protected function hiddenPasswordPrompt(): string
    {
        return CliPrompt::hiddenPrompt();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDbConfig(): array
    {
        $this->dbConfig ??= $this->getConfig($this->dbFile);
        return $this->dbConfig;
    }
}
