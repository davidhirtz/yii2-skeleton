<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Html\Script;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Thing extends Widget
{
    protected ?string $type = null;
    protected ?string $id = null;

    /**
     * @var array<string, mixed>
     */
    protected array $properties = [];

    public function type(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function properties(array $properties): static
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function addProperties(array $properties): static
    {
        $this->properties = [...$this->properties, ...$properties];
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function getNode(): array
    {
        return StructuredData::filter([
            '@type' => $this->type,
            '@id' => $this->id,
            ...$this->getProperties(),
        ]);
    }

    public function register(): void
    {
        $node = $this->build();

        if ($node !== null) {
            $this->view->registerStructuredData($node);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(): ?array
    {
        $this->configure();
        return $this->isVisible() ? $this->getNode() : null;
    }

    public function isVisible(): bool
    {
        return $this->type !== null && parent::isVisible();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getProperties(): array
    {
        return $this->properties;
    }

    protected function renderContent(): string|Stringable
    {
        return Script::make()
            ->type('application/ld+json')
            ->content(StructuredData::encode([$this->getNode()]));
    }
}
