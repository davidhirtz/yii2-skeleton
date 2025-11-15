<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\toolbars;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class GridToolbarItem extends Widget
{
    use ContainerConfigurationTrait;
    use GridTrait;
    use TagAttributesTrait;
    use TagContentTrait;
    use TagVisibilityTrait;

    protected function renderContent(): string|Stringable
    {
        return $this->content
            ? Div::make()
                ->attributes($this->attributes)
                ->html(...$this->content)
            : '';
    }
}
