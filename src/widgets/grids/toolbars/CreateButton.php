<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\toolbars;

use Hirtz\Skeleton\helpers\Url;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\Icon;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;
use Hirtz\Skeleton\html\traits\TagLinkTrait;
use Hirtz\Skeleton\widgets\Widget;
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
