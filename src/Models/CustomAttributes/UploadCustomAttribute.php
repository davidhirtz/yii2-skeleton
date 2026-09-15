<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\UploadField;
use Override;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\validators\FileValidator;
use yii\web\UploadedFile;

/**
 * One file attached to a record, stored by the `upload` component rather than in the media library: it has no folder,
 * no transformations and no assets, and it goes with the record it hangs on.
 *
 * The JSON column holds the filename alone — a translatable definition one per language — and the file itself sits in
 * a directory derived from the record. Until the record is saved the attribute carries the token the upload action
 * parked the file under, which is what lets a file be picked before the record exists.
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
     * The `accept` attribute of the file input, so the picker offers the right files to begin with.
     */
    public function getAccept(): ?string
    {
        return $this->extensions ? '.' . implode(',.', $this->extensions) : null;
    }

    /**
     * Whether the upload action may take this file at all, answered before it is written to the temporary directory.
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
     * The files are served by the web server straight out of a public directory, so what may land there is an allow
     * list and never a default — without one an editor could store a `.php` file and run it.
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
     * An inline rule names a method of the *model*, which hands the attribute back here — a closure would be rebound
     * to the model by {@see \yii\validators\InlineValidator} and an array callable is handed no model at all.
     *
     * @see \Hirtz\Skeleton\Models\Traits\CustomAttributesTrait::validateCustomAttributeUpload()
     */
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [['validateCustomAttributeUpload']];
    }

    /**
     * The value is either the token the upload action answered with or a filename; anything else is dropped rather
     * than reported, since a legitimate client never sends it.
     */
    public function validateUpload(Model $owner, string $attribute): void
    {
        $value = $owner->{$attribute};

        if ($value === null || $value === '') {
            return;
        }

        $upload = $this->getUpload();

        if ($upload->isToken($value)) {
            if ($upload->getTempFile($value) === null) {
                $owner->addError($attribute, Yii::t('skeleton', 'UPLOAD_EXPIRED_ERROR'));
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

    /**
     * The record has an id now, so the file the upload action parked under the token moves into the directory it
     * keeps for good — replacing whatever the attribute held before, since it holds one file.
     */
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

    #[Override]
    protected function getFingerprintData(): array
    {
        return [$this->extensions, $this->maxSize, $this->checkExtensionByMimeType];
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
