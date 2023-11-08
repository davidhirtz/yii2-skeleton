<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;

/**
 * @property int $id
 * @property int $parent_id
 * @property string $path
 *
 * @property-read static[] $ancestors {@see static::getAncestors()}
 * @property-read static[] $children {@see static::getChildren()}
 * @property-read static[] $descendants {@see static::getDescendants()}
 * @property-read static $parent {@see static::getParent()}
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
        if ($this->_ancestors === null || $refresh) {
            $this->_ancestors = !$this->path ? [] : $this->findAncestors()
                ->indexBy('id')
                ->all();
        }

        return $this->_ancestors;
    }

    public function setAncestors(array $ancestors): void
    {
        $this->_ancestors = [];

        if ($this->path) {
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
        return static::find()->where(['id' => $this->getAncestorIds()])
            ->orderBy(['path' => SORT_ASC]);
    }

    /**
     * @return static[]
     */
    public function getChildren(bool $refresh = false): array
    {
        if ($this->_children === null || $refresh) {
            $this->_children = $this->findChildren()
                ->indexBy('id')
                ->all();
        }

        return $this->_children;
    }

    public function setChildren(array $children): void
    {
        $this->_children = [];

        foreach ($children as $child) {
            if ($child['parent_id'] == $this->id) {
                $this->_children[$child->id] = $child;
            }
        }
    }

    public function findChildren(): ActiveQuery
    {
        return static::find()->where(['parent_id' => $this->id]);
    }

    /**
     * @return static[]
     */
    public function getDescendants(bool $refresh = false): array
    {
        if ($this->_descendants === null || $refresh) {
            $this->_descendants = $this->findDescendants()
                ->indexBy('id')
                ->all();
        }

        return $this->_descendants;
    }

    public function setDescendants(array $descendants): void
    {
        $this->_descendants = [];
        $length = strlen($this->path);

        foreach ($descendants as $descendant) {
            if (substr($descendant->path, 0, $length) === $this->path) {
                $this->_descendants[$descendant->id] = $descendant;
            }
        }
    }

    public function findDescendants(): ActiveQuery
    {
        $path = ArrayHelper::createCacheString(array_merge($this->getAncestorIds(), [$this->id]));
        $fieldName = static::tableName() . '.[[path]]';

        return static::find()->where($fieldName . ' = :path OR ' . $fieldName . ' LIKE :like', [
            'path' => $path,
            'like' => $path . ',%',
        ])->orderBy(['path' => SORT_ASC]);
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    public function findSiblings(): ActiveQuery
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    public function getAncestorIds(): array
    {
        return ArrayHelper::cacheStringToArray($this->path);
    }
}
