<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\web\UploadedFile;

/**
 * Class StreamUploadedFile
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property string $partialName
 */
class StreamUploadedFile extends UploadedFile
{
    /**
     * @var string the external url
     */
    public $url;

    /**
     * @var resource this must be set as a protected variable so PHP garbage collection will not remove the file after
     * running {@link StreamUploadedFile::init()}.
     */
    protected $tmpFile;

    /**
     * @var string
     */
    private $_temporaryUploadPath;

    /**
     * Copies file from url.
     */
    public function init()
    {
        if (!$this->tempName) {
            $this->tempName = $this->getTemporaryUploadPath() . uniqid();
        }

        if ($this->loadTemporaryFile()) {
            $this->name = basename(parse_url($this->url, PHP_URL_PATH));
            $this->type = FileHelper::getMimeType($this->tempName);
        }

        parent::init();
    }

    /**
     * @return bool
     */
    protected function loadTemporaryFile()
    {
        if (!$this->url || !($contents = @file_get_contents($this->url))) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return false;
        }

        if (!($this->size = file_put_contents($this->tempName, $contents))) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            $file = Yii::getAlias($file);
            return $deleteTempFile ? FileHelper::rename($this->tempName, $file) : copy($this->tempName, $file);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTemporaryUploadPath()
    {
        if ($this->_temporaryUploadPath === null) {
            $this->setTemporaryUploadPath('@runtime/uploads');
        }

        return $this->_temporaryUploadPath;
    }

    /**
     * @param string $path
     */
    public function setTemporaryUploadPath($path)
    {
        $this->_temporaryUploadPath = rtrim(Yii::getAlias($path), '/') . '/';
        FileHelper::createDirectory($this->_temporaryUploadPath);
    }
}