<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;
use Seld\CliPrompt\CliPrompt;
use Yii;

/**
 * Manages application migrations
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    use ConfigTrait;

    public $migrationPath = null;

    public $migrationNamespaces = [
        'app\migrations',
        'davidhirtz\yii2\skeleton\migrations',
    ];

    public string $dbFile = '@config/db.php';
    public $templateFile = '@skeleton/views/migration.php';

    private ?array $_dbConfig = null;

    public function beforeAction($action): bool
    {
        if ($this->interactive) {
            $this->actionConfig(false);
        }

        return $this->getDbConfig() && parent::beforeAction($action);
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
                $db['password'] = CliPrompt::hiddenPrompt();

                $this->setConfig($this->dbFile, $db);
                $this->_dbConfig = $db;

                Yii::$app->setComponents([
                    'db' => array_merge(Yii::$app->getComponents()['db'], $db),
                ]);
            }
        }
    }

    public function getDbConfig(): array
    {
        $this->_dbConfig ??= $this->getConfig($this->dbFile);
        return $this->_dbConfig;
    }
}
