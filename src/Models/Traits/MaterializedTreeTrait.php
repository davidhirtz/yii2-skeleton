<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use yii\db\ActiveQuery;
use Hirtz\Skeleton\Helpers\ArrayHelper;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property list<int>|null $path
 *
 * @property-read static[] $ancestors {@see static::getAncestors()}
 * @property-read static[] $children {@see static::getChildren()}
 * @property-read static[] $descendants {@see static::getDescendants()}
 * @property-read static|null $parent {@see static::getParent()}
 */
trait MaterializedTreeTrait
{
    /**
     * @var array<int|string, static>|null
     */
    private ?array $ancestors = null;
    /**
     * @var array<int|string, static>|null
     */
    private ?array $descendants = null;
    /**
     * @var array<int|string, static>|null
     */
    private ?array $children = null;

    /**
     * @return static[]
     */
    public function getAncestors(bool $refresh = false): array
    {
        if ($refresh) {
            $this->ancestors = null;
        }

        $this->ancestors ??= $this->path
            ? $this->findAncestors()
                ->indexBy('id')
                ->all()
            : [];

        return $this->ancestors;
    }

    /**
     * @param array<int|string, static> $ancestors
     */
    public function setAncestors(array $ancestors): void
    {
        $this->ancestors = [];

        if ($this->path) {
            $ancestors = ArrayHelper::index($ancestors, 'id');
            $ancestorIds = $this->getAncestorIds();

            foreach ($ancestorIds as $ancestorId) {
                if (isset($ancestors[$ancestorId])) {
                    $this->ancestors[$ancestorId] = $ancestors[$ancestorId];
                }
            }
        }
    }

    public function getFirstAncestor(): ?static
    {
        if (!$this->parent_id) {
            return null;
        }

        $ancestors = $this->getAncestors();
        return current($ancestors) ?: null;
    }

    /**
     * @return ActiveQuery<static>
     */
    public function findAncestors(): ActiveQuery
    {
        return static::find()
            ->where([
                'id' => $this->getAncestorIds(),
            ])
            ->orderBy([
                'path' => SORT_ASC,
            ]);
    }

    /**
     * @return static[]
     */
    public function getChildren(bool $refresh = false): array
    {
        if ($refresh) {
            $this->children = null;
        }

        $this->children ??= $this->findChildren()
            ->indexBy('id')
            ->all();

        return $this->children;
    }

    /**
     * @param array<int|string, static> $children
     */
    public function setChildren(array $children): void
    {
        $this->children = [];

        foreach ($children as $child) {
            if ($child['parent_id'] === $this->id) {
                $this->children[$child->id] = $child;
            }
        }
    }

    /**
     * @return ActiveQuery<static>
     */
    public function findChildren(): ActiveQuery
    {
        return static::find()
            ->where([
                'parent_id' => $this->id,
            ])
            ->orderBy([
                'position' => SORT_ASC,
            ]);
    }

    /**
     * @return static[]
     */
    public function getDescendants(bool $refresh = false): array
    {
        if ($refresh) {
            $this->descendants = null;
        }

        $this->descendants ??= $this->findDescendants()
            ->indexBy('id')
            ->all();

        return $this->descendants;
    }

    /**
     * @param array<int|string, static> $descendants
     */
    public function setDescendants(array $descendants): void
    {
        $path = $this->path ?? [];
        $length = count($path);
        $this->descendants = [];

        foreach ($descendants as $descendant) {
            if (array_slice($descendant->path ?? [], 0, $length) === $path) {
                $this->descendants[$descendant->id] = $descendant;
            }
        }
    }

    /**
     * @return ActiveQuery<static>
     */
    public function findDescendants(): ActiveQuery
    {
        $fieldName = static::tableName() . '.[[path]]';

        return static::find()
            ->where("JSON_CONTAINS($fieldName, :id)", [
                'id' => (string)$this->id,
            ])
            ->orderBy([
                'path' => SORT_ASC,
                'position' => SORT_ASC,
            ]);
    }

    /**
     * @return ActiveQuery<static>
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * @return ActiveQuery<static>
     */
    public function findSiblings(): ActiveQuery
    {
        return static::find()
            ->where(['parent_id' => $this->parent_id,])
            ->orderBy(['position' => SORT_ASC]);
    }

    /**
     * @return list<int>
     */
    public function getAncestorIds(): array
    {
        return array_map(intval(...), $this->path ?? []);
    }
}
