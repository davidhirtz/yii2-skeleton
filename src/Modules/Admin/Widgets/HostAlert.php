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

/**
 * A production host that neither pins its host nor limits the hosts a request may name builds every absolute URL —
 * a password reset link among them — from whatever `Host` header the request carried.
 */
class HostAlert extends Widget
{
    use ContainerTrait;
    use TagContentTrait;
    use IconTrait;

    /**
     * @var bool|null a project whose web server refuses unknown hosts answers `false` from an `EVENT_CONFIGURE`
     * listener.
     */
    protected ?bool $unpinned = null;

    #[Override]
    protected function configure(): void
    {
        $this->unpinned ??= $this->isProduction() && !$this->isHostPinned();

        if ($this->unpinned) {
            $this->icon ??= 'exclamation-triangle';

            if (!$this->content) {
                $this->addText(Yii::t('skeleton', 'HOST_ALERT_MESSAGE'));
            }
        }

        parent::configure();
    }

    public function unpinned(?bool $unpinned): static
    {
        $this->unpinned = $unpinned;
        return $this;
    }

    public function getUnpinned(): bool
    {
        return (bool)$this->unpinned;
    }

    protected function isProduction(): bool
    {
        return Application::current()->getRequest()->getEnvironment() === null;
    }

    protected function isHostPinned(): bool
    {
        $app = Application::current();
        return $app->getRequest()->allowedHosts || !$app->getUrlManager()->isHostInfoFromRequest();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->unpinned
            ? Alert::make()
                ->attributes($this->attributes)
                ->content(...$this->content)
                ->warning()
                ->icon($this->icon)
            : '';
    }

    #[Override]
    public function isVisible(): bool
    {
        return (bool)$this->unpinned && parent::isVisible();
    }
}
