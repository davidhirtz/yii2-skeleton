<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

trait StdOutBufferControllerTrait
{
    private string $stdOutBuffer = '';

    public function confirm($message, $default = false): bool
    {
        if ($this->interactive) {
            $this->stdOutBuffer .= $message . ' (yes|no) [' . ($default ? 'yes' : 'no') . ']:';
            return false;
        }

        return true;
    }

    public function stderr($string): bool|int
    {
        $this->stdOutBuffer .= $string;
        return true;
    }

    public function stdout($string): bool|int
    {
        $this->stdOutBuffer .= $string;
        return true;
    }

    public function flushStdOutBuffer(): string
    {
        $result = $this->stdOutBuffer;
        $this->stdOutBuffer = '';

        return $result;
    }
}
