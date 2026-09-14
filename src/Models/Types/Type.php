<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Types;

use Closure;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\Definitions\Definition;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Override;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\BaseActiveRecord;

class Type extends Definition
{
    /**
     * @var class-string<Model>|null
     */
    protected ?string $modelClass = null;

    /**
     * @var list<string>
     */
    protected array $hiddenFields = [];

    /**
     * @var list<CustomAttribute>|Closure(Model): list<CustomAttribute>|null
     */
    protected array|Closure|null $customAttributes = null;

    protected Closure|bool $available = true;

    /**
     * @param class-string<Model>|null $modelClass the model class records of this type are instantiated as, see
     * {@see TypeAttributeTrait::instantiate()}
     */
    public function modelClass(?string $modelClass): static
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function hiddenFields(string ...$hiddenFields): static
    {
        $this->hiddenFields = array_values($hiddenFields);
        return $this;
    }

    /**
     * @param list<CustomAttribute>|Closure(Model): list<CustomAttribute>|null $customAttributes
     */
    public function customAttributes(array|Closure|null $customAttributes): static
    {
        $this->customAttributes = $customAttributes;
        return $this;
    }

    /**
     * Whether the type is offered for a record in the admin — not a frontend render filter, which a subclass adds
     * under its own name.
     */
    public function available(Closure|bool $available = true): static
    {
        $this->available = $available;
        return $this;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    /**
     * @return list<string>
     */
    public function getHiddenFields(): array
    {
        return $this->hiddenFields;
    }

    /**
     * @return list<CustomAttribute>
     */
    public function getCustomAttributes(Model $owner): array
    {
        return $this->customAttributes instanceof Closure
            ? ($this->customAttributes)($owner)
            : $this->customAttributes ?? [];
    }

    public function isAvailable(?Model $owner): bool
    {
        return $this->available instanceof Closure ? (bool)($this->available)($owner) : $this->available;
    }

    /**
     * A rule stops matching records that already hold the type — a parent's type changed, a record moved to
     * another tenant — and those have to keep saving and keep showing what they are. So the value a record is
     * stored with stays offered and stays valid, while every other unavailable one does not.
     */
    public function isAvailableOrStored(Model $owner, string $attribute): bool
    {
        if ($this->isAvailable($owner)) {
            return true;
        }

        $stored = $owner instanceof BaseActiveRecord ? $owner->getOldAttribute($attribute) : null;

        return $stored !== null && (string)$stored === (string)$this->value;
    }

    #[Override]
    public function validate(string $modelClass): void
    {
        parent::validate($modelClass);

        if ($this->modelClass !== null && !is_a($this->modelClass, $modelClass, true)) {
            throw new InvalidConfigException("{$this->getDisplayValue()} of $modelClass names the model class {$this->modelClass}, which is not a subclass of it.");
        }
    }
}
