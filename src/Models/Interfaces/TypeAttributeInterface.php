<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Types\Type;

/**
 * @property int $type
 */
interface TypeAttributeInterface
{
    public const int TYPE_DEFAULT = 1;

    /**
     * The declaration, and the only instance method of the three: it is what an installation replaces through the
     * container, and {@see \Hirtz\Skeleton\Models\Definitions\DefinitionRegistry} is its only caller.
     *
     * @return list<Type>
     */
    public function getTypes(): array;

    /**
     * @return class-string<Type>
     */
    public static function getTypeClass(): string;

    /**
     * @return array<int, Type>
     */
    public static function getTypeDefinitions(): array;

    public static function findType(?int $type): ?Type;

    public function getType(): ?Type;

    public function getTypeIcon(): string;

    public function getTypeName(): string;

    public function getTypePlural(): string;

    /**
     * @param array<string, mixed> $row
     */
    public static function instantiate($row): static;

    /**
     * @return array<int, static>
     */
    public static function getTypeInstances(): array;
}
