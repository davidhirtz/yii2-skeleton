<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Definitions;

use yii\base\InvalidConfigException;

/**
 * A definition a model declares for one of its attributes, resolved and validated against that model by
 * {@see DefinitionRegistry}.
 */
abstract class ModelDefinition extends Definition
{
    protected ?string $plural = null;

    public function plural(?string $plural): static
    {
        $this->plural = $plural;
        return $this;
    }

    /**
     * Falls back to the name, not to an inflection: `Inflector::pluralize()` is English-only and this is what the
     * admin navigation renders.
     */
    public function getPlural(): string
    {
        return $this->plural ?? $this->getName();
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
}
