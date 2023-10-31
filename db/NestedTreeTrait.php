<?php

namespace davidhirtz\yii2\skeleton\db;

use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class NestedTreeTrait.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property int $id
 * @property int $parent_id
 * @property int $rgt
 * @property int $lft
 * @property string $name
 *
 * @property-read ActiveRecord $parent
 * @method static ActiveQuery find()
 */
trait NestedTreeTrait
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

    public function transactions(): array
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * @param bool $refresh
     * @return ActiveRecord[]
     */
    public function getAncestors($refresh = false): array
    {
        if ($this->_ancestors === null || $refresh) {
            $this->_ancestors = [];

            if ($this->parent_id) {
                $this->_ancestors = $this->findAncestors()
                    ->indexBy('id')
                    ->all();
            }
        }

        return $this->_ancestors;
    }

    /**
     * @param static[] $records
     */
    public function setAncestors(array $records): void
    {
        $this->_ancestors = [];

        if ($this->parent_id) {
            foreach ($records as $record) {
                if ($record->lft < $this->rgt) {
                    if ($record->rgt > $this->rgt) {
                        $this->_ancestors[$record->id] = $record;
                    }

                    continue;
                }

                break;
            }
        }
    }

    public function getFirstAncestor(): ?ActiveRecord
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
    public function findAncestors(): ActiveQuery
    {
        return static::find()->where('[[lft]]<:rgt AND [[rgt]]>:rgt', ['rgt' => $this->rgt]);
    }

    /**
     * @param bool $refresh
     * @return ActiveRecord[]
     */
    public function getDescendants(bool $refresh = false): array
    {
        if ($this->_descendants === null || $refresh) {
            $this->_descendants = [];

            if ($this->lft < $this->rgt + 1) {
                $this->_descendants = $this->findDescendants()
                    ->indexBy('id')
                    ->all();
            }
        }

        return $this->_descendants;
    }

    /**
     * @param static[] $records
     */
    public function setDescendants(array $records): void
    {
        $this->_descendants = [];

        if ($this->lft < $this->rgt + 1) {
            foreach ($records as $record) {
                if ($record->lft > $this->lft && $record->rgt < $this->rgt) {
                    $this->_descendants[$record->id] = $record;
                }
            }
        }
    }

    /**
     * @return ActiveQuery
     */
    public function findDescendants(): ActiveQuery
    {
        return static::find()->where('[[lft]]>:lft AND [[rgt]]<:rgt', ['lft' => $this->lft, 'rgt' => $this->rgt]);
    }

    /**
     * @return int
     */
    public function getBranchCount(): int
    {
        return ($this->rgt - $this->lft - 1) / 2;
    }

    /**
     * Validation rule.
     */
    public function validateParentId(): void
    {
        if ($this->parent_id) {
            if ($this->isAttributeChanged('parent_id', false)) {
                $parent = static::findOne([
                    'id' => $this->parent_id,
                ]);

                if (!$parent) {
                    $this->addInvalidAttributeError('parent_id');
                    return;
                }

                if (!$this->getIsNewRecord()) {
                    if ($this->parent_id == $this->id || ($this->lft < $parent->lft && $this->rgt > $parent->rgt)) {
                        $this->addInvalidAttributeError('parent_id');
                        return;
                    }
                }

                $this->populateParentRelation($parent);
            }
        } else {
            $this->parent_id = null;
        }
    }

    public function populateParentRelation(?ActiveRecord $parent): void
    {
        $this->populateRelation('parent', $parent);
        $this->parent_id = $parent ? (int)$parent->getPrimaryKey() : null;
    }

    /**
     * Updates tree before insert or update.
     */
    public function updateTreeBeforeSave(): void
    {
        if ($this->getIsNewRecord()) {
            if ($this->parent_id) {
                // Update right hand side of tree to make room for item.
                static::updateAllCounters(['lft' => 2, 'rgt' => 2], '[[lft]]>:rgt', [
                    'rgt' => $this->parent->getAttribute('rgt'),
                ]);

                // Update branch.
                static::updateAllCounters(['rgt' => 2], ':lft BETWEEN [[lft]] and [[rgt]]', [
                    'lft' => $this->parent->getAttribute('lft'),
                ]);

                $this->rgt = $this->parent->getAttribute('rgt') + 1;
                $this->lft = $this->parent->getAttribute('rgt');
            } else {
                $rgt = static::find()->max('rgt');

                $this->rgt = $rgt + 2;
                $this->lft = $rgt + 1;
            }
        } elseif ($this->isAttributeChanged('parent_id', false)) {
            $query = static::find()->select(['id'])->where('[[lft]] BETWEEN :lft AND :rgt', [
                'lft' => $this->lft,
                'rgt' => $this->rgt,
            ]);

            $branchIds = ArrayHelper::getColumn($query->all(), 'id');
            $diff = count($branchIds) * 2;

            // Remove branch from ancestors.
            static::updateAllCounters(['rgt' => -$diff], '[[lft]]<:rgt AND [[rgt]]>:rgt', [
                'rgt' => $this->rgt,
            ]);

            // Detach branch from right hand side of tree.
            static::updateAllCounters(['lft' => -$diff, 'rgt' => -$diff], '[[lft]]>:rgt', [
                'rgt' => $this->rgt,
            ]);

            if ($this->parent) {
                // Refresh tree attributes.
                $this->parent->refresh();

                // Update new ancestors.
                $query = (new Query())->where([
                    'not in',
                    'id',
                    $branchIds
                ])->andWhere(new Expression(':rgt BETWEEN [[lft]] AND [[rgt]]', ['rgt' => $this->parent->getAttribute('rgt')]));
                static::updateAllCounters(['rgt' => $diff], $query->where);

                // Update new right hand side of tree.
                $query = (new Query())
                    ->where(['not in', 'id', $branchIds])
                    ->andWhere(new Expression('[[lft]]>:rgt', ['rgt' => $this->parent->getAttribute('rgt')]));

                static::updateAllCounters(['lft' => $diff, 'rgt' => $diff], $query->where);

                $this->parent->setAttribute('rgt', $this->parent->getAttribute('rgt') + $diff);
                $diff = $this->parent->getAttribute('rgt') > $this->rgt ? ('+' . ($this->parent->getAttribute('rgt') - $this->rgt - 1)) : ('-' . abs($this->parent->getAttribute('rgt') - $this->rgt - 1));
            } else {
                // Find max right excluding self and children.
                $rgt = static::find()->where(['not in', 'id', $branchIds])->max('rgt');
                $diff = $rgt - $this->lft + 1;
            }

            // Update branch.
            static::updateAllCounters(['lft' => $diff, 'rgt' => $diff], ['in', 'id', $branchIds]);

            $this->rgt += $diff;
            $this->lft += $diff;
        }
    }

    /**
     * Updates tree after delete.
     */
    public function updateNestedTreeAfterDelete(): void
    {
        // Update branch, then right side.
        static::updateAllCounters(['rgt' => -2], '[[lft]]<:rgt AND [[rgt]]>:rgt', ['rgt' => $this->rgt]);
        static::updateAllCounters(['lft' => -2, 'rgt' => -2], '[[lft]]>:rgt', ['rgt' => $this->rgt]);
    }

    /**
     * Deletes nested records one by one, triggering all related active record events.
     */
    public function deleteNestedTreeItems(): void
    {
        while (true) {
            $record = static::find()
                ->where('[[lft]]>:lft AND [[rgt]]<:rgt', ['lft' => $this->lft, 'rgt' => $this->rgt])
                ->orderBy(['lft' => SORT_DESC])
                ->one();

            if (!$record) {
                break;
            }

            $record->delete();
            $this->refresh();
        }
    }

    /**
     * @param ActiveRecord|null $parent
     * @param array $order
     */
    public static function rebuildNestedTree(?ActiveRecord $parent = null, array $order = []): void
    {
        $parentId = $parent?->getPrimaryKey();
        $query = static::find();

        if ($parent) {
            $query->where('[[lft]]>:lft AND [[rgt]]<:rgt', [
                'lft' => $parent->getAttribute('lft'),
                'rgt' => $parent->getAttribute('rgt'),
            ]);
        }

        /** @var static[] $models */
        $models = $query->select(['id', 'parent_id', 'lft', 'rgt'])
            ->orderBy(['lft' => SORT_ASC, 'position' => SORT_ASC])
            ->indexBy('id')
            ->all();

        $tree = [];

        foreach ($models as $model) {
            $tree[$model->parent_id][] = (int)$model->id;
        }

        if ($order) {
            $children = array_flip($tree[$parentId]);
            $tree[$parentId] = array_flip(array_intersect_key($order, $children) + $children);
        }

        $lft = $parent ? $parent->getAttribute('lft') + 1 : 1;
        $tree = static::rebuildNestedTreeBranch($tree, $lft, $parentId);

        foreach ($models as $model) {
            $model->updateAttributes($tree[$model->id]);
        }
    }

    /**
     * @param array $branch
     * @param int $lft
     * @param int $parentId
     *
     * @return array()
     */
    private static function rebuildNestedTreeBranch(array $branch, int &$lft, int $parentId): array
    {
        $tree = [];

        foreach ($branch[$parentId] as $id) {
            $tree[$id]['lft'] = $lft++;

            if (isset($branch[$id])) {
                $tree += static::rebuildNestedTreeBranch($branch, $lft, $id);
            }

            $tree[$id]['rgt'] = $lft++;
        }

        return $tree;
    }

    /**
     * @param ActiveRecord[] $records
     * @param string $attribute
     * @param string $indent
     * @return array
     */
    public static function indentNestedTree(array $records, string $attribute, string $indent = '-'): array
    {
        $items = $cache = [];
        $padding = $rgt = 0;

        foreach ($records as $record) {
            if ($record->getAttribute('lft') < $rgt) {
                ++$padding;
                $cache[$record->getAttribute('parent_id')] = $padding;
            } elseif ($record->getAttribute('lft') > $rgt + 1) {
                $padding = $record->getAttribute('parent_id') ? $cache[$record->getAttribute('parent_id')] : 0;
            }

            $items[$record->getPrimaryKey()] = trim(str_repeat($indent, $padding) . ' ' . $record->getAttribute($attribute));
            $rgt = $record->getAttribute('rgt');
        }

        return $items;
    }
}