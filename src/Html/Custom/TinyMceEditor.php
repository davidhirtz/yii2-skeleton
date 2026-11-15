<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Custom;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;

class TinyMceEditor extends Tag
{
    use TagContentTrait;

    #[Override]
    protected function getTagName(): string
    {
        return 'tinymce-editor';
    }
}
