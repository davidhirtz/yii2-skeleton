<?php
declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\traits;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFieldNew;

trait FieldsTrait
{
    /**
     * @var ActiveFieldNew[]|string[]
     */
    protected ?array $fields;

    public function fields(ActiveFieldNew|string ...$fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function addField(ActiveFieldNew|string ...$fields): static
    {
        $this->fields = [
            ...($this->fields ?? []),
            ...$fields,
        ];

        return $this;
    }
}