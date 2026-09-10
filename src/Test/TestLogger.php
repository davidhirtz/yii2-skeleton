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
     * @var bool whether messages are collected in {@see static::$messages}, which is what
     * {@see TestCase::countQueries()} evaluates. Off by default, so a test that does not count keeps no messages.
     */
    public bool $isRecording = false;

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
        if ($this->isRecording) {
            $this->messages[] = [$message, $level, $category, microtime(true), []];
        }

        if (
            !in_array('--debug', $_SERVER['argv'], true)
            || str_starts_with($category, Command::class)) {
            return;
        }

        if ($message instanceof Exception) {
            $message = $message->__toString();
        }

        $color = match ($level) {
            Logger::LEVEL_ERROR => Console::FG_RED,
            Logger::LEVEL_WARNING => Console::FG_YELLOW,
            default => Console::FG_GREEN,
        };

        $text = Console::ansiFormat("[$category] " . VarDumper::export($message), [$color]);
        Console::output($text);
    }
}
