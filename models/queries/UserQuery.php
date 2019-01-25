<?php
namespace davidhirtz\yii2\skeleton\models\queries;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\models\User;

/**
 * Class UserQuery.
 * @package davidhirtz\yii2\skeleton\models\queries
 * @see UserQuery
 *
 * @method User[] all($db=null)
 * @method User one($db=null)
 */
class UserQuery extends ActiveQuery
{
	/**
	 * @return $this
	 */
	public function identityAttributesOnly()
	{
		return $this->select(['id', 'status', 'name', 'email', 'gender', 'first_name', 'last_name', 'language', 'timezone', 'email_confirmation_code', 'is_owner', 'login_count', 'last_login', 'updated_at']);
	}

	/**
	 * @return $this
	 */
	public function loginAttributesOnly()
	{
		return $this->select(['id', 'status', 'name', 'password', 'password_salt', 'timezone', 'language', 'email_confirmation_code', 'is_owner', 'login_count', 'last_login']);
	}

	/**
	 * @return $this
	 */
	public function nameAttributesOnly()
	{
		return $this->select(['id', 'status', 'name', 'first_name', 'last_name', 'is_owner']);
	}

	/**
	 * @return $this
	 */
	public function listAttributesOnly()
	{
		return $this->select(['id', 'status', 'name', 'email', 'first_name', 'last_name', 'email_confirmation_code', 'is_owner', 'last_login', 'created_at']);
	}

	/**
	 * @return $this
	 */
	public function enabled()
	{
		return $this->andWhere([User::tableName().'.[[status]]'=>User::STATUS_ENABLED]);
	}

	/**
	 * @param string $search
	 * @return $this
	 */
	public function matching($search)
	{
		if($keywords=$this->splitSearchString($search))
		{
			$tableName=User::tableName();

			if(count($keywords)==1)
			{
				$keyword=array_pop($keywords);

				if(is_numeric($keyword))
				{
					$this->andWhere("{$tableName}.[[id]]=:id", ['id'=>$keyword]);
				}
				else
				{
					if(strpos($keyword, '@')!==false)
					{
						$this->andWhere("{$tableName}.[[email]] LIKE :search", ['search'=>"%{$keyword}%"]);
					}
					else
					{
						$this->andWhere("{$tableName}.[[name]] LIKE :search OR {$tableName}.[[email]] LIKE :search OR {$tableName}.[[first_name]] LIKE :search OR {$tableName}.[[last_name]] LIKE :search", ['search'=>"{$keyword}%"]);
					}
				}
			}
			else
			{
				$this->andWhere("{$tableName}.[[first_name]] LIKE :name OR {$tableName}.[[last_name]] LIKE :name OR ({$tableName}.[[first_name]] LIKE :firstname AND {$tableName}.[[last_name]] LIKE :lastname) OR ({$tableName}.[[first_name]] LIKE :lastname AND {$tableName}.[[last_name]] LIKE :firstname)", [
					'name'=>implode(' ', $keywords).'%',
					'firstname'=>array_pop($keywords).'%',
					'lastname'=>implode(' ', $keywords).'%',
				]);
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function orderByName()
	{
		return $this->orderBy(['name'=>SORT_ASC]);
	}
}