<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;

/**
 * Class MaterializedTreeTrait
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property int $id
 * @property int $parent_id
 * @property string $path
 *
 * @property ActiveRecord $parent
 * @method static ActiveQuery find()
 */
trait MaterializedTreeTrait
{
    /**
     * @var ActiveRecord[]
     * @see getAncestors()
     */
    private $_ancestors;

    /**
     * @var ActiveRecord[]
     * @see getDescendants()
     */
    private $_descendants;

    /**
     * @var ActiveRecord[]
     * @see getChildren()
     */
    private $_children;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * @param bool $refresh
     * @return ActiveRecord[]
     */
    public function getAncestors($refresh = false)
    {
        if ($this->_ancestors === null || $refresh) {
            $this->_ancestors = !$this->path ? [] : $this->findAncestors()
                ->indexBy('id')
                ->all();
        }

        return $this->_ancestors;
    }

    /**
     * @param ActiveRecord[]|null $ancestors
     */
    public function setAncestors($ancestors)
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

    /**
     * @return ActiveRecord|null
     */
    public function getFirstAncestor()
    {
        if ($this->parent_id) {
            $ancestors = $this->getAncestors();
            return current($ancestors);
        }

        return null;
    }

    /**
     * @return ActiveQuery
     */
    public function findAncestors()
    {
        return static::find()->where(['id' => $this->getAncestorIds()])
            ->orderBy(['path' => SORT_ASC]);
    }

    /**
     * @param bool $refresh
     * @return ActiveRecord[]
     */
    public function getChildren($refresh = false)
    {
        if ($this->_children === null || $refresh) {
            $this->_children = $this->findChildren()
                ->indexBy('id')
                ->all();
        }

        return $this->_children;
    }

    /**
     * @param static[] $children
     */
    public function setChildren($children)
    {
        $this->_children = [];

        foreach ($children as $child) {
            if ($child['parent_id'] == $this->id) {
                $this->_children[$child->id] = $child;
            }
        }
    }

    /**
     * @return ActiveQuery
     */
    public function findChildren()
    {
        return static::find()->where(['parent_id' => $this->id]);
    }

    /**
     * @return ActiveQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    /**
     * @return array
     */
    public function getAncestorIds(): array
    {
        return ArrayHelper::cacheStringToArray($this->path);
    }

    /**
     * @param bool $refresh
     * @return ActiveRecord[]
     */
    public function getDescendants($refresh = false)
    {
        if ($this->_descendants === null || $refresh) {
            $this->_descendants = $this->findDescendants()
                ->indexBy('id')
                ->all();
        }

        return $this->_descendants;
    }

    /**
     * @param static[] $descendants
     */
    public function setDescendants($descendants)
    {
        $this->_descendants = [];
        $length = strlen($this->path);

        foreach ($descendants as $descendant) {
            if (substr($descendant->path, 0, $length) === $this->path) {
                $this->_descendants[$descendant->id] = $descendant;
            }
        }
    }

    /**
     * @return ActiveQuery
     */
    public function findDescendants()
    {
        $path = ArrayHelper::createCacheString(array_merge($this->getAncestorIds(), [$this->id]));
        $fieldName = static::tableName() . '.[[path]]';

        return static::find()->where($fieldName . ' = :path OR ' . $fieldName . ' LIKE :like', [
            'path' => $path,
            'like' => $path . ',%',
        ])->orderBy(['path' => SORT_ASC]);
    }
}