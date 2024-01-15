<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ConfigTrait;
use Yii;
use yii\console\Controller;

/**
 * Manages application parameters.
 */
class ParamsController extends Controller
{
    use ConfigTrait;

    public string $config = '@root/config/params.php';

    /**
     * List application parameters.
     */
    public function actionIndex(): void
    {
        if (empty(Yii::$app->params['cookieValidationKey'])) {
            $this->actionCookie();
        }

        $config = $this->getConfig($this->config);
        $params = Yii::$app->params;
        $maxLength = 0;

        ksort($params);

        foreach (Yii::$app->params as $name => $value) {
            if (!array_key_exists($name, $config)) {
                unset($params[$name]);

                $name = "*$name*";
                $params[$name] = $value;
            }

            $maxLength = max($maxLength, strlen($name));
        }

        foreach ($params as $name => $value) {
            $this->stdout(str_pad("- $name  ", $maxLength + 4));
            $this->stdoutVar($value);
            $this->stdout(PHP_EOL);
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
            $message = $found ? 'Override existing cookie validation key?' : 'Generate cookie validation key?';

            if (!$this->interactive || $this->confirm($message, !$found)) {
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

        if (!isset($params[$param])
            || !$this->interactive
            || $this->confirm('Parameter already exists, override?')) {
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
            unset(Yii::$app->params[$param], $params[$param]);
            $this->setConfig($this->config, $params);
        }
    }

    protected static function generateCookieValidationKey(): string
    {
        $length = 32;
        $bytes = openssl_random_pseudo_bytes($length);

        return strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
    }
}
