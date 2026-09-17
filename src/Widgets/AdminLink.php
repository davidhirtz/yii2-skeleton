<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Widgets\Buttons\AdminButton;
use Override;
use Stringable;

/**
 * The frontend counterpart of {@see AdminButton}: an overlay covering the record it is given, linking to that
 * record's admin page for an account holding its permission. The `admin` class is what
 * {@see AdminButton::registerCss()} positions and reveals, so the button has to be on the page as well.
 */
class AdminLink extends Widget
{
    use TagAttributesTrait;

    protected AdminModelInterface $model;

    public function model(AdminModelInterface $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['class'] ??= 'admin';
        $this->attributes['target'] ??= '_blank';

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $route = $this->canUpdateModel() ? $this->model->getAdminRoute() : null;
        return $route ? A::make()->attributes($this->attributes)->href($route) : '';
    }

    protected function canUpdateModel(): bool
    {
        return $this->webuser->can($this->model->getPermissionName());
    }

    public static function tag(AdminModelInterface $model): string
    {
        return static::make()->model($model)->render();
    }
}
