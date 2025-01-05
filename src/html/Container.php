<?php

namespace davidhirtz\yii2\skeleton\html;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Base\TagContentTrait;

class Container extends BaseTag
{
    use TagContentTrait;

    protected array $attributes = [
        'class' => 'container',
    ];

    public function centered(): self
    {
        $new = clone $this;
        Html::addCssClass($new->attributes, 'container-centered');
        return $new;
    }
}
