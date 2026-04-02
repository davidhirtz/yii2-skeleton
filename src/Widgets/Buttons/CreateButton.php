<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class CreateButton extends Widget
{
    use TagAttributesTrait;
    use IconTextTrait;
    use TagLinkTrait;
    use VisibilityTrait;

    #[Override]
    protected function configure(): void
    {
        $this->content = $this->content ?: [Yii::t('skeleton', 'Create')];
        $this->icon ??= Icon::make()->name('plus');
        $this->attributes['href'] ??= Url::toRoute(['create']);

        parent::configure();
    }

    public function renderContent(): string|Stringable
    {
        return $this->isVisible()
            ? Button::make()
                ->addAttributes($this->attributes)
                ->primary()
                ->content(...$this->content)
                ->icon($this->icon)
            : '';
    }
}
