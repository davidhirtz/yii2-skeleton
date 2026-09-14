<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Types;

use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Trail;
use Override;
use yii\base\InvalidConfigException;

class TrailType extends Type
{
    protected ?Message $message = null;
    protected ?int $parentType = null;
    protected bool $hasDataModel = false;

    /**
     * @param Message|null $message a pointer, not text: it is rendered in the language of whoever reads the trail.
     */
    public function message(?Message $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function parentType(?int $parentType): static
    {
        $this->parentType = $parentType;
        return $this;
    }

    public function hasDataModel(bool $hasDataModel = true): static
    {
        $this->hasDataModel = $hasDataModel;
        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function getParentType(): ?int
    {
        return $this->parentType;
    }

    public function hasDataModelEnabled(): bool
    {
        return $this->hasDataModel;
    }

    #[Override]
    public function validate(string $modelClass): void
    {
        parent::validate($modelClass);

        /** @var class-string<Trail> $modelClass */
        if ($this->parentType !== null && !isset($modelClass::getTypeDefinitions()[$this->parentType])) {
            throw new InvalidConfigException("{$this->getDisplayValue()} of $modelClass names the undeclared parent type {$this->parentType}.");
        }
    }
}
