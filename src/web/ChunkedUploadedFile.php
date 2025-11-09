<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Override;
use Yii;
use yii\base\InvalidCallException;
use yii\web\UploadedFile;

/**
 * @property string $partialName
 * @method static ChunkedUploadedFile|null getInstance($model, $attribute)
 */
class ChunkedUploadedFile extends UploadedFile
{
    /**
     * @var int temporary file lifetime in seconds
     */
    public int $tempFileLifetime = 86400;

    /**
     * @var int garbage collection probability in percentage, defaults to 1%
     */
    public int $gcProbability = 1;

    /**
     * @var int|null the maximum file size for chunked uploads. Can be set via container definitions config.
     */
    public ?int $maxSize = null;

    private ?string $_partialUploadPath = null;

    public function init(): void
    {
        $this->saveTempFile();
        parent::init();
    }

    protected function saveTempFile(): void
    {
        if (!$this->name || !$this->tempName || $this->error !== UPLOAD_ERR_OK) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $range = (string)Yii::$app->getRequest()->getHeaders()->get('content-range');

        if (!preg_match('/^bytes (\d+)-(\d+)\/(\d+)$/', $range, $matches)) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        [, $start, $end, $size] = array_map(intval(...), $matches);

        if ($this->size !== $size || ($this->maxSize > 0 && $this->size > $this->maxSize)) {
            $this->error = UPLOAD_ERR_FORM_SIZE;
            return;
        }

        $tempName = $this->getPartialUploadPath() . Yii::$app->getSession()->getId() . '-' . $this->name . '.part';

        if ($start === 0 && is_file($tempName)) {
            Yii::debug("Remove previously aborted upload '$tempName'");
            @unlink($tempName);
        }

        $data = fopen($this->tempName, 'r');

        if ($data === false || file_put_contents($tempName, $data, FILE_APPEND) === false) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return;
        }

        $this->error = $end + 1 < $size ? UPLOAD_ERR_PARTIAL : UPLOAD_ERR_OK;
        $this->tempName = $tempName;
    }

    #[Override]
    public function saveAs($file, $deleteTempFile = true): bool
    {
        if ($deleteTempFile) {
            $deleteTempFile = random_int(1, 10000) <= $this->gcProbability * 100;
        }

        if ($deleteTempFile) {
            $this->removeAbortedFiles();
        }

        if ($this->isCompleted()) {
            return FileHelper::rename($this->tempName, $file);
        }

        return false;
    }

    protected function removeAbortedFiles(): int
    {
        $lifetime = time() - $this->tempFileLifetime;
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

    public function isCompleted(): bool
    {
        clearstatcache(true, $this->tempName);
        return filesize($this->tempName) === $this->size;
    }

    /**
     * @noinspection PhpUnused
     */
    public function isPartial(): bool
    {
        return $this->error === UPLOAD_ERR_PARTIAL;
    }

    public static function getInstanceByName($name): ?static
    {
        $file = $_FILES[$name] ?? null;

        return $file
            ? Yii::$container->get(static::class, config: [
                'name' => $file['name'] ?? null,
                'fullPath' => $file['full_path'] ?? null,
                'tempName' => $file['tmp_name'] ?? null,
                'type' => $file['type'] ?? null,
                'size' => $file['size'] ?? null,
                'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
            ])
            : null;
    }

    #[Override]
    public static function getInstances($model, $attribute): void
    {
        throw new InvalidCallException();
    }

    #[Override]
    public static function getInstancesByName($name): void
    {
        throw new InvalidCallException();
    }
}
