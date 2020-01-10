<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;

/**
 * Class StreamUploadedFile
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property string $partialName
 */
class StreamUploadedFile extends \yii\web\UploadedFile
{
    /**
     * @var
     */
    public $url;

    /**
     * @var resource
     */
    private $tmpFile;

    /**
     * Uploads file from url.
     */
    public function init()
    {
        if (!$this->url || !($contents = file_get_contents($this->url))) {
            $this->error = UPLOAD_ERR_NO_FILE;
        } else {
            if (($this->tmpFile = tmpfile()) === false) {
                $this->error = UPLOAD_ERR_NO_TMP_DIR;
            } elseif (fwrite($this->tmpFile, $contents) === false) {
                $this->error = UPLOAD_ERR_CANT_WRITE;
            } else {
                $this->name = basename(parse_url($this->url, PHP_URL_PATH));
                $this->tempName = stream_get_meta_data($this->tmpFile)['uri'];
                $this->type = FileHelper::getMimeType($this->tempName);
                $this->size = filesize($this->tempName);
            }
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        return $this->error == UPLOAD_ERR_OK ? file_put_contents($file, file_get_contents($this->tempName)) : false;
    }
}