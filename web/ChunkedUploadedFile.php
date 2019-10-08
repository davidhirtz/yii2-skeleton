<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Class ChunkedUploadedFile.
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property string $partialName
 */
class ChunkedUploadedFile extends \yii\web\UploadedFile
{
    /**
     * @var int
     */
    public $chunkOffset;

    /**
     * @var int
     */
    public $chunkSize;

    /**
     * @var int
     */
    public $lifetime = 86400;

    /**
     * @var int
     */
    public $gcProbability = 1000;

    /**
     * @var string
     */
    private $_partialName;

    /**
     * @var string
     */
    private $_partialUploadPath;

    /**
     * Checks whether file was uploaded in chunks.
     */
    public function init()
    {
        // Try to get file name from header if it was  not set via FILES.
        if (!$this->name) {
            $this->name = rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', ArrayHelper::getValue($_SERVER, 'HTTP_CONTENT_DISPOSITION')));
        }

        // Parse the Content-Range header, which is formatted like this:
        // Content-Range: bytes {int:start}-{int:end}/{int:total}
        if ($range = ArrayHelper::getValue($_SERVER, 'HTTP_CONTENT_RANGE')) {
            $range = preg_split('/[^0-9]+/', $range);
            $this->chunkOffset = ArrayHelper::getValue($range, 1);
            $this->chunkSize = ArrayHelper::getValue($range, 2);
            $this->size = ArrayHelper::getValue($range, 3);
        }

        if ($this->chunkOffset !== null) {
            if (file_put_contents($tempName = $this->getPartialUploadPath() . $this->getPartialName(), fopen($this->tempName, 'r'), FILE_APPEND) === false) {
                $this->error = UPLOAD_ERR_CANT_WRITE;
            } else {
                if (!$this->isCompleted()) {
                    $this->error = UPLOAD_ERR_PARTIAL;
                }

                // Update temporary name to use the actual combined temp file instead of
                // the partial upload.
                $this->tempName = $tempName;
            }
        }

        parent::init();
    }

    /**
     * Saves the chunked uploaded file.
     * Returns boolean whether all chunks of the file where uploaded successfully.
     *
     * @param string $file
     * @param boolean $deleteTempFile
     * @return boolean
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($deleteTempFile && rand(1, 10000) <= $this->gcProbability) {
            $this->removeAbortedFiles();
        }

        if ($this->chunkOffset !== null) {
            return $this->isCompleted() ? rename($this->getPartialUploadPath() . $this->getPartialName(), $file) : false;
        }

        return parent::saveAs($file, $deleteTempFile);
    }

    /**
     * @return int the file count of deleted temporary files
     */
    public function removeAbortedFiles()
    {
        $lifetime = time() - $this->lifetime;
        $path = rtrim(\Yii::getAlias($this->getPartialUploadPath()), '/');
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

    /***********************************************************************
     * Getters / setters.
     ***********************************************************************/

    /**
     * @return string
     */
    public function getPartialName()
    {
        if ($this->_partialName === null) {
            $this->_partialName = Yii::$app->getSession()->getId() . '-' . $this->name . '.part';
        }

        return $this->_partialName;
    }

    /**
     * @param string $partialName
     */
    public function setPartialName($partialName)
    {
        $this->_partialName = $partialName;
    }

    /**
     * @return string
     */
    public function getPartialUploadPath()
    {
        if ($this->_partialUploadPath === null) {
            $this->setPartialUploadPath('@runtime/uploads');
        }

        return $this->_partialUploadPath;
    }

    /**
     * @param string $path
     */
    public function setPartialUploadPath($path)
    {
        FileHelper::createDirectory($this->_partialUploadPath = rtrim(\Yii::getAlias($path), '/') . '/');
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        if ($this->chunkSize === null) {
            return true;
        }

        clearstatcache(true, $name = $this->getPartialUploadPath() . $this->getPartialName());
        return filesize($name) == $this->size;
    }

    /**
     * @return bool
     */
    public function isPartial()
    {
        return $this->chunkSize !== null && $this->error === UPLOAD_ERR_PARTIAL;
    }

    /***********************************************************************
     * Static methods.
     ***********************************************************************/

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile
     */
    public static function getInstance($model, $attribute)
    {
        return parent::getInstance($model, $attribute);
    }

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile[]
     */
    public static function getInstances($model, $attribute)
    {
        return parent::getInstances($model, $attribute);
    }

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile
     */
    public static function getInstanceByName($name)
    {
        return parent::getInstanceByName($name);
    }

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile[]
     */
    public static function getInstancesByName($name)
    {
        return parent::getInstancesByName($name);
    }
}