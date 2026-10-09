<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Html\Script;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

/**
 * A schema.org node of any type. {@see static::register()} adds it to the page's graph, which the view renders as one
 * script in the head; rendering it prints a script of its own.
 *
 * ```php
 * Thing::make()
 *     ->type('Event')
 *     ->id(StructuredData::id($url, 'event'))
 *     ->properties(['name' => $entry->name, 'startDate' => StructuredData::date($entry->publish_date)])
 *     ->register();
 * ```
 */
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

    /**
     * Adds the node to the page's graph, replacing one with the same `@id`.
     */
    public function register(): void
    {
        $this->configure();

        if ($this->isVisible()) {
            $this->view->registerStructuredData($this->getNode());
        }
    }

    /**
     * A node needs a type.
     */
    public function isVisible(): bool
    {
        return $this->type !== null && parent::isVisible();
    }

    /**
     * The properties a subclass derives, followed by the ones set, which win.
     *
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
