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
     * @var string
     */
    public $partialUploadPath = '@runtime/uploads/';

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
     * Checks whether file was uploaded in chunks.
     */
    public function init()
    {
        /**
         * Try to get file name from header if it was  not set via FILES.
         */
        if (!$this->name) {
            $this->name = rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', ArrayHelper::getValue($_SERVER, 'HTTP_CONTENT_DISPOSITION')));
        }

        /**
         * Parse the Content-Range header, which is formatted like this:
         * Content-Range: bytes {int:start}-{int:end}/{int:total}
         */
        if ($range = ArrayHelper::getValue($_SERVER, 'HTTP_CONTENT_RANGE')) {
            $range = preg_split('/[^0-9]+/', $range);
            $this->chunkOffset = ArrayHelper::getValue($range, 1);
            $this->chunkSize = ArrayHelper::getValue($range, 2);
            $this->size = ArrayHelper::getValue($range, 3);
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
        /**
         * Garbage collection.
         */
        if (rand(1, 10000) <= $this->gcProbability) {
            $this->removeAbortedFiles();
        }

        /**
         * Check if file was uploaded in chunks.
         */
        if ($this->chunkOffset !== null) {
            FileHelper::createDirectory($path = rtrim(\Yii::getAlias($this->partialUploadPath), '/'));
            $name = $path . '/' . $this->getPartialName();

            /**
             * Write partial file.
             */
            if (file_put_contents($name, fopen($this->tempName, 'r'), FILE_APPEND) === false) {
                return false;
            }

            if ($deleteTempFile) {
                unlink($this->tempName);
            }

            /**
             * If file size matches total file size, move file to destination.
             */
            clearstatcache(true, $name);
            return filesize($name) == $this->size ? rename($name, $file) : false;
        }

        return parent::saveAs($file, $deleteTempFile);
    }

    /**
     * @return int
     */
    public function removeAbortedFiles()
    {
        $lifetime = time() - $this->lifetime;
        $path = rtrim(\Yii::getAlias($this->partialUploadPath), '/');
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
            $this->_partialName = Yii::$app->session->id . '-' . $this->name . '.part';
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

    /***********************************************************************
     * Static methods.
     ***********************************************************************/

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile|\yii\web\UploadedFile
     */
    public static function getInstance($model, $attribute)
    {
        return parent::getInstance($model, $attribute);
    }

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile[]|\yii\web\UploadedFile[]
     */
    public static function getInstances($model, $attribute)
    {
        return parent::getInstances($model, $attribute);
    }

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile|\yii\web\UploadedFile
     */
    public static function getInstanceByName($name)
    {
        return parent::getInstanceByName($name);
    }

    /**
     * @inheritdoc
     * @return ChunkedUploadedFile[]|\yii\web\UploadedFile[]
     */
    public static function getInstancesByName($name)
    {
        return parent::getInstancesByName($name);
    }
}