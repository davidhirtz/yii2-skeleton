<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\web\UploadedFile;

/**
 * @property string $partialName
 */
class StreamUploadedFile extends UploadedFile
{
    /**
     * @var string|null the external url
     */
    public ?string $url = null;

    /**
     * @var array|null containing a list of allowed extensions which will be filtered against the found mime-type of
     * the downloaded file. This will also determine the file ending. Leave empty to use url ending.
     */
    public ?array $allowedExtensions = null;

    private ?string $_temporaryUploadPath = null;

    public function init(): void
    {
        if (!$this->tempName) {
            $this->tempName = $this->getTemporaryUploadPath() . uniqid();
        }

        $this->loadTemporaryFile();

        parent::init();
    }

    protected function loadTemporaryFile(): void
    {
        if (!$this->url) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->url = FileHelper::encodeUrl($this->url);
        $contents = @file_get_contents($this->url);

        if (!$contents) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->size = @file_put_contents($this->tempName, $contents);

        if (!$this->size) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return;
        }

        $this->name = basename(parse_url($this->url, PHP_URL_PATH));
        $this->type = FileHelper::getMimeType($this->tempName);

        if ($this->allowedExtensions && !in_array($this->getExtension(), $this->allowedExtensions)) {
            $this->error = UPLOAD_ERR_EXTENSION;
            @unlink($this->tempName);
        }
    }

    public function saveAs($file, $deleteTempFile = true): bool
    {
        if (!$this->error) {
            $file = Yii::getAlias($file);
            return $deleteTempFile ? FileHelper::rename($this->tempName, $file) : copy($this->tempName, $file);
        }

        return false;
    }

    public function getExtension(): string
    {
        $potentialExtensions = $this->type ? FileHelper::getExtensionsByMimeType($this->type) : [];

        if ($this->allowedExtensions) {
            $potentialExtensions = array_intersect($this->allowedExtensions, $potentialExtensions);
        }

        if ($potentialExtensions) {
            return current($potentialExtensions);
        }

        return parent::getExtension();
    }

    public function getTemporaryUploadPath(): ?string
    {
        if ($this->_temporaryUploadPath === null) {
            $this->setTemporaryUploadPath('@runtime/uploads');
        }

        return $this->_temporaryUploadPath;
    }

    public function setTemporaryUploadPath(string $path): void
    {
        $this->_temporaryUploadPath = rtrim((string)Yii::getAlias($path), '/') . '/';
        FileHelper::createDirectory($this->_temporaryUploadPath);
    }
}
