<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use yii\base\Exception;
use yii\console\Controller;
use Yii;

/**
 * Manages application parameter.
 * @package davidhirtz\yii2\skeleton\console\controllers
 */
class ParamsController extends Controller
{
    use ConfigTrait;

    /**
     * @var string
     */
    public $config = '@app/config/params.php';

    /**
     * Setup application.
     */
    public function actionIndex()
    {
        $params = $this->getConfig($this->config);

        if (empty($params['cookieValidationKey'])) {
            $this->actionPermissions();
            $this->actionCookie();
        } else {
            foreach ($params as $name => $value) {
                $this->stdout("{$name} => ");
                $this->stdoutVar($value);
                $this->stdout(PHP_EOL);
            }
        }
    }

    /**
     * Creates cookie validation key.
     *
     * @param bool $replace
     * @throws \Exception
     */
    public function actionCookie($replace = true)
    {
        $params = $this->getConfig($this->config);
        $found = !empty($params['cookieValidationKey']);

        if (!$found || $replace) {
            if ($this->confirm($found ? 'Override existing cookie validation key?' : 'Generate cookie validation key?', !$found)) {
                $params['cookieValidationKey'] = static::generateCookieValidationKey();
                $this->setConfig($this->config, $params);
            }
        }
    }

    /**
     * Adds or updates give parameter in config.
     *
     * @param string $param
     * @param mixed $value
     * @throws \Exception
     */
    public function actionCreate($param, $value)
    {
        $params = $this->getConfig($this->config);

        if (!isset($params[$param]) || $this->confirm('Parameter already exists, override?', false)) {
            $params[$param] = $this->filterUserInput($value);
            $this->setConfig($this->config, $params);
        }
    }


    /**
     * Adds or updates give parameter in config.
     *
     * @param string $param
     * @param mixed $value
     * @throws \Exception
     */
    public function actionUpdate($param, $value)
    {
        $params = $this->getConfig($this->config);
        $value = $this->filterUserInput($value);

        if (!isset($params[$param]) || $params[$param] !== $value) {
            $params[$param] = $value;
            $this->setConfig($this->config, $params);
        }
    }

    /**
     * Removes given parameter from config.
     *
     * @param string $param
     * @throws \Exception
     */
    public function actionDelete($param)
    {
        $params = $this->getConfig($this->config);

        if (isset($params[$param])) {
            unset($params[$param]);
            $this->setConfig($this->config, $params);
        }
    }

    /**
     * Sets permissions for application folders.
     *
     * @throws Exception
     */
    public function actionPermissions()
    {
        FileHelper::createDirectory(Yii::getAlias('@webroot/css'));
        FileHelper::createDirectory(Yii::getAlias('@webroot/js'));

        foreach (['@webroot/assets', '@webroot/uploads', '@runtime'] as $path) {
            try {
                chmod(Yii::getAlias($path), 0777);
            } catch (\Exception $e) {
                $this->stderr("Failed to change permissions for directory \"{$path}\": " . $e->getMessage());
            }
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected static function generateCookieValidationKey()
    {
        if (!extension_loaded('openssl')) {
            throw new \Exception('The OpenSSL PHP extension is required by Yii2.');
        }
        $length = 32;
        $bytes = openssl_random_pseudo_bytes($length);
        return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
    }

}