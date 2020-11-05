<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
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
     * @var string
     */
    public $url;

    /**
     * Uploads file from url.
     */
    public function init()
    {
        if (!$this->url || !($contents = @file_get_contents($this->url))) {
            $this->error = UPLOAD_ERR_NO_FILE;
        } elseif (($tmpFile = tmpfile()) === false) {
            $this->error = UPLOAD_ERR_NO_TMP_DIR;
        } elseif (fwrite($tmpFile, $contents) === false) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
        } else {
            $this->name = basename(parse_url($this->url, PHP_URL_PATH));
            $this->tempName = stream_get_meta_data($tmpFile)['uri'];
            $this->type = FileHelper::getMimeType($this->tempName);
            $this->size = filesize($this->tempName);
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