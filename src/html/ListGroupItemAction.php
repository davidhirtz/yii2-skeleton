<?php

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\traits\ConditionalRenderTrait;
use davidhirtz\yii2\skeleton\html\traits\IconTextTrait;
use Yiisoft\Html\Tag\Base\NormalTag;

class ListGroupItemAction extends NormalTag
{
    use ConditionalRenderTrait;
    use IconTextTrait;

    protected array $attributes = [
        'class' => 'list-group-item list-group-item-action',
    ];

    public function href(string|array|null $href): self
    {
        $new = clone $this;
        $new->attributes['href'] = $href ? Url::to($href) : null;
        return $new;
    }

    protected function generateContent(): string
    {
        return $this->generateIconTextContent();
    }

    protected function getName(): string
    {
        return 'a';
    }
}
