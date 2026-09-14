<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Widgets\Alert;
use Override;
use Yii;

/**
 * Renders nothing on a production host, which is what makes it safe to echo unconditionally.
 *
 * @see Request::$environments
 */
class EnvironmentAlert extends Alert
{
    protected ?string $environment = null;

    #[Override]
    protected function configure(): void
    {
        $request = Yii::$app->getRequest();
        $this->environment ??= $request->getEnvironment();

        if ($this->environment !== null) {
            $this->icon ??= 'exclamation-triangle';
            $this->warning();

            if (!$this->content) {
                $this->addText(Yii::t('skeleton', 'ENVIRONMENT_ALERT_MESSAGE', [
                    'environment' => $request->getEnvironmentName(),
                    'host' => $request->getHostName(),
                ]));
            }
        }

        parent::configure();
    }

    public function environment(?string $environment): static
    {
        $this->environment = $environment;
        return $this;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    #[Override]
    public function isVisible(): bool
    {
        return $this->environment !== null && parent::isVisible();
    }
}
