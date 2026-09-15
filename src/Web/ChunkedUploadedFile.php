<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use Yii;
use yii\base\InvalidCallException;
use yii\web\UploadedFile;

class ChunkedUploadedFile extends UploadedFile
{
    /**
     * @var int|null the maximum file size for chunked uploads.
     */
    public ?int $maxSize = null;

    private bool $isChunked = false;

    public function init(): void
    {
        $this->saveTempFile();
        parent::init();
    }

    protected function saveTempFile(): void
    {
        // An upload PHP already rejected carries no temporary file: a browser that aborts mid-transfer reports
        // `UPLOAD_ERR_PARTIAL` with an empty `tmp_name`, and `fopen('')` is a `ValueError`, not a `false`.
        if ($this->error || !$this->tempName) {
            return;
        }

        $range = (string)Application::current()->getRequest()->getHeaders()->get('content-range');

        if (!preg_match('/^bytes (\d+)-(\d+)\/(\d+)$/', $range, $matches)) {
            return;
        }

        [, $start, $end, $this->size] = array_map(intval(...), $matches);


        if ($this->maxSize > 0 && $this->size > $this->maxSize) {
            $this->error = UPLOAD_ERR_FORM_SIZE;
            return;
        }

        $tempName = $this->getPartialUploadPath() . Application::current()->getSession()->getId() . "-$this->name.tmp";

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
        $this->isChunked = true;

        Yii::debug($isPartial
            ? "Uploaded $percentage% of \"$this->name\"."
            : "Upload of \"$this->name\" completed.");
    }

    /**
     * The abandoned chunks are not collected here: {@see Upload} owns the directory and
     * {@see Upload::collectGarbageOncePerSession()} sweeps it, so an upload pays for it once rather than by dice
     * roll on every write.
     */
    #[Override]
    public function saveAs($file, $deleteTempFile = true): bool
    {
        if ($this->isCompleted()) {
            return FileHelper::rename($this->tempName, $file);
        }

        return false;
    }

    public function getPartialUploadPath(): string
    {
        $path = Upload::getComponent()->tempPath;
        FileHelper::createDirectory($path);

        return $path;
    }

    public function isCompleted(): bool
    {
        if (!$this->tempName) {
            return false;
        }

        clearstatcache(true, $this->tempName);
        return filesize($this->tempName) === $this->size;
    }

    /**
     * Whether a chunk landed and the next one is expected. PHP reports an aborted transfer as `UPLOAD_ERR_PARTIAL`
     * too, so the error code alone cannot tell the two apart — only a chunk this class wrote itself counts.
     *
     * @noinspection PhpUnused
     */
    public function isPartial(): bool
    {
        return $this->isChunked && $this->error === UPLOAD_ERR_PARTIAL;
    }

    #[\Override]
    public static function getInstance($model, $attribute): ?static
    {
        $file = $_FILES[$model->formName()] ?? null;

        return isset($file['tmp_name'][$attribute])
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

    #[\Override]
    public static function getInstanceByName($name): ?static
    {
        $file = $_FILES[$name] ?? null;

        return isset($file['tmp_name'])
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
