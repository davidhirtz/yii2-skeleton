<?php

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;
use Yiisoft\Html\Tag\Base\NormalTag;

class Div extends NormalTag
{
    use TagIconTextTrait;
    use TagTooltipAttributeTrait;

    protected function getName(): string
    {
        return 'div';
    }
}
