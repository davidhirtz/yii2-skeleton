<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;

/**
 * `getAdminRoute()` and `getPermissionName()` are left to the model, since a silent default would hide every link
 * to it or show one it should not.
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

        return $this->getAdminNameAttributeValue() ?: Yii::t('skeleton', 'MODEL_ID_FORMAT', [
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

    public function getAdminParent(): ?AdminModelInterface
    {
        return null;
    }

    public function getAdminIndexBreadcrumb(): ?Breadcrumb
    {
        return null;
    }

    public function getAdminSubtitle(): ?string
    {
        return null;
    }

    protected function getAdminPositionLabel(?string $type = null, ?int $total = null): string
    {
        $type ??= $this->getAdminType();
        $position = $this instanceof ActiveRecordInterface ? $this->getAttribute('position') : null;

        if ($total && is_numeric($position) && $position > 0 && $position <= $total) {
            return Yii::t('skeleton', 'MODEL_POSITION_FORMAT', [
                'model' => $type,
                'position' => (int)$position,
                'total' => $total,
            ]);
        }

        $position ??= $this->getAdminId();

        return is_scalar($position)
            ? Yii::t('skeleton', 'MODEL_ID_FORMAT', ['model' => $type, 'id' => $position])
            : $type;
    }

    public function getParamName(): string
    {
        return Inflector::slug($this->formName());
    }

    public function getAdminIcon(): ?string
    {
        if ($this instanceof TypeAttributeInterface && ($icon = $this->getTypeIcon())) {
            return $icon;
        }

        return $this->getDefaultAdminIcon();
    }

    protected function getDefaultAdminIcon(): ?string
    {
        return null;
    }

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

    protected function getAdminId(): int|string|null
    {
        if (!$this instanceof ActiveRecordInterface) {
            return null;
        }

        $id = $this->getPrimaryKey();

        return is_int($id) || (is_string($id) && $id !== '') ? $id : null;
    }
}
