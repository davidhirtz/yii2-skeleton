<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Override;

/**
 * Allows you to flush cache.
 */
class CacheController extends \yii\console\controllers\CacheController
{
    /**
     * Flushes given cache components.
     */
    #[Override]
    public function actionFlush(string ...$caches): void
    {
        parent::actionFlush(...$caches);
    }
}
