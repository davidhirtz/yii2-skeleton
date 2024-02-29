<?php

namespace davidhirtz\yii2\skeleton\models\forms\traits;

use davidhirtz\yii2\skeleton\models\forms\UserPictureForm;
use yii\web\UploadedFile;

trait UserFormTrait
{
    public ?string $city = null;
    public ?string $country = null;
    public ?string $email = null;
    public ?string $first_name = null;
    public ?string $language = null;
    public ?string $last_name = null;
    public ?string $name = null;
    public ?string $timezone = null;

    public ?string $repeatPassword = null;

    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->setUserAttributes();

            if ($this->user->validate()) {
                $this->uploadUserPicture();
            }

            if ($this->user->hasErrors()) {
                $this->addErrors($this->user->getErrors());
            }
        }

        parent::afterValidate();
    }

    public function isAttributeRequired($attribute): bool
    {
        return parent::isAttributeRequired($attribute) || $this->user->isAttributeRequired($attribute);
    }

    public function setAttributesFromUser(): void
    {
        foreach ($this->safeAttributes() as $attribute) {
            if ($this->user->hasAttribute($attribute)) {
                $this->$attribute = $this->user->getAttribute($attribute);
            }
        }
    }

    public function setUserAttributes(): void
    {
        foreach ($this->safeAttributes() as $attribute) {
            if ($this->user->hasAttribute($attribute)) {
                $this->user->setAttribute($attribute, $this->$attribute);
            }
        }
    }

    public function uploadUserPicture(): void
    {
        $form = UserPictureForm::create(['user' => $this->user]);
        $form->file = UploadedFile::getInstance($this, 'upload');
        $form->upload();
    }
}
