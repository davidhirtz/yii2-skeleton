<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use yii\data\DataProviderInterface;

/**
 * @template T of DataProviderInterface|null
 */
trait ProviderTrait
{
    /**
     * @var T
     */
    public ?DataProviderInterface $provider;

    /**
     * @param T $provider
     */
    public function provider(?DataProviderInterface $provider): static
    {
        $this->provider = $provider;
        return $this;
    }
}
