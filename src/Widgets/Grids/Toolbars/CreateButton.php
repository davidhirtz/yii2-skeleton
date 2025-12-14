<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Icon;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class CreateButton extends Widget
{
    use TagAttributesTrait;
    use TagIconTextTrait;
    use TagLinkTrait;

    #[\Override]
    protected function configure(): void
    {
        $this->content = $this->content ?: [Yii::t('skeleton', 'Create')];
        $this->icon ??= Icon::make()->name('plus');
        $this->attributes['href'] ??= Url::toRoute(['create']);

        parent::configure();
    }

    public function renderContent(): Stringable
    {
        return Button::make()
            ->addAttributes($this->attributes)
            ->primary()
            ->content(...$this->content)
            ->icon($this->icon);
    }
}
