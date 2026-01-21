<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\base\InvalidCallException;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * @property string $partialName
 * @method static ChunkedUploadedFile|null getInstance($model, $attribute)
 * @method static ChunkedUploadedFile|null getInstanceByName($name)
 */
class ChunkedUploadedFile extends UploadedFile
{
    /**
     * @var int|null the current chunk offset set by HTTP headers
     */
    public ?int $chunkOffset = null;

    /**
     * @var int|null the chunk size set by HTTP headers
     */
    public ?int $chunkSize = null;

    /**
     * @var int temporary file lifetime in seconds
     */
    public int $lifetime = 86400;

    /**
     * @var int garbage collection probability, defaults to 1%
     */
    public int $gcProbability = 1;

    /**
     * @var int|null the maximum file size for chunked uploads. Can be set via container definitions config.
     */
    public ?int $maxSize = null;

    private ?string $_partialName = null;
    private ?string $_partialUploadPath = null;

    /**
     * Checks whether file was uploaded in chunks.
     */
    public function init(): void
    {
        // Try to get file name from header if it was not set via FILES.
        if (!$this->name) {
            $subject = (string)ArrayHelper::getValue($_SERVER, 'HTTP_CONTENT_DISPOSITION');
            $this->name = rawurldecode((string)preg_replace('/(^[^"]+")|("$)/', '', $subject));
        }

        // Parse the Content-Range header, which is formatted like this:
        // Content-Range: bytes {int:start}-{int:end}/{int:total}
        $range = ArrayHelper::getValue($_SERVER, 'HTTP_CONTENT_RANGE');

        if ($range) {
            $range = preg_split('/[^0-9]+/', (string)$range);
            $range = array_map(intval(...), $range);

            $this->chunkOffset = $range[1] ?? null;
            $this->chunkSize = $range[2] ?? null;
            $this->size = $range[3] ?? null;
        }

        // Unfortunately, Yii initializes UploadedFile without checking the definitions first.
        $this->maxSize ??= Yii::$container->getDefinitions()[static::class]['maxSize'] ?? null;

        if ($this->maxSize > 0 && $this->size > $this->maxSize) {
            $this->error = UPLOAD_ERR_FORM_SIZE;
        } elseif ($this->chunkOffset !== null) {
            $tempName = $this->getPartialUploadPath() . $this->getPartialName();

            // Remove previously aborted file upload on first upload chunk.
            if ($this->chunkOffset == 0 && is_file($tempName)) {
                Yii::debug("Remove previously aborted upload '$tempName'");
                @unlink($tempName);
            }

            $data = fopen($this->tempName, 'r');

            if ($data === false) {
                $this->error = UPLOAD_ERR_NO_FILE;
            } elseif (file_put_contents($tempName, $data, FILE_APPEND) === false) {
                $this->error = UPLOAD_ERR_CANT_WRITE;
            } else {
                if (!$this->isCompleted()) {
                    $this->error = UPLOAD_ERR_PARTIAL;
                }

                // Update temporary name to use the actual combined temp file instead of the partial upload.
                $this->tempName = $tempName;
            }
        }

        parent::init();
    }

    /**
     * Saves the chunked uploaded file and returns bool whether saving the file was successful.
     */
    public function saveAs($file, $deleteTempFile = true): bool
    {
        if ($deleteTempFile) {
            $deleteTempFile = random_int(1, 10000) <= $this->gcProbability * 100;
        }

        if ($deleteTempFile) {
            $this->removeAbortedFiles();
        }

        if ($this->chunkOffset !== null) {
            if ($this->isCompleted()) {
                $tempFilename = $this->getPartialUploadPath() . $this->getPartialName();
                return FileHelper::rename($tempFilename, $file);
            }

            return false;
        }

        return parent::saveAs($file, $deleteTempFile);
    }

    /**
     * @return int the file count of deleted temporary files
     */
    public function removeAbortedFiles(): int
    {
        $lifetime = time() - $this->lifetime;
        $path = rtrim((string)Yii::getAlias($this->getPartialUploadPath()), '/');
        $fileCount = 0;

        if (is_dir($path)) {
            foreach (glob($path . '/*') as $file) {
                if (filemtime($file) <= $lifetime && unlink($file)) {
                    ++$fileCount;
                }
            }
        }

        return $fileCount;
    }

    public function getPartialName(): string
    {
        $this->_partialName ??= Yii::$app->getSession()->getId() . '-' . $this->name . '.part';
        return $this->_partialName;
    }

    /**
     * @noinspection PhpUnused
     */
    public function setPartialName(string $partialName): void
    {
        $this->_partialName = $partialName;
    }

    public function getPartialUploadPath(): ?string
    {
        if ($this->_partialUploadPath === null) {
            $this->setPartialUploadPath('@runtime/uploads');
        }

        return $this->_partialUploadPath;
    }

    public function setPartialUploadPath(string $path): void
    {
        $this->_partialUploadPath = rtrim((string)Yii::getAlias($path), '/') . '/';
        FileHelper::createDirectory($this->_partialUploadPath);
    }

    /**
     * Returns true if the file size matches the `size` set by HTTP headers on chunked uploads.
     * Stat cache needs to be refreshed before returning the accurate file size.
     */
    public function isCompleted(): bool
    {
        if ($this->chunkSize === null) {
            return true;
        }

        clearstatcache(true, $tempName = $this->getPartialUploadPath() . $this->getPartialName());
        return filesize($tempName) == $this->size;
    }


    public function isPartial(): bool
    {
        return $this->chunkSize !== null && $this->error === UPLOAD_ERR_PARTIAL;
    }

    /**
     * Multiple uploads are not supported for chunked uploads.
     * @noinspection PhpDocSignatureInspection
     */
    public static function getInstances($model, $attribute): void
    {
        throw new InvalidCallException();
    }

    /**
     * Multiple uploads are not supported for chunked uploads.
     * @noinspection PhpDocSignatureInspection
     */
    public static function getInstancesByName($name): void
    {
        throw new InvalidCallException();
    }
}
