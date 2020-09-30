<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use yii\helpers\Html;

/**
 * Class Submenu
 * @package davidhirtz\yii2\skeleton\widgets\fontawesome
 */
class Submenu extends Nav
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var
     */
    public $cssClass = ['submenu', 'nav', 'nav-pills'];

    /**
     * Sets nav pills class.
     */
    public function init()
    {
        Html::addCssClass($this->options, $this->cssClass);
        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        $content = parent::run();
        return ($this->title ? Html::tag('h1', $this->title, ['class' => 'page-header']) : '') . ($content  ? Html::tag('nav', $content) : '');
    }
}