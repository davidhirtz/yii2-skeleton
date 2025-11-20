<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms\traits;

use davidhirtz\yii2\skeleton\models\forms\UserPictureForm;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use yii\web\UploadedFile;

trait UserFormTrait
{
    public ?string $repeatPassword = null;
    public UploadedFile|StreamUploadedFile|string|null $upload = null;


    protected function uploadUserPicture(): void
    {
        $form = UserPictureForm::create(['user' => $this->user]);
        $form->file = UploadedFile::getInstance($this, 'upload');
        $form->upload();
    }
}
