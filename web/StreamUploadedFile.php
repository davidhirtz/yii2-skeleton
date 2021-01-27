<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Exception;
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
     * @var string
     */
    public $url;

    /**
     * @var resource this must be set as a protected variable so PHP garbage collection will not remove the file after
     * running {@link StreamUploadedFile::init()}.
     */
    protected $tmpFile;

    /**
     * Uploads file from url.
     */
    public function init()
    {
        if (!$this->url || !($contents = @file_get_contents($this->url))) {
            $this->error = UPLOAD_ERR_NO_FILE;
        } elseif (($this->tmpFile = tmpfile()) === false) {
            $this->error = UPLOAD_ERR_NO_TMP_DIR;
        } elseif (fwrite($this->tmpFile, $contents) === false) {
            $this->error = UPLOAD_ERR_CANT_WRITE;
        } else {
            $this->name = basename(parse_url($this->url, PHP_URL_PATH));
            $this->tempName = stream_get_meta_data($this->tmpFile)['uri'];
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
        if ($this->error == UPLOAD_ERR_OK) {
            $file = Yii::getAlias($file);

            try {
                if (file_put_contents($file, file_get_contents($this->tempName))) {
                    if ($deleteTempFile) {
                        @unlink($this->tempName);
                    }

                    return true;
                }
            } catch (Exception $exception) {
                Yii::error($exception);
            }
        }

        return false;
    }
}