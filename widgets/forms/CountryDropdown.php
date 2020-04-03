<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Class CountryDropDownList
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
class CountryDropdown extends InputWidget
{
    /**
     * @var bool
     */
    public $allowEmpty = true;

    /**
     * @var bool
     */
    public $countryCodes = true;

    /**
     * @var bool
     */
    public $lowerCaseCodes = false;

    /**
     * @var array
     */
    public $options = ['class' => 'form-control'];

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if ($this->lowerCaseCodes && !$this->countryCodes) {
            throw new InvalidConfigException;
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        if ($this->allowEmpty && !isset($this->options['prompt'])) {
            $this->options['prompt'] = '';
        }

        $countries = $this->getCountries();
        return $this->hasModel() ? Html::activeDropDownList($this->model, $this->attribute, $countries, $this->options) : Html::dropDownList($this->name, $this->value, $countries, $this->options);
    }

    /**
     * @return array
     */
    public function getCountries(): array
    {
        $countries = require(Yii::getAlias('@skeleton/messages/') . Yii::$app->language . '/countries.php');
        return $this->countryCodes ? ($this->lowerCaseCodes ? array_change_key_case($countries) : $countries) : array_combine($countries, $countries);
    }
}