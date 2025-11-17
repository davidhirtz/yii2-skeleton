<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\config;

/**
 * @template T of ConfigInterface
 */
interface ConfigInterface
{
    /**
     * @param T $config
     * @return T
     */
    public function merge(ConfigInterface $config): self;
}
