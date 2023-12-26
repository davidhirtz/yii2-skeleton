<?php

namespace davidhirtz\yii2\skeleton\models\queries;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\models\AuthItem;
use Yii;
use yii\db\Connection;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class AuthItemQuery extends ActiveQuery
{
    public function orderByType(): static
    {
        return $this->orderBy(['type' => SORT_ASC, 'name' => SORT_ASC]);
    }

    public function withAssignment(int $userId): static
    {
        $this->addSelect(['isAssigned' => '([[item_name]]=[[name]])']);
        $this->join('LEFT JOIN', Yii::$app->authManager->assignmentTable, '[[item_name]]=[[name]] AND [[user_id]]=:userId', ['userId' => $userId]);

        return $this;
    }

    public function withUsers(): static
    {
        return $this->with([
            'users' => function (UserQuery $query): void {
                $query->selectListAttributes()
                    ->orderBy(['name' => SORT_ASC]);
            }
        ]);
    }

    /**
     * @return AuthItem[]
     */
    public function allWithChildren(?Connection$db = null): array
    {
        /**  @var AuthItem[] $items */
        $items = ArrayHelper::index(parent::all($db), 'name');

        $relations = (new Query())->select('*')
            ->from(Yii::$app->getAuthManager()->itemChildTable)
            ->all();

        foreach ($relations as $relation) {
            $this->setAuthItemChild($items, $relations, $relation['parent'], $relation['child']);
        }

        return $items;
    }

    private function setAuthItemChild(array &$items, array $relations, string $parent, string$child): void
    {
        if (!$items[$child]->isInherited) {
            $items[$child]->isInherited = $items[$parent]->isAssigned;
        }

        $items[$parent]->children[$child] = $items[$child];

        foreach ($relations as $relation) {
            if ($relation['parent'] == $child) {
                $this->setAuthItemChild($items, $relations, $parent, $relation['child']);
            }
        }
    }
}
