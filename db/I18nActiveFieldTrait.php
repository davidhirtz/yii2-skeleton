<?php
namespace davidhirtz\yii2\skeleton\db;
use davidhirtz\yii2\skeleton\i18n\I18N;
use Yii;
use yii\widgets\ActiveField;

/**
 * Class I18nActiveFieldTrait.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property ActiveRecord $model
 */
trait I18nActiveFieldTrait
{
	/**
	 * @param ActiveField $field
	 * @param null $except
	 * @return ActiveField[]
	 */
	public function getI18nAttributeFields($field, $except=null)
	{
		$fields=[$field];

		if(in_array($field->attribute, $this->model->i18nAttributes))
		{
			if($except===null)
			{
				$except=[Yii::$app->sourceLanguage];
			}

			foreach(Yii::$app->getI18n()->getLanguages() as $language)
			{
				if(!in_array($language, $except))
				{
					$fields[]=$clone=clone $field;
					$clone->attribute=I18N::getAttributeName($field->attribute, $language);
				}
			}
		}

		return $fields;
	}
}