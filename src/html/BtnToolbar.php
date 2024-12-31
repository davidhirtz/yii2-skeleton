<?php

namespace davidhirtz\yii2\skeleton\html;

use Yiisoft\Html\Tag\Base\NormalTag;
use Yiisoft\Html\Tag\Base\TagContentTrait;

final class BtnToolbar extends NormalTag
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
