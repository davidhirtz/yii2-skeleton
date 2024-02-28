<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\helpers\Image;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use yii\base\Model;
use yii\web\UploadedFile;

class UserPictureForm extends Model
{
    use ModelTrait;

    public bool $autorotatePicture = true;
    public UploadedFile|StreamUploadedFile|string|null $upload = null;
    public array $uploadExtensions = ['gif', 'jpg', 'jpeg', 'png'];
    public bool $uploadCheckExtensionByMimeType = true;

    protected ?string $filename = null;

    public function __construct(public User $user, array $config = [])
    {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [
                ['upload'],
                'file',
                'checkExtensionByMimeType' => $this->uploadCheckExtensionByMimeType,
                'extensions' => $this->uploadExtensions,
            ],
        ];
    }

    public function save(): bool
    {
        $uploadPath = $this->user->getUploadPath();

        if (!$uploadPath || !FileHelper::createDirectory($uploadPath)) {
            return false;
        }

        codecept_debug('Path exists');

        if (!$this->validate()) {
            return false;
        }

        codecept_debug('Validate ok');

        if ($this->upload->saveAs($uploadPath . $this->filename)) {
            if ($this->autorotatePicture) {
                Image::autorotate($uploadPath . $this->filename)->save();
            }

            $this->user->picture = $this->filename;
            $this->upload = null;

            codecept_debug('Save ok');
            return true;
        }
        codecept_debug('Save failed');

        return false;
    }

    public function generatePictureFilename(): void
    {
        $extension = $this->upload->extension ?? null;

        if (!$extension) {
            $extensions = array_intersect($this->uploadExtensions, FileHelper::getExtensionsByMimeType($this->upload->type ?? false));
            $extension = $extensions ? current($extensions) : null;
        }

        $this->filename = FileHelper::generateRandomFilename($extension, 12);
        $this->generatePictureFilenameInternal();
    }

    private function generatePictureFilenameInternal(): void
    {
        if (is_file($this->user->getUploadPath() . $this->filename)) {
            $this->generatePictureFilename();
        }
    }
}