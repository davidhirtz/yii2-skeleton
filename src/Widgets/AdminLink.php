<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Buttons\AdminButton;
use Override;
use Stringable;
use Yii;

/**
 * The `admin` class is what {@see AdminButton::registerCss()} positions and reveals, so an {@see AdminButton} has to be
 * on the page as well.
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
        return $route ? A::make()->attributes($this->attributes)->href($this->withContentLanguage($route)) : '';
    }

    /**
     * Always the page's language, never compared with the session's, so a cached page stays the same for everyone.
     *
     * @param array<array-key, mixed> $route
     * @return array<array-key, mixed>
     */
    protected function withContentLanguage(array $route): array
    {
        $module = Module::current();

        if ($module->hasContentLanguage(Yii::$app->language) && count($module->getContentLanguages()) > 1) {
            $route[$module->contentLanguageParam] = Yii::$app->language;
        }

        return $route;
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
