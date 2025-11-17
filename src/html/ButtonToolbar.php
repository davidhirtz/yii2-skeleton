<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class ButtonToolbar extends Tag
{
    use TagContentTrait;

    protected function prepareAttributes(): void
    {
        $this->addClass('btn-toolbar');
        parent::prepareAttributes();
    }
}
