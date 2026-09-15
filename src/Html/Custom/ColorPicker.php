<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Custom;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;

/**
 * The element `components/ColorPicker.ts` defines: it keeps the inputs it contains in sync, so a hex color field
 * that does not render it is two unrelated inputs.
 */
class ColorPicker extends Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'color-picker';
    }
}
