<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Override;

/**
 * An upload made from a file the application itself names — the source of a duplicate, a path a console command
 * was given — so the path is opened as it stands, a stream wrapper's included. A target that arrived with a
 * request is not one of these: that is {@see StreamUploadedFile}, and it is guarded.
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
