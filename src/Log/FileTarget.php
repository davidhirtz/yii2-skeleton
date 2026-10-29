<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log;

use Hirtz\Skeleton\Log\Traits\MaskQueryParamsTrait;
use Override;

/**
 * Masks the rendered context rather than the variables, so one rule covers every variable carrying the URL.
 */
class FileTarget extends \yii\log\FileTarget
{
    use MaskQueryParamsTrait;

    #[Override]
    protected function getContextMessage(): string
    {
        return $this->maskQueryParamValues(parent::getContextMessage());
    }

    /**
     * UTC, since Yii's `date()` follows the zone {@see \Hirtz\Skeleton\Models\User::findIdentity()} pins per account.
     *
     * @param float|int $timestamp
     */
    #[Override]
    protected function getTime($timestamp): string
    {
        $parts = explode('.', sprintf('%F', $timestamp));

        return gmdate('Y-m-d H:i:s', (int)$parts[0]) . ($this->microtime ? ('.' . $parts[1]) : '');
    }
}
