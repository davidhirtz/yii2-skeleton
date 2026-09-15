<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class EnvironmentAlert extends Widget
{
    use ContainerTrait;
    use TagContentTrait;
    use IconTrait;

    protected ?string $environment = null;

    #[Override]
    protected function configure(): void
    {
        $request = Application::current()->getRequest();
        $this->environment ??= $request->getEnvironment();

        if ($this->environment !== null) {
            $this->icon ??= 'exclamation-triangle';

            if (!$this->content) {
                $this->addText(Yii::t('skeleton', 'ENVIRONMENT_ALERT_MESSAGE', [
                    'environment' => $request->getEnvironmentName(),
                    'host' => $request->getHostName(),
                ]));
            }
        }

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        return $this->environment
            ? Alert::make()
                ->attributes($this->attributes)
                ->content(...$this->content)
                ->warning()
                ->icon($this->icon)
            : '';
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
