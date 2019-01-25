<?php
namespace davidhirtz\yii2\skeleton\db;
use davidhirtz\yii2\skeleton\i18n\I18N;
use Yii;

/**
 * Class ActiveQuery.
 * @package \davidhirtz\yii2\skeleton\db
 * @see ActiveRecord
 *
 * @method ActiveRecord[] all($db=null)
 * @method ActiveRecord one($db=null)
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
	/**
	 * @return $this
	 */
	public function replaceI18nAttributes()
	{
		if(is_array($this->select))
		{
			if($attributes=$this->getModelInstance()->i18nAttributes)
			{
				foreach($this->select as &$attribute)
				{
					if(in_array($attribute, $attributes))
					{
						$attribute=I18N::getAttributeName($attribute);
					}
				}
			}
		}

		return $this;
	}

	/**
	 * @param array $attributes
	 * @return $this
	 */
	public function whereLower($attributes)
	{
		foreach($attributes as $attribute=>$value)
		{
			$this->andWhere(["LOWER({$attribute})"=>mb_strtolower($value, Yii::$app->charset)]);
		}

		return $this;
	}

	/**
	 * @param string $search
	 * @return array
	 */
	public function splitSearchString($search)
	{
		return array_filter(preg_split('/[\s,]+/', $this->sanitizeSearchString($search)));
	}

	/**
	 * @param string $search
	 * @return string
	 */
	public function sanitizeSearchString($search)
	{
		return trim(strtr($search, ['%'=>'']));
	}

	/**
	 * @return ActiveRecord
	 */
	protected function getModelInstance()
	{
		/** @var ActiveRecord $model */
		$model=$this->modelClass;
		return $model::instance();
	}

}