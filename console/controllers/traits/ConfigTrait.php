<?php

namespace davidhirtz\yii2\skeleton\console\controllers\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use yii\helpers\Console;
use Yii;

/**
 * ConfigFileTrait.
 * @package davidhirtz\yii2\skeleton\console\controllers\traits
 */
trait ConfigTrait
{
    /**
     * @param string $value
     * @return int|mixed
     */
    protected function filterUserInput($value)
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $boolean !== null ? $boolean : (filter_var($value, FILTER_VALIDATE_INT) !== false ? intval($value) : $value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function stdoutVar($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                return $this->stdout($value ? 'true' : 'false', Console::BOLD);

            case 'integer':
            case 'double':
                return $value;

            default:
                return $this->stdout("'{$value}'", Console::FG_GREEN);
        }
    }

    /**
     * @param string $file
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig($file, $default = [])
    {
        $file = Yii::getAlias($file);
        return is_file($file) ? require($file) : $default;
    }

    /**
     * @param $file
     * @param $config
     * @throws \Exception
     */
    protected function setConfig($file, $config)
    {
        FileHelper::createConfigFile($file, $config);
        $this->stdout($file . ' was updated.' . PHP_EOL, Console::FG_GREEN);
    }
}