<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

/**
 * {@see AdminModelInterface::getAdminRoute()} is deliberately not implemented here: only the model knows its
 * controller, and a silent `false` would hide every link to it.
 *
 * @mixin Model
 */
trait AdminModelTrait
{
    public function getAdminName(): string
    {
        $id = $this->getAdminId();

        if ($id === null) {
            return $this->getAdminType();
        }

        return $this->getAdminNameAttributeValue() ?: Yii::t('skeleton', 'COMMON_MODEL_ID', [
            'model' => $this->getAdminType(),
            'id' => $id,
        ]);
    }

    public function getAdminType(): string
    {
        if ($this instanceof TypeAttributeInterface && ($name = $this->getTypeName())) {
            return $name;
        }

        return (new ReflectionClass(static::class))->getShortName();
    }

    public function getAdminIcon(): ?string
    {
        if ($this instanceof TypeAttributeInterface && ($icon = $this->getTypeIcon())) {
            return $icon;
        }

        return $this instanceof StatusAttributeInterface ? ($this->getStatusIcon() ?: null) : null;
    }

    /**
     * Read through the magic getter, so a translated or custom `name` counts as one.
     */
    protected function getAdminNameAttributeValue(): string
    {
        if (!in_array('name', $this->attributes(), true)) {
            return '';
        }

        $attribute = $this instanceof I18nAttributeInterface
            ? $this->getI18nAttributeName('name', fallback: true)
            : 'name';

        $value = $this->$attribute;

        return is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * A composite key has no place in `COMMON_MODEL_ID`, so such a model falls back to its type.
     */
    protected function getAdminId(): int|string|null
    {
        if (!$this instanceof ActiveRecordInterface) {
            return null;
        }

        $id = $this->getPrimaryKey();

        return is_int($id) || (is_string($id) && $id !== '') ? $id : null;
    }
}
