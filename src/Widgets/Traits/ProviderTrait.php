<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use yii\data\DataProviderInterface;

/**
 * @template TProvider of DataProviderInterface|null
 */
trait ProviderTrait
{
    /**
     * @var TProvider
     */
    public ?DataProviderInterface $provider = null;

    /**
     * @param TProvider $provider
     */
    public function provider(?DataProviderInterface $provider): static
    {
        $this->provider = $provider;
        return $this;
    }
}
