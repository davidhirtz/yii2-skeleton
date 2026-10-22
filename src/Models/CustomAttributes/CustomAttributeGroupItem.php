<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Override;
use yii\base\Model;
use yii\db\BaseActiveRecord;

/**
 * One row of a {@see GroupCustomAttribute}. Its form name carries the owner's, so `Html::getInputName()` produces
 * `Section[links][0][label]` and the nested case works by recursion.
 */
class CustomAttributeGroupItem extends Model implements CustomAttributeInterface, I18nAttributeInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use ModelTrait;

    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function __construct(
        private readonly GroupCustomAttribute $group,
        private readonly Model $owner,
        private readonly string $formName,
        private readonly ?string $index = null,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    #[Override]
    public function attributes(): array
    {
        return $this->getCustomAttributeNames();
    }

    #[Override]
    public function formName(): string
    {
        return $this->formName;
    }

    #[Override]
    public function rules(): array
    {
        return $this->getCustomAttributeRules();
    }

    #[Override]
    public function attributeLabels(): array
    {
        return $this->getCustomAttributeLabels();
    }

    #[Override]
    public function attributeHints(): array
    {
        return array_filter($this->getCustomAttributeHints(), static fn (?string $hint): bool => $hint !== null);
    }

    #[Override]
    public function getCustomAttributes(): array
    {
        return $this->group->getAttributes();
    }

    public function getGroup(): GroupCustomAttribute
    {
        return $this->group;
    }

    public function getOwner(): Model
    {
        return $this->owner;
    }

    #[Override]
    public function beforeValidate(): bool
    {
        $this->applyCustomAttributeDefaults();
        return parent::beforeValidate();
    }

    /**
     * The row's stored value, so a child can tell what the form showed from what it changed.
     */
    public function getOldAttribute(string $name): mixed
    {
        $old = $this->owner instanceof BaseActiveRecord || $this->owner instanceof self
            ? $this->owner->getOldAttribute($this->group->name)
            : null;

        $row = $this->index !== null && is_array($old) ? ($old[$this->index] ?? null) : $old;
        $value = is_array($row) ? ($row[$name] ?? null) : null;
        $definition = $this->getCustomAttribute($name);

        return $definition ? $definition->unserialize($value) : $value;
    }

    public function hasAttribute(string $name): bool
    {
        return in_array($name, $this->attributes(), true);
    }

    public function getAttribute(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    #[Override]
    public function __get($name)
    {
        return $this->hasAttribute((string)$name) ? $this->getAttribute((string)$name) : parent::__get($name);
    }

    #[Override]
    public function __set($name, $value): void
    {
        if ($this->hasAttribute((string)$name)) {
            $this->setAttribute((string)$name, $value);
            return;
        }

        parent::__set($name, $value);
    }

    #[Override]
    public function __isset($name): bool
    {
        return $this->hasAttribute((string)$name) ? $this->getAttribute((string)$name) !== null : parent::__isset($name);
    }
}
