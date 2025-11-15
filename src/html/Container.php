<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Container extends Tag
{
    use TagContentTrait;

    protected function prepareAttributes(): void
    {
        $this->addClass('container');
    }

    public function centered(): static
    {
        return $this->addClass('container-centered');
    }
}
