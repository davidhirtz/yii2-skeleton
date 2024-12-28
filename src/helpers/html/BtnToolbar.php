<?php

namespace davidhirtz\yii2\skeleton\helpers\html;

use Yiisoft\Html\Tag\Base\NormalTag;
use Yiisoft\Html\Tag\Base\TagContentTrait;

class BtnToolbar extends NormalTag
{
    use TagContentTrait;

    protected array $attributes = ['class' => 'btn-toolbar'];

    public function addButton(Btn $btn): self
    {
        $this->content[] = $btn;
        return $this;
    }

    protected function getName(): string
    {
        return 'div';
    }
}
