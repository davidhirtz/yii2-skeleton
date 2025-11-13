<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Override;
use Yii;
use yii\base\InvalidCallException;
use yii\web\UploadedFile;

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
     * @var int|null the maximum file size for chunked uploads.
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
        $range = (string)Yii::$app->getRequest()->getHeaders()->get('content-range');

        if (!preg_match('/^bytes (\d+)-(\d+)\/(\d+)$/', $range, $matches)) {
            return;
        }

        [, $start, $end, $this->size] = array_map(intval(...), $matches);


        if ($this->maxSize > 0 && $this->size > $this->maxSize) {
            $this->error = UPLOAD_ERR_FORM_SIZE;
            return;
        }

        $tempName = $this->getPartialUploadPath() . Yii::$app->getSession()->getId() . "-$this->name.tmp";

        if ($start === 0 && is_file($tempName)) {
            Yii::debug("Remove previously aborted upload '$tempName'");
            @unlink($tempName);
        }

        $data = fopen($this->tempName, 'r');

        if ($data === false || file_put_contents($tempName, $data, FILE_APPEND) === false) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return;
        }

        $isPartial = $end + 1 < $this->size;
        $percentage = round((($start + $end) / $this->size) * 100);

        $this->error = $isPartial ? UPLOAD_ERR_PARTIAL : UPLOAD_ERR_OK;
        $this->tempName = $tempName;

        Yii::debug($isPartial
            ? "Uploaded $percentage% of \"$this->name\"."
            : "Upload of \"$this->name\" completed.");
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

    public static function getInstance($model, $attribute): ?static
    {
        $file = $_FILES[$model->formName()] ?? null;

        return $file
            ? Yii::$container->get(static::class, config: [
                'error' => $file['error'][$attribute],
                'fullPath' => $file['full_path'][$attribute],
                'name' => $file['name'][$attribute],
                'size' => $file['size'][$attribute],
                'tempName' => $file['tmp_name'][$attribute],
                'type' => $file['type'][$attribute],
            ])
            : null;
    }

    public static function getInstanceByName($name): ?static
    {
        $file = $_FILES[$name] ?? null;

        return $file
            ? Yii::$container->get(static::class, config: [
                'error' => $file['error'],
                'fullPath' => $file['full_path'],
                'name' => $file['name'],
                'size' => $file['size'],
                'tempName' => $file['tmp_name'],
                'type' => $file['type'],
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
