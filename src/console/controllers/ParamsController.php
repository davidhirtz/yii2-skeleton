<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Exception;
use Yii;
use yii\console\Controller;

/**
 * Manages application parameters.
 */
class ParamsController extends Controller
{
    use ConfigTrait;

    public string $config = '@config/params.php';

    /**
     * Setup application.
     */
    public function actionIndex(): void
    {
        $params = $this->getConfig($this->config);

        if (empty($params['cookieValidationKey'])) {
            $this->actionPermissions();
            $this->actionCookie();
        } else {
            foreach ($params as $name => $value) {
                $this->stdout("$name => ");
                $this->stdoutVar($value);
                $this->stdout(PHP_EOL);
            }
        }
    }

    /**
     * Generates cookie validation key.
     */
    public function actionCookie(bool $replace = true): void
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
     */
    public function actionCreate(string $param, string $value): void
    {
        $params = $this->getConfig($this->config);

        if (!isset($params[$param]) || $this->confirm('Parameter already exists, override?')) {
            $params[$param] = $this->filterUserInput($value);
            $this->setConfig($this->config, $params);
        }
    }


    /**
     * Adds or updates give parameter in config.
     */
    public function actionUpdate(string $param, string $value): void
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
     */
    public function actionDelete(string $param): void
    {
        $params = $this->getConfig($this->config);

        if (isset($params[$param])) {
            unset($params[$param]);
            $this->setConfig($this->config, $params);
        }
    }

    /**
     * Sets permissions for application folders.
     */
    public function actionPermissions(): void
    {
        FileHelper::createDirectory(Yii::getAlias('@webroot/css'));
        FileHelper::createDirectory(Yii::getAlias('@webroot/js'));

        foreach (['@webroot/assets', '@webroot/uploads', '@runtime'] as $path) {
            try {
                chmod(Yii::getAlias($path), 0777);
            } catch (Exception $e) {
                $this->stderr("Failed to change permissions for directory \"$path\": " . $e->getMessage());
                $this->stdout(PHP_EOL);
            }
        }
    }

    protected static function generateCookieValidationKey(): string
    {
        if (!extension_loaded('openssl')) {
            throw new Exception('The OpenSSL PHP extension is required by Yii2.');
        }
        $length = 32;
        $bytes = openssl_random_pseudo_bytes($length);
        return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
    }
}
