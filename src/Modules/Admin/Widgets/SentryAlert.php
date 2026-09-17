<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Log\SentryTarget;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * An installation reporting nowhere but its own log file loses every error nobody happens to look for, which is
 * only worth saying on a production host. The check is the log target rather than `params.sentryDsn`, since a
 * project may configure the target itself.
 */
class SentryAlert extends Widget
{
    use ContainerTrait;
    use TagContentTrait;
    use IconTrait;

    /**
     * @var bool|null a project reporting its errors some other way answers `false` from an `EVENT_CONFIGURE`
     * listener.
     */
    protected ?bool $unreported = null;

    #[Override]
    protected function configure(): void
    {
        $this->unreported ??= $this->isProduction() && !$this->hasSentryTarget();

        if ($this->unreported) {
            $this->icon ??= 'exclamation-triangle';

            if (!$this->content) {
                $this->addText(Yii::t('skeleton', 'SENTRY_ALERT_MESSAGE'));
            }
        }

        parent::configure();
    }

    public function unreported(?bool $unreported): static
    {
        $this->unreported = $unreported;
        return $this;
    }

    public function getUnreported(): bool
    {
        return (bool)$this->unreported;
    }

    protected function isProduction(): bool
    {
        return Application::current()->getRequest()->getEnvironment() === null;
    }

    protected function hasSentryTarget(): bool
    {
        foreach (Yii::$app->getLog()->targets as $target) {
            if ($target instanceof SentryTarget && $target->getEnabled()) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->unreported
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
        return (bool)$this->unreported && parent::isVisible();
    }
}
