<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Xml;

use SimpleXMLElement;
use yii\base\InvalidArgumentException;

/**
 * A namespace-aware read of a {@see SimpleXMLElement} tree: an attribute and a child of a prefixed namespace keeps
 * its prefix (`dc:creator`), which the element's own accessors drop because they answer one namespace at a time.
 */
class XmlNode
{
    /**
     * @param array<string, string> $attributes
     * @param array<string, list<self>> $children
     */
    final public function __construct(
        public readonly string $name,
        public readonly ?string $text = null,
        public readonly array $attributes = [],
        public readonly array $children = [],
    ) {
    }

    public static function fromString(string $xml): static
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml);

        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        // An element without content, attributes or children is falsy, so only an identity check reports a failure.
        if ($element === false) {
            throw new InvalidArgumentException('The given string is not valid XML.');
        }

        return static::fromElement($element);
    }

    public static function fromElement(SimpleXMLElement $element): static
    {
        $attributes = [];
        $children = [];

        // The empty prefix comes last, it reads whatever carries no namespace and must not be overridden by a
        // default namespace declared on the document.
        $prefixes = [...array_keys($element->getDocNamespaces(true)), ''];

        foreach ($prefixes as $prefix) {
            foreach ($element->attributes($prefix, true) ?? [] as $name => $value) {
                $attributes[static::getPrefixedName($prefix, trim((string)$name))] = trim((string)$value);
            }

            foreach ($element->children($prefix, true) ?? [] as $name => $child) {
                $children[static::getPrefixedName($prefix, (string)$name)][] = static::fromElement($child);
            }
        }

        $text = trim((string)$element);

        return new static($element->getName(), $text === '' ? null : $text, $attributes, $children);
    }

    public function getAttribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    public function getChild(string $name): ?self
    {
        return $this->getChildren($name)[0] ?? null;
    }

    /**
     * @return list<self>
     */
    public function getChildren(string $name): array
    {
        return $this->children[$name] ?? [];
    }

    /**
     * @return array{
     *     name: string,
     *     text: string|null,
     *     attributes: array<string, string>,
     *     children: array<string, list<array<string, mixed>>>,
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'text' => $this->text,
            'attributes' => $this->attributes,
            'children' => array_map(
                static fn (array $children): array => array_map(
                    static fn (self $child): array => $child->toArray(),
                    $children,
                ),
                $this->children,
            ),
        ];
    }

    protected static function getPrefixedName(string $prefix, string $name): string
    {
        return $prefix === '' ? $name : "$prefix:$name";
    }
}
