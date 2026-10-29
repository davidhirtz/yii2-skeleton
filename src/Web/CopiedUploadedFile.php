<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Override;

/**
 * Opens the path as it stands, stream wrappers included, so a path from a request goes through
 * {@see StreamUploadedFile}.
 */
class CopiedUploadedFile extends AbstractUploadedFile
{
    public ?string $path = null;

    #[Override]
    protected function saveTemporaryFile(): void
    {
        if (!$this->path) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->name = basename($this->path);
        $source = @fopen($this->path, 'rb');

        if (!$source) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->copyToTemporaryFile($source);
        fclose($source);
    }
}
