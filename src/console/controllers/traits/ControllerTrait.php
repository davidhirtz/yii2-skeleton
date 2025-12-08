<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\console\controllers\traits;

trait ControllerTrait
{
    private ?float $_microTime = null;

    protected function startDuration(): void
    {
        $this->_microTime = microtime(true);
    }

    public function interactiveStdout(): int|bool
    {
        return $this->interactive ? $this->stdout(...func_get_args()) : 0;
    }

    public function interactiveStartStdout(string $message = ''): int|bool
    {
        $this->startDuration();
        return $this->interactiveStdout(...func_get_args());
    }

    public function interactiveDoneStdout(bool $success = true): int|bool
    {
        return $this->interactiveStdout(($success ? ' done' : ' failed') . $this->getDuration() . PHP_EOL);
    }

    protected function getDuration(?float $microTime = null): string
    {
        $time = (microtime(true) - ($microTime ?: $this->_microTime));
        return ' (time: ' . sprintf('%.2f', $time) . 's)';
    }
}
