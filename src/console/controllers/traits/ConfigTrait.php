<?php

namespace davidhirtz\yii2\skeleton\console\controllers\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\helpers\Console;

trait ConfigTrait
{
    protected function filterUserInput(string $value): int|string
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $boolean ?? (string)$value;
    }

    protected function stdoutVar(mixed $value): bool|int|string
    {
        return match (gettype($value)) {
            'boolean' => $this->stdout($value ? 'true' : 'false', Console::BOLD),
            'integer', 'double' => $value,
            default => $this->stdout("'$value'", Console::FG_GREEN),
        };
    }

    protected function getConfig(string $file, array $default = []): array
    {
        $file = Yii::getAlias($file);
        return is_file($file) ? require ($file) : $default;
    }

    protected function setConfig(string $file, array $config): void
    {
        FileHelper::createConfigFile($file, $config);
        $this->stdout($file . ' was updated.' . PHP_EOL, Console::FG_GREEN);
    }
}
