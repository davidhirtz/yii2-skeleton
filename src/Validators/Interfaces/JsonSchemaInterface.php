<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators\Interfaces;

use Hirtz\Skeleton\Mcp\SchemaBuilder;
use yii\base\Model;

/**
 * Describes a validator's attribute to an MCP client, for a validator {@see SchemaBuilder} cannot recognise by
 * class.
 */
interface JsonSchemaInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(Model $model, string $attribute): array;
}
