<?php

namespace davidhirtz\yii2\skeleton\models\queries;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

/**
 * Class AuthItemQuery.
 * @package davidhirtz\yii2\skeleton\models\queries
 */
class AuthItemQuery extends ActiveQuery
{
	/**
	 * @return $this
	 */
	public function orderByType()
	{
		return $this->orderBy(['type'=>SORT_ASC, 'name'=>SORT_ASC]);
	}

	/**
	 * @param int $userId
	 * @return $this
	 */
	public function withAssignment($userId)
	{
		$this->addSelect(['isAssigned'=>'([[item_name]]=[[name]])']);
		$this->join('LEFT JOIN', Yii::$app->authManager->assignmentTable, '[[item_name]]=[[name]] AND [[user_id]]=:userId', ['userId'=>$userId]);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withUsers()
	{
		return $this->with([
			'users'=>function(UserQuery $query)
				{
					$query->selectListAttributes()
						->orderBy(['name'=>SORT_ASC]);
				}
		]);
	}

	/**
	 * @param Connection $db
	 * @return \davidhirtz\yii2\skeleton\models\AuthItem[]|array
	 */
	public function allWithChildren($db=null)
	{
		/**  @var \davidhirtz\yii2\skeleton\models\AuthItem[] $items */
		$items=ArrayHelper::index(parent::all($db), 'name');
		$relations=(new \yii\db\Query())->select('*')->from(Yii::$app->getAuthManager()->itemChildTable)->all();

		foreach($relations as $relation)
		{
			$this->setAuthItemChild($items, $relations, $relation['parent'], $relation['child']);
		}

		return $items;
	}

	/**
	 * @param \davidhirtz\yii2\skeleton\models\AuthItem[] $items
	 * @param array $relations
	 * @param string $parent
	 * @param string $child
	 */
	private function setAuthItemChild(&$items, $relations, $parent, $child)
	{
		if(!$items[$child]->isInherited)
		{
			$items[$child]->isInherited=$items[$parent]->isAssigned;
		}

		$items[$parent]->children[$child]=$items[$child];

		foreach($relations as $relation)
		{
			if($relation['parent']==$child)
			{
				$this->setAuthItemChild($items, $relations, $parent, $relation['child']);
			}
		}

	}
}