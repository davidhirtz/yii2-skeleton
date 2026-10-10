<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent;

use DateInterval;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Yii;
use yii\base\Configurable;

class Cookie implements Configurable
{
    use ContainerConfigurationTrait;

    protected string $name = '';
    protected ?string $duration = null;
    protected bool $thirdParty = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            $this->configureProperties($config);
        }
    }

    /**
     * A `*` matches any characters (`_ga_*`).
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * An ISO 8601 interval (`P2Y`), `null` for the end of the session.
     */
    public function duration(?string $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Set on the provider's domain, so the banner's script can neither see nor delete it.
     */
    public function thirdParty(bool $thirdParty = true): static
    {
        $this->thirdParty = $thirdParty;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function isThirdParty(): bool
    {
        return $this->thirdParty;
    }

    public function getDurationLabel(): string
    {
        if ($this->duration === null) {
            return Yii::t('skeleton', 'CONSENT_DURATION_SESSION');
        }

        $interval = new DateInterval($this->duration);

        if ($interval->y) {
            return Yii::t('skeleton', 'CONSENT_DURATION_YEARS', ['n' => $interval->y]);
        }

        if ($interval->m) {
            return Yii::t('skeleton', 'CONSENT_DURATION_MONTHS', ['n' => $interval->m]);
        }

        return Yii::t('skeleton', 'CONSENT_DURATION_DAYS', ['n' => $interval->d]);
    }
}
