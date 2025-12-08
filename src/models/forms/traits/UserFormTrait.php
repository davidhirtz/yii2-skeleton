<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\models\forms\traits;

use Hirtz\Skeleton\models\forms\UserPictureForm;
use Hirtz\Skeleton\web\StreamUploadedFile;
use Override;
use yii\web\UploadedFile;

trait UserFormTrait
{
    public ?string $repeatPassword = null;
    public UploadedFile|StreamUploadedFile|string|null $upload = null;

    #[Override]
    public function load($data, $formName = null): bool
    {
        $this->user->load($data, $formName);
        return parent::load($data, $formName);
    }

    #[Override]
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $this->clearErrors();

        if (!$this->user->validate($attributeNames, $clearErrors)) {
            $this->addErrors($this->user->getErrors());
        }

        return parent::validate($attributeNames, false);
    }
    public function save(): bool
    {
        if (!$this->validate() || !$this->beforeSave()) {
            return false;
        }

        if ($this->user->upsert(false)) {
            $this->afterSave();
            return true;
        }

        return false;
    }

    protected function uploadUserPicture(): void
    {
        $form = UserPictureForm::create(['user' => $this->user]);
        $form->file = UploadedFile::getInstance($this, 'upload');
        $form->upload();
    }
}
