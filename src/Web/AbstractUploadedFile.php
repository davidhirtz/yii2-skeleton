<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use Yii;
use yii\web\UploadedFile;

/**
 * The subclass a caller picks is the whole difference between a request-supplied target and a trusted one.
 */
abstract class AbstractUploadedFile extends UploadedFile
{
    /**
     * Filtered against the mime type found after writing, it also sets the file ending; empty keeps the source's.
     *
     * @var list<string>|null
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
     * Sets {@see UploadedFile::$tempName} and {@see UploadedFile::$name}, or {@see UploadedFile::$error} on failure.
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
            return $deleteTempFile ? FileHelper::moveUploadedFile($this->tempName, $file) : copy($this->tempName, $file);
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
     * The directory every other upload waits in, so the one collector of {@see Upload::$tempPath} reaches it.
     */
    public function getTemporaryUploadPath(): string
    {
        $path = Upload::getComponent()->tempPath;
        FileHelper::createDirectory($path);

        return $path;
    }
}
