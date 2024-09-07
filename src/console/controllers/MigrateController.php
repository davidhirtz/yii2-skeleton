<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;
use davidhirtz\yii2\skeleton\models\User;
use Seld\CliPrompt\CliPrompt;
use Yii;
use yii\helpers\Console;

/**
 * Manages application migrations
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    use ConfigTrait;

    public $migrationPath = null;

    public $migrationNamespaces = [
        'app\migrations',
        'davidhirtz\yii2\skeleton\migrations',
    ];

    public string $dbFile = '@root/config/db.php';
    public $templateFile = '@skeleton/views/migration.php';

    private ?array $_dbConfig = null;

    public function beforeAction($action): bool
    {
        if (!Yii::$app->getDb()->dsn) {
            if (!$this->interactive) {
                $this->stderr('Database connection not configured.' . PHP_EOL, Console::FG_RED);
                return false;
            }

            $this->actionConfig(false);
        }

        return Yii::$app->getDb()->dsn && parent::beforeAction($action);
    }

    public function afterAction($action, $result): mixed
    {
        if (
            $this->interactive
            && !User::find()->exists()
            && $this->confirm('Create owner user account?', true)
        ) {
            $this->run('user/create');
        }

        return parent::afterAction($action, $result);
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
                $this->_dbConfig = $db;

                Yii::$app->setComponents([
                    'db' => array_merge(Yii::$app->getComponents()['db'], $db),
                ]);
            }
        }
    }

    protected function hiddenPasswordPrompt(): string
    {
        return CliPrompt::hiddenPrompt();
    }

    public function getDbConfig(): array
    {
        $this->_dbConfig ??= $this->getConfig($this->dbFile);
        return $this->_dbConfig;
    }
}
