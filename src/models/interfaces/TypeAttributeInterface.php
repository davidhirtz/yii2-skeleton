<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\models\interfaces;

interface TypeAttributeInterface
{
    public const int TYPE_DEFAULT = 1;

    public static function getTypes(): array;

    public function getTypeIcon(): string;

    public function getTypeName(): string;

    public function getTypePlural(): string;

    public function getTypeOptions(): array;

    public static function instantiate($row): static;

    /**
     * @return static[]
     */
    public static function getTypeInstances(): array;
}
