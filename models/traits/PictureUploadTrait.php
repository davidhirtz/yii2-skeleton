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
     * @var array
     */
    public $uploadExtensions = ['gif', 'jpg', 'jpeg', 'png'];

    /**
     * Generates filename for picture upload.
     */
    public function generatePictureFilename()
    {
        $extension = $this->upload->extension ?? null;

        if (!$extension) {
            $extensions = array_intersect($this->uploadExtensions, FileHelper::getExtensionsByMimeType($this->upload->type ?? false));
            $extension = $extensions ? current($extensions) : null;
        }

        $this->picture = FileHelper::generateRandomFilename($extension, 12);
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