<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;

/**
 * {@see AdminModelInterface::getAdminRoute()} and {@see AdminModelInterface::getPermissionName()} are deliberately
 * not implemented here: only the model knows its controller and the permission guarding it, and a silent default
 * would hide every link to it — or, worse, show one it should not.
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

    /**
     * The shape a subordinate record answers {@see AdminModelInterface::getAdminSubtitle()} with: its noun and
     * its position among its siblings, falling back to the primary key for a model that has no `position`.
     * Read through `getAttribute()` rather than the magic property, which is undeclared on a model without the
     * column.
     *
     * @param string|null $type a noun to use in place of {@see static::getAdminType()}, for a record whose type
     *     name repeats what the subtitle already says before it
     */
    protected function getAdminPositionLabel(?string $type = null): string
    {
        $type ??= $this->getAdminType();
        $position = $this instanceof ActiveRecordInterface ? $this->getAttribute('position') : null;
        $position ??= $this->getAdminId();

        return is_scalar($position)
            ? Yii::t('skeleton', 'COMMON_MODEL_ID', ['model' => $type, 'id' => $position])
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
