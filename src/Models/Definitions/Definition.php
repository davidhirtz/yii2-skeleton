<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Definitions;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;

/**
 * A definition is born with its value and must never be mutated afterwards: {@see DefinitionRegistry} caches it and
 * every record of that value shares the instance.
 */
abstract class Definition
{
    use ContainerConfigurationTrait;

    protected ?string $name = null;
    protected ?string $plural = null;
    protected ?string $icon = null;

    public function __construct(public readonly int|string $value)
    {
    }

    public function name(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function plural(?string $plural): static
    {
        $this->plural = $plural;
        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getPlural(): string
    {
        return $this->plural ?? Inflector::pluralize($this->getName());
    }

    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * @param class-string $modelClass
     */
    public function validate(string $modelClass): void
    {
        if ($this->getName() === '') {
            throw new InvalidConfigException("$modelClass declares {$this->getDisplayValue()} without a name.");
        }
    }

    protected function getDisplayValue(): string
    {
        return static::class . ' "' . $this->value . '"';
    }
}
