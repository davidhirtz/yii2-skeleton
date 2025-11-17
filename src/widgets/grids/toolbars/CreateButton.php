<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\toolbars;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class CreateButton extends Widget
{
    use TagAttributesTrait;
    use TagIconTextTrait;
    use TagLinkTrait;

    public function renderContent(): Stringable
    {
        if (!$this->content) {
            $this->content = [Yii::t('skeleton', 'Create')];
        }

        $this->attributes['href'] ??= ['create'];
        $this->icon ??= Icon::make()->name('plus');

        return Button::make()
            ->addAttributes($this->attributes)
            ->primary()
            ->content(...$this->content)
            ->icon($this->icon);
    }
}
