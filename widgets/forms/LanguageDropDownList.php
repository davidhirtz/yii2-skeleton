<?php
namespace davidhirtz\yii2\skeleton\forms;

use Yii;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Class LanguageDropDownList.
 * @package davidhirtz\yii2\skeleton\form
 *
 * @property array $languages
 * @see LanguageDropDownList::getLanguages()
 */
class LanguageDropDownList extends InputWidget
{
	/**
	 * @var array
	 */
	public $options=['class'=>'form-control'];

	/**
	 * @see LanguageDropDownList::getLanguages()
	 * @var array
	 */
	private $_languages;

	/**
	 * Displays language drop down.
	 * @return string
	 */
	public function run()
	{
		$languages=$this->getLanguages();
		asort($languages);

		if($this->hasModel())
		{
			echo Html::activeDropDownList($this->model, $this->attribute, $languages, $this->options);
		}
		else
		{
			echo Html::dropDownList($this->name, $this->value, $languages, $this->options);
		}
	}

	/**
	 * @return array
	 */
	public function getLanguages()
	{
		if($this->_languages===null)
		{
			foreach(Yii::$app->i18n->languages as $id)
			{
				$this->_languages[$id]=mb_strtoupper($id, Yii::$app->charset);
			}
		}

		return $this->_languages;
	}

	/**
	 * @param array $languages
	 */
	public function setLanguages($languages)
	{
		$this->_languages=$languages;
	}
}