<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Override;

/**
 * Yii caps `maxSize` at php.ini's `upload_max_filesize`, which bounds a single request — a file assembled from chunks
 * ({@see \Hirtz\Skeleton\Web\ChunkedUploadedFile}) is never subject to it, and PHP refuses an oversized request itself.
 */
class FileValidator extends \yii\validators\FileValidator
{
    #[Override]
    public function getSizeLimit(): int
    {
        return $this->maxSize ?? parent::getSizeLimit();
    }
}
