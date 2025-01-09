<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagAjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\html\traits\TagModalTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;

class Link extends Tag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
    use TagLinkTrait;
    use TagModalTrait;
    use TagTooltipAttributeTrait;

    protected function getName(): string
    {
        return 'a';
    }
}
