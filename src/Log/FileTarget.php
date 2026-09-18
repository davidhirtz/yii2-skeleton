<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log;

use Hirtz\Skeleton\Log\Traits\MaskQueryParamsTrait;
use Override;

/**
 * Yii's file target, plus the masking `maskVars` cannot express. The pass is over the rendered context rather
 * than over the variables, so one rule covers `_SERVER.REQUEST_URI`, `QUERY_STRING`, `HTTP_REFERER` and
 * whichever other variable happens to carry the same URL.
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
     * Yii stamps the line with `date()`, which answers in the process time zone — and
     * {@see \Hirtz\Skeleton\Models\User::findIdentity()} pins that to the account behind the request. So the file
     * held one time zone per user and was not even in chronological order, while the admin reads it back through
     * the formatter, whose `defaultTimeZone` is UTC. The line is written in UTC instead.
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
