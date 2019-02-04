<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;

/**
 * Manages application migrations.
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    use ConfigTrait;

    /**
     * @var string
     */
    public $dbFile = '@app/config/db.php';

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \Exception
     */
    public function beforeAction($action)
    {
        $this->actionConfig(false);
        return parent::beforeAction($action);
    }

    /**
     * Creates database connection credentials.
     *
     * @param bool $replace
     * @throws \Exception
     */
    public function actionConfig($replace = true)
    {
        $db = $this->getConfig($this->dbFile);
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
            }
        }
    }
}
