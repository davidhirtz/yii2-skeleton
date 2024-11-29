<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\interfaces;

interface TypeAttributeInterface
{
    public const TYPE_DEFAULT = 1;

    public static function getTypes(): array;

    public function getTypeIcon(): string;

    public function getTypeName(): string;

    public function getTypeOptions(): array;

    public static function instantiate($row): static;
}
