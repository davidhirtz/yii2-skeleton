<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use Yii;
use yii\web\UploadedFile;

/**
 * An upload the server produces itself rather than one a browser posted: {@see StreamUploadedFile} fetches a URL,
 * {@see CopiedUploadedFile} copies a file the application names. Which of the two a caller reaches for is the
 * whole of the difference between a request-supplied target and a trusted one.
 */
abstract class AbstractUploadedFile extends UploadedFile
{
    /**
     * @var list<string>|null the extensions the upload may turn out to have, filtered against the mime type found
     * after it was written. This also determines the file ending; leave empty to use the source's.
     */
    public ?array $allowedExtensions = null;

    public function init(): void
    {
        if (!$this->tempName) {
            $this->tempName = $this->getTemporaryUploadPath() . uniqid();
        }

        $this->saveTemporaryFile();

        if (!$this->error) {
            $this->type = FileHelper::getMimeType($this->tempName);

            if ($this->allowedExtensions && !in_array($this->getExtension(), $this->allowedExtensions, true)) {
                $this->error = UPLOAD_ERR_EXTENSION;
                FileHelper::unlink($this->tempName);
            }
        }

        parent::init();
    }

    /**
     * Writes the source to {@see UploadedFile::$tempName} and sets {@see UploadedFile::$name}, or reports why it
     * could not through {@see UploadedFile::$error}.
     */
    abstract protected function saveTemporaryFile(): void;

    /**
     * @param resource $stream
     */
    protected function copyToTemporaryFile($stream, ?int $maxSize = null): void
    {
        $target = @fopen($this->tempName, 'wb');

        if (!$target) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return;
        }

        $size = stream_copy_to_stream($stream, $target, $maxSize === null ? -1 : $maxSize + 1);
        fclose($target);

        if (!$size) {
            $this->error = UPLOAD_ERR_NO_FILE;
            FileHelper::unlink($this->tempName);
            return;
        }

        if ($maxSize !== null && $size > $maxSize) {
            $this->error = UPLOAD_ERR_INI_SIZE;
            FileHelper::unlink($this->tempName);
            return;
        }

        $this->size = $size;
    }

    #[Override]
    public function saveAs($file, $deleteTempFile = true): bool
    {
        if (!$this->error) {
            $file = Yii::getAlias($file);
            return $deleteTempFile ? FileHelper::rename($this->tempName, $file) : copy($this->tempName, $file);
        }

        return false;
    }

    #[Override]
    public function getExtension(): string
    {
        $potentialExtensions = $this->type ? FileHelper::getExtensionsByMimeType($this->type) : [];

        if ($this->allowedExtensions) {
            $potentialExtensions = array_intersect($this->allowedExtensions, $potentialExtensions);
        }

        return $potentialExtensions ? current($potentialExtensions) : parent::getExtension();
    }

    /**
     * The same directory every other upload waits in, so one collector reaches all of them — a file parked under
     * a path of its own would be swept by nothing the moment a project moves {@see Upload::$tempPath}.
     */
    public function getTemporaryUploadPath(): string
    {
        $path = Upload::getComponent()->tempPath;
        FileHelper::createDirectory($path);

        return $path;
    }
}
