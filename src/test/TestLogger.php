<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Override;
use yii\base\Exception;
use yii\db\Command;
use yii\helpers\Console;
use yii\helpers\VarDumper;
use yii\log\Logger;

class TestLogger extends Logger
{
    /**
     * Overridden to prevent register_shutdown_function call.
     */
    #[Override]
    public function init(): void
    {
    }

    #[Override]
    public function log($message, $level, $category = 'application'): void
    {
        if (
            !in_array('--debug', $_SERVER['argv'], true)
            || !in_array($level, [Logger::LEVEL_WARNING, Logger::LEVEL_ERROR])
            || str_starts_with($category, Command::class)) {
            return;
        }

        if ($message instanceof Exception) {
            $message = $message->__toString();
        }

        $color = match ($level) {
            Logger::LEVEL_ERROR => Console::FG_RED,
            Logger::LEVEL_WARNING => Console::FG_YELLOW,
        };

        $text = Console::ansiFormat("[$category] " . VarDumper::export($message), [$color]);
        Console::output($text);
    }
}
