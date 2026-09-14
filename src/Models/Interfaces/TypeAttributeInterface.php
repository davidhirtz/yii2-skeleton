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
     * @return list<Type>
     */
    public static function getTypes(): array;

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

    public static function instantiate($row): static;

    /**
     * @return array<int, static>
     */
    public static function getTypeInstances(): array;
}
