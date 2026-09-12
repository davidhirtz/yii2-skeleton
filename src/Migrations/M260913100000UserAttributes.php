<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\ColumnSchemaBuilder;
use yii\db\JsonExpression;
use yii\db\Migration;
use yii\db\Query;

/**
 * The column names are hardcoded: `User` no longer declares them, and a project that keeps them declares them as
 * custom attributes instead, which only resolves once the columns are gone.
 *
 * @noinspection PhpUnused
 */
class M260913100000UserAttributes extends Migration
{
    use MigrationTrait;

    /**
     * @var list<string> copied into `custom_attributes`; `picture` is dropped with the feature
     */
    private const array LEGACY_COLUMNS = [
        'first_name',
        'last_name',
        'birthdate',
        'city',
        'country',
    ];

    private const string PICTURE_COLUMN = 'picture';

    public function safeUp(): void
    {
        $values = $this->readLegacyValues();

        $this->addCustomAttributesColumn(User::tableName());

        foreach ([...self::LEGACY_COLUMNS, self::PICTURE_COLUMN] as $column) {
            if ($this->hasColumn(User::tableName(), $column)) {
                $this->dropIndexesContainingColumn(User::tableName(), $column);
                $this->dropColumn(User::tableName(), $column);
            }
        }

        foreach ($values as $id => $attributes) {
            $this->update(User::tableName(), [
                'custom_attributes' => new JsonExpression($attributes),
            ], ['id' => $id]);
        }
    }

    public function safeDown(): void
    {
        $values = $this->readCustomAttributes();

        foreach ($this->getLegacyColumnTypes() as $column => $type) {
            $this->addColumn(User::tableName(), $column, (string)$type);
        }

        foreach ($values as $id => $attributes) {
            $this->update(User::tableName(), $attributes, ['id' => $id]);
        }

        $this->dropCustomAttributesColumn(User::tableName());
    }

    /**
     * @return array<string, ColumnSchemaBuilder>
     */
    private function getLegacyColumnTypes(): array
    {
        return [
            'first_name' => $this->string(50)->null(),
            'last_name' => $this->string(50)->null(),
            'birthdate' => $this->date()->null(),
            'city' => $this->string(50)->null(),
            'country' => $this->string(2)->null(),
            self::PICTURE_COLUMN => $this->string(50)->null(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readLegacyValues(): array
    {
        $columns = array_filter(
            self::LEGACY_COLUMNS,
            fn (string $column): bool => $this->hasColumn(User::tableName(), $column),
        );

        if (!$columns) {
            return [];
        }

        $rows = (new Query())
            ->select(['id', ...$columns])
            ->from(User::tableName())
            ->all($this->getDb());

        $values = [];

        foreach ($rows as $row) {
            $attributes = array_filter(
                array_intersect_key($row, array_flip($columns)),
                static fn (mixed $value): bool => $value !== null && $value !== '',
            );

            if ($attributes) {
                $values[(int)$row['id']] = array_map(strval(...), $attributes);
            }
        }

        return $values;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCustomAttributes(): array
    {
        $rows = (new Query())
            ->select(['id', 'custom_attributes'])
            ->from(User::tableName())
            ->all($this->getDb());

        $values = [];

        foreach ($rows as $row) {
            $attributes = json_decode((string)$row['custom_attributes'], true);
            $attributes = array_intersect_key(
                is_array($attributes) ? $attributes : [],
                array_flip(self::LEGACY_COLUMNS),
            );

            if ($attributes) {
                $values[(int)$row['id']] = array_map(strval(...), $attributes);
            }
        }

        return $values;
    }
}
