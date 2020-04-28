<?php

namespace davidhirtz\yii2\skeleton\validators;

/**
 * Class FileValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class FileValidator extends \yii\validators\FileValidator
{
    /**
     * Removes the ini check from original validator, because it's not working
     * with chunked uploads.
     *
     * @return float|int
     */
    public function getSizeLimit()
    {
        return $this->maxSize;
    }
}