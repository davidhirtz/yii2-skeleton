<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Upload\Upload;
use Override;

/**
 * The name is the caller's, so only its basename is kept; the size is held to {@see Upload::$maxStreamUploadSize}.
 */
class ContentUploadedFile extends AbstractUploadedFile
{
    public ?string $content = null;
    public ?string $filename = null;

    #[Override]
    protected function saveTemporaryFile(): void
    {
        if ($this->content === null || $this->content === '') {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->name = basename(str_replace('\\', '/', (string)$this->filename)) ?: 'upload';

        $stream = fopen('php://memory', 'r+b');

        if (!$stream) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return;
        }

        fwrite($stream, $this->content);
        rewind($stream);

        $this->copyToTemporaryFile($stream, Upload::getComponent()->maxStreamUploadSize);
        fclose($stream);
    }
}
