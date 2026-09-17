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
}
