<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;
use Yii;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Class LanguageDropDownList.
 * @package davidhirtz\yii2\skeleton\widgets\form
 *
 * @property array $languages
 * @see LanguageDropdown::getLanguages()
 */
class LanguageDropdown extends InputWidget
{
    use WidgetConfigTrait;

    /**
     * @var bool
     */
    public $hideOnEmpty = true;

    /**
     * @var array
     */
    public $options = ['class' => 'form-control'];

    /**
     * @see LanguageDropdown::getLanguages()
     * @var array
     */
    private $_languages;

    /**
     * Displays language drop down.
     * @return string
     */
    public function run()
    {
        $languages = $this->getLanguages();

        if (count($languages) < 2 && $this->hideOnEmpty) {
            return null;
        }

        return $this->hasModel() ? Html::activeDropDownList($this->model, $this->attribute, $languages, $this->options) : Html::dropDownList($this->name, $this->value, $languages, $this->options);
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        if ($this->_languages === null) {

            $i18n = Yii::$app->getI18n();

            foreach ($i18n->languages as $language) {
                $this->_languages[$language] = $i18n->getLabel($language);
            }

            asort($this->_languages);
        }

        return $this->_languages;
    }

    /**
     * @param array $languages
     */
    public function setLanguages($languages)
    {
        $this->_languages = $languages;
    }
}