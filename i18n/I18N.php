<?php
namespace davidhirtz\yii2\skeleton\i18n;
use Yii;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;

/**
 * Class I18N.
 * @package davidhirtz\yii2\skeleton\i18n
 *
 * @property array $languages
 * @see \davidhirtz\yii2\skeleton\i18n\I18N::getLanguages()
 */
class I18N extends \yii\i18n\I18N
{
	/**
	 * @var array
	 */
	public static $languageLabels=[
		'de'=>'Deutsch',
		'en-US'=>'English',
	];

	/**
	 * @var array
	 * @see getLanguages()
	 */
	private $_languages;

	/**
	 * @return array|null
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		if($this->_languages===null)
		{
			/**
			 * Set default language.
			 */
			$this->_languages=[Yii::$app->sourceLanguage];
			$source=$this->getMessageSource('app');

			/**
			 * Load languages from translations.
			 */
			if($source instanceof PhpMessageSource)
			{
				/** @noinspection PhpIncludeInspection */
				$config=require(Yii::getAlias($source->basePath).DIRECTORY_SEPARATOR.'config.php');
				$this->_languages=array_unique(array_merge($this->_languages, (array)ArrayHelper::getValue($config, 'languages')));
			}

			sort($this->_languages);
		}

		return $this->_languages;
	}

	/**
	 * @param array|string $languages
	 */
	public function setLanguages($languages)
	{
		$this->_languages=array_unique((array)$languages);
		sort($this->_languages);
	}

	/**
	 * @param string $language
	 * @return mixed
	 */
	public function getLabel($language)
	{
		return ArrayHelper::getValue(static::$languageLabels, $language);
	}

	/**
	 * @param string $attribute
	 * @param string $language
	 * @return string
	 */
	public static function getAttributeName($attribute, $language=null)
	{
		if(!$language)
		{
			$language=Yii::$app->language;
		}

		return rtrim($attribute.'_'.($language!=Yii::$app->sourceLanguage ? $language : ''), '_');
	}

	/**
	 * @param $attribute
	 * @return array
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getAttributeNames($attribute)
	{
		$names=[];

		foreach($this->getLanguages() as $language)
		{
			$names[]=static::getAttributeName($attribute, $language);
		}

		return $names;
	}

	/**
	 * @param string $tableName
	 * @param string $language
	 * @return string
	 */
	public static function getTableName($tableName, $language=null)
	{
		return '{{%'.static::getAttributeName($tableName, $language).'}}';
	}
}