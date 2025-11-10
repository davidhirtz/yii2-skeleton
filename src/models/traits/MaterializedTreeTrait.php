<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $path
 * @property int $position
 *
 * @property-read static[] $ancestors {@see static::getAncestors()}
 * @property-read static[] $children {@see static::getChildren()}
 * @property-read static[] $descendants {@see static::getDescendants()}
 * @property-read static|null $parent {@see static::getParent()}
 */
trait MaterializedTreeTrait
{
    private ?array $_ancestors = null;
    private ?array $_descendants = null;
    private ?array $_children = null;

    /**
     * @return static[]
     */
    public function getAncestors(bool $refresh = false): array
    {
        if ($refresh) {
            $this->_ancestors = null;
        }

        $this->_ancestors ??= $this->path
            ? $this->findAncestors()
                ->indexBy('id')
                ->all()
            : [];

        return $this->_ancestors;
    }

    public function setAncestors(array $ancestors): void
    {
        $this->_ancestors = [];

        if ($this->path) {
            $ancestors = ArrayHelper::index($ancestors, 'id');
            $ancestorIds = $this->getAncestorIds();

            foreach ($ancestorIds as $ancestorId) {
                if (isset($ancestors[$ancestorId])) {
                    $this->_ancestors[$ancestorId] = $ancestors[$ancestorId];
                }
            }
        }
    }

    public function getFirstAncestor(): ?static
    {
        return $this->parent_id ? current($this->ancestors) : null;
    }

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
            $this->_children = null;
        }

        $this->_children ??= $this->findChildren()
            ->indexBy('id')
            ->all();

        return $this->_children;
    }

    public function setChildren(array $children): void
    {
        $this->_children = [];

        foreach ($children as $child) {
            if ($child['parent_id'] === $this->id) {
                $this->_children[$child->id] = $child;
            }
        }
    }

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
            $this->_descendants = null;
        }

        $this->_descendants ??= $this->findDescendants()
            ->indexBy('id')
            ->all();

        return $this->_descendants;
    }

    public function setDescendants(array $descendants): void
    {
        $length = $this->path ? strlen($this->path) : 0;
        $this->_descendants = [];

        foreach ($descendants as $descendant) {
            $path = $descendant->path ? substr((string)$descendant->path, 0, $length) : null;

            if ($path === $this->path) {
                $this->_descendants[$descendant->id] = $descendant;
            }
        }
    }

    public function findDescendants(): ActiveQuery
    {
        $path = $this->getPathFromIds([
            ...$this->getAncestorIds(),
            $this->id,
        ]);

        $fieldName = static::tableName() . '.[[path]]';

        return static::find()
            ->where("$fieldName = :path OR $fieldName LIKE :partialPath", [
                'path' => $path,
                'partialPath' => "$path,%",
            ])
            ->orderBy([
                'path' => SORT_ASC,
                'position' => SORT_ASC,
            ]);
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    public function findSiblings(): ActiveQuery
    {
        return static::find()
            ->where([
                'parent_id' => $this->parent_id,
            ])
            ->orderBy([
                'position' => SORT_ASC,
            ]);
    }

    public function getAncestorIds(): array
    {
        return $this->getIdsFromPath($this->path);
    }

    public function getIdsFromPath(?string $path): array
    {
        return ArrayHelper::cacheStringToArray($path);
    }

    public function getPathFromIds(array $ids = []): string
    {
        return ArrayHelper::createCacheString($ids);
    }
}
