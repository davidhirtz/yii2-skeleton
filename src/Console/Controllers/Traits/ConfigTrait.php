<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers\Traits;

use Hirtz\Skeleton\Helpers\FileHelper;
use Yii;
use yii\helpers\Console;

trait ConfigTrait
{
    protected function filterUserInput(string $value): int|string|bool|null
    {
        $value = trim($value, '\'" ');

        if ($value === 'null') {
            return null;
        }

        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $boolean ?? $value;
    }

    protected function stdoutVar(mixed $value): bool|int
    {
        return match (gettype($value)) {
            'boolean' => $this->stdout($value ? 'true' : 'false', Console::BOLD),
            'integer', 'double' => $this->stdout((string)$value),
            'NULL' => $this->stdout('null', Console::BOLD),
            default => $this->stdout("'$value'", Console::FG_GREEN),
        };
    }

    protected function getConfig(string $file, array $default = []): array
    {
        $file = Yii::getAlias($file);
        return is_file($file) ? require ($file) : $default;
    }

    protected function setConfig(string $file, array $config, ?string $message = null): void
    {
        if (!FileHelper::createConfigFile($file, $config)) {
            $this->stderr("Unable to create config file." . PHP_EOL, Console::FG_RED);
            return;
        }

        Yii::$app->params = [
            ...Yii::$app->params,
            ...$config,
        ];

        if ($message) {
            $this->stdout($message . PHP_EOL, Console::FG_GREEN);
        }
    }
}
