<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;
use Yii;

/**
 * Manages application migrations
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    use ConfigTrait;

    /**
     * @var array
     */
    private $_dbConfig;

    /**
     * @var string
     */
    public $dbFile = '@app/config/db.php';

    /**
     * @var string
     */
    public $templateFile = '@skeleton/views/migration.php';

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \Exception
     */
    public function beforeAction($action)
    {
        if ($this->interactive) {
            $this->actionConfig(false);
        }

        return $this->getDbConfig() ? parent::beforeAction($action) : false;
    }

    /**
     * Creates database connection credentials.
     *
     * @param bool $replace
     * @throws \Exception
     */
    public function actionConfig($replace = true)
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
                        $db['dsn'] .= ";{$name}={$value}";
                    }
                }

                $db['dsn'] = trim($db['dsn'], ';');

                $db['username'] = $this->prompt('Enter username:', ['default' => $dsn['dbname'], 'required' => true]);

                $this->stdout('Enter password: ');
                $db['password'] = \Seld\CliPrompt\CliPrompt::hiddenPrompt();

                $this->setConfig($this->dbFile, $db);
                $this->_dbConfig = $db;

                Yii::$app->setComponents([
                    'db' => array_merge(Yii::$app->getComponents()['db'], $db),
                ]);
            }
        }
    }

    /**
     * @return array
     */
    public function getDbConfig()
    {
        if ($this->_dbConfig === null) {
            $this->_dbConfig = $this->getConfig($this->dbFile);
        }

        return $this->_dbConfig;
    }
}
