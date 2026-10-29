<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Validators\FileValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\UploadField;
use Override;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Until the record is saved the attribute holds the token the upload action parked the file under, not a filename.
 *
 * @see Upload
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UploadController
 */
class UploadCustomAttribute extends CustomAttribute
{
    /**
     * @var list<string>
     */
    protected array $extensions = [];

    protected ?int $maxSize = null;
    protected bool $checkExtensionByMimeType = false;
    protected ?string $permission = null;

    /**
     * @param list<string> $extensions
     */
    public function extensions(array $extensions): static
    {
        $this->extensions = $extensions;
        return $this;
    }

    public function maxSize(?int $maxSize): static
    {
        $this->maxSize = $maxSize;
        return $this;
    }

    public function checkExtensionByMimeType(bool $checkExtensionByMimeType = true): static
    {
        $this->checkExtensionByMimeType = $checkExtensionByMimeType;
        return $this;
    }

    /**
     * Without a permission the upload action is guarded by the rate limit alone.
     */
    public function permission(?string $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /**
     * @return list<string>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getMaxSize(): int
    {
        $ceiling = $this->getUpload()->maxSize;
        return $this->maxSize === null ? $ceiling : min($this->maxSize, $ceiling);
    }

    /**
     * `null` while the attribute is empty or holds a token.
     */
    public function getUrl(ActiveRecord $owner, string $name): ?string
    {
        $value = $owner->getAttribute($name);
        $upload = $this->getUpload();

        return is_string($value) && $value !== '' && !$upload->isToken($value)
            ? $upload->getUrl($owner, $name, $value)
            : null;
    }

    public function getAccept(): ?string
    {
        return $this->extensions ? '.' . implode(',.', $this->extensions) : null;
    }

    /**
     * Answered before the file is written to the temporary directory.
     */
    public function validateUploadedFile(UploadedFile $upload): ?string
    {
        $validator = Yii::$container->get(FileValidator::class, config: [
            'checkExtensionByMimeType' => $this->checkExtensionByMimeType,
            'extensions' => $this->assertExtensions(),
            'maxSize' => $this->getMaxSize(),
        ]);

        return $validator->validate($upload, $error) ? null : $error;
    }

    /**
     * An allow list, never a default: the directory is served as is, so an editor could otherwise store a `.php`
     * file and run it.
     *
     * @return list<string>
     */
    protected function assertExtensions(): array
    {
        if (!$this->extensions) {
            throw new InvalidConfigException("Upload custom attribute \"$this->name\" declares no extensions.");
        }

        return $this->extensions;
    }

    /**
     * An inline rule naming a model method, since {@see \yii\validators\InlineValidator} rebinds a closure to the
     * model and hands an array callable no model at all.
     *
     * @see \Hirtz\Skeleton\Models\Traits\CustomAttributesTrait::validateCustomAttributeUpload()
     */
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [['validateCustomAttributeUpload']];
    }

    /**
     * Anything but a token or a filename is dropped rather than reported, since a legitimate client never sends it.
     */
    public function validateUpload(Model $owner, string $attribute): void
    {
        $value = $owner->{$attribute};

        if ($value === null || $value === '') {
            return;
        }

        $upload = $this->getUpload();

        if ($upload->isToken($value)) {
            $path = $upload->getTempFile($value);

            if ($path === null) {
                $owner->addError($attribute, Yii::t('skeleton', 'UPLOAD_EXPIRED_ERROR'));
                return;
            }

            // The token is not bound to the attribute that parked the file: one parked for a more permissive
            // attribute is held to this one's rules before it is kept.
            $error = $this->validateUploadedFile(new UploadedFile([
                'name' => $upload->getFilenameFromToken($value),
                'tempName' => $path,
                'type' => (string)FileHelper::getMimeType($path),
                'size' => (int)filesize($path),
                'error' => UPLOAD_ERR_OK,
            ]));

            if ($error !== null) {
                $owner->addError($attribute, $error);
            }

            return;
        }

        // Otherwise it is the filename the record already holds, or the one a duplicate was inserted with. It
        // becomes a path segment, so that is all it may be — a request is free to send whatever it likes.
        /** @noinspection PhpConditionCheckedByNextConditionInspection */
        if (!is_string($value) || $value !== basename($value) || str_starts_with($value, '.')) {
            $owner->{$attribute} = null;
        }
    }

    #[Override]
    public function normalize(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string)$value;
    }

    #[Override]
    public function serialize(mixed $value): mixed
    {
        $value = $this->normalize($value);
        $upload = $this->getUpload();

        return is_string($value) && $upload->isToken($value) ? $upload->getFilenameFromToken($value) : $value;
    }

    #[Override]
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        return is_string($value) ? (string)$this->serialize($value) : null;
    }

    #[Override]
    public function afterSave(Model $owner, string $name, mixed $old): void
    {
        if (!$owner instanceof ActiveRecord) {
            return;
        }

        $upload = $this->getUpload();
        $value = $owner->{$name};

        if (is_string($value) && $upload->isToken($value)) {
            $filename = $upload->store($owner, $name, $value);

            if ($filename === null) {
                Yii::warning("Storing the upload of \"$name\" failed, the attribute is cleared.");
                $owner->{$name} = null;
                $owner->updateAttributes([$owner->getCustomAttributesColumn() => $this->clearValue($owner, $name)]);

                return;
            }

            $owner->{$name} = $filename;

            return;
        }

        if ($value === null && $old !== null) {
            $upload->delete($owner, $name);
        }
    }

    #[Override]
    public function afterDelete(Model $owner, string $name): void
    {
        if ($owner instanceof ActiveRecord) {
            $this->getUpload()->delete($owner, $name);
        }
    }

    #[Override]
    public function afterDuplicate(Model $duplicate, Model $source, string $name): void
    {
        if ($duplicate instanceof ActiveRecord && $source instanceof ActiveRecord) {
            $this->getUpload()->copy($source, $duplicate, $name);
        }
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        if (!$owner instanceof ActiveRecord) {
            throw new InvalidConfigException(static::class . ' requires an ActiveRecord owner.');
        }

        $this->assertExtensions();

        return $this->configureField(UploadField::make()->definition($this), $owner);
    }


    protected function getUpload(): Upload
    {
        return Upload::getComponent();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function clearValue(ActiveRecord $owner, string $name): ?array
    {
        $values = $owner->getAttribute($owner->getCustomAttributesColumn());
        $values = is_array($values) ? $values : [];

        unset($values[$name]);

        return $values ?: null;
    }
}
