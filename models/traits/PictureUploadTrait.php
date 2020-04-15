<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use yii\web\UploadedFile;

/**
 * Class PictureUploadTrait
 * @package davidhirtz\yii2\skeleton\models\traits
 *
 * This could be moved to a behavior, to enabled event triggers.
 */
trait PictureUploadTrait
{
    /**
     * @var UploadedFile|StreamUploadedFile
     */
    public $upload;

    /**
     * Generates filename for picture upload.
     */
    public function generatePictureFilename()
    {
        $this->picture = FileHelper::generateRandomFilename($this->upload->extension ?? null, 12);
        $this->generatePictureFilenameInternal();
    }

    /**
     * Makes sure the generated picture filename is not used already.
     */
    private function generatePictureFilenameInternal()
    {
        if (is_file($this->getUploadPath() . $this->picture)) {
            $this->generatePictureFilename();
        }
    }

    /**
     * @return bool
     */
    public function savePictureUpload(): bool
    {
        if (FileHelper::createDirectory($uploadPath = $this->getUploadPath())) {
            return $this->upload->saveAs($uploadPath . $this->picture);
        }

        return false;
    }
}