<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base;

use yii\base\BootstrapInterface;

/**
 * A `Bootstrap` whose defaults are configuration: merged between the core configuration before anything is built;
 * a default the application must be able to replace, or that depends on the application, stays in `bootstrap()`.
 */
interface ConfigBootstrapInterface extends BootstrapInterface
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaultConfig(): array;
}
