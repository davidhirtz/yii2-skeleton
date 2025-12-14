<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Helpers\Image;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\StreamUploadedFile;
use yii\base\Model;
use yii\web\UploadedFile;

class UserPictureForm extends Model
{
    use ModelTrait;

    public bool $autorotatePicture = true;
    public UploadedFile|StreamUploadedFile|string|null $file = null;
    public array $uploadExtensions = ['gif', 'jpg', 'jpeg', 'png'];
    public bool $uploadCheckExtensionByMimeType = true;

    protected ?string $filename = null;

    public function __construct(public User $user, array $config = [])
    {
        parent::__construct($config);
    }

    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['file'],
                'file',
                'checkExtensionByMimeType' => $this->uploadCheckExtensionByMimeType,
                'extensions' => $this->uploadExtensions,
            ],
        ];
    }

    public function upload(): bool
    {
        $uploadPath = $this->user->getUploadPath();

        if (!$this->file || !$uploadPath || !FileHelper::createDirectory($uploadPath)) {
            return false;
        }

        if (!$this->validate()) {
            return false;
        }

        $this->generatePictureFilename();

        if ($this->file->saveAs($uploadPath . $this->filename)) {
            if ($this->autorotatePicture) {
                Image::autorotate($uploadPath . $this->filename)->save();
            }

            $this->user->picture = $this->filename;
            $this->file = null;

            return true;
        }

        return false;
    }

    public function generatePictureFilename(): void
    {
        $extension = $this->file->extension ?? null;

        if (!$extension) {
            $extensions = array_intersect($this->uploadExtensions, FileHelper::getExtensionsByMimeType($this->file->type ?? false));
            $extension = $extensions ? current($extensions) : 'jpg';
        }

        $path = FileHelper::generateRandomFilename($this->user->getUploadPath(), $extension, 12);
        $this->filename = basename($path);
    }
}
