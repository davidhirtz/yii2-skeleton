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
use yii\console\Exception;
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
     * @see MigrateController::checkHistory()
     */
    private const array MIGRATING_ACTIONS = ['up', 'down', 'to', 'redo', 'fresh'];

    public string $dbFile = '@root/config/db.php';

    /**
     * @var string a script an upgrade may leave to repair a migration history this code cannot resolve, empty to
     * refuse instead
     * @see MigrateController::repairHistory()
     */
    public string $upgradeFile = '@root/upgrade/collapse.php';
    public $templateFile = '@skeleton/../resources/views/migration.php';

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
     * Yii resolves a namespace's directory through an alias Composer never writes for the root package, and a
     * namespace nothing resolves is dropped, since a standalone bundle has no `App\` prefix and Yii would throw.
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
     * Must repair before `parent::beforeAction()` computes the new migrations, and guards only the migrating
     * actions, since `repairHistory()` calls `actionBackup()` itself and `create` must take no metadata lock.
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

        $this->stdout(Yii::t('skeleton', 'MIGRATE_UNRESOLVED_TEXT', [
            'count' => count($unresolved),
        ]) . PHP_EOL, Console::FG_YELLOW);

        foreach ($unresolved as $version) {
            $this->stdout("  $version" . PHP_EOL);
        }

        return $this->repairHistory($history);
    }

    /**
     * The file's presence is the authorisation, since a deployment cannot be asked to confirm anything.
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
        if (!$this->backUpOnce()) {
            return false;
        }

        $this->stdout(Yii::t('skeleton', 'MIGRATE_UPGRADE_TEXT', ['file' => $file]) . PHP_EOL);

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
            && User::getDb()->getTableSchema(User::tableName(), true) !== null
            && !User::find()->exists()
            && $this->confirm('Create owner user account?', true)
        ) {
            return $this->run('user/create');
        }

        return $result;
    }

    #[Override]
    protected function truncateDatabase(): void
    {
        if (!$this->backUpOnce()) {
            throw new Exception('No table was dropped.');
        }

        parent::truncateDatabase();
    }

    #[Override]
    protected function migrateUp($class): bool
    {
        if (!$this->backUpOnce()) {
            return false;
        }

        return $this->invalidateRbacCache(parent::migrateUp($class));
    }

    #[Override]
    protected function migrateDown($class): bool
    {
        if (!$this->backUpOnce()) {
            return false;
        }

        return $this->invalidateRbacCache(parent::migrateDown($class));
    }

    /**
     * A failed backup stops the run, since a deployment would otherwise migrate without the backup it configured.
     */
    private function backUpOnce(): bool
    {
        if ($this->skipBackup) {
            return true;
        }

        $this->skipBackup = true;

        if ($this->actionBackup() !== ExitCode::OK) {
            $this->stderr('Pass --skipBackup to migrate without a backup.' . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
    }

    /**
     * A migration may write `auth_item` with plain SQL, which the RBAC cache, kept without expiry, never notices.
     */
    protected function invalidateRbacCache(bool $result): bool
    {
        if ($result) {
            Yii::$app->getAuthManager()->invalidateCache();
        }

        return $result;
    }

    /**
     * Creates database connection credentials.
     */
    public function actionConfig(bool $replace = true): int
    {
        // Without a terminal every prompt answers its default, which would write empty credentials over working ones
        if (!$this->interactive) {
            $this->stderr('The database credentials can only be entered interactively.' . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

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

        return ExitCode::OK;
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
