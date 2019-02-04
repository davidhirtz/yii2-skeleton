<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class Breadcrumbs.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
    /**
     * @var string
     */
    public $cssClass;

    /**
     * @var string
     */
    public $itemTemplate = "<li class=\"breadcrumb-item\">{link}</li>\n";

    /**
     * @var string
     */
    public $activeItemTemplate = "<li class=\"breadcrumb-item active\">{link}</li>\n";

    /**
     * Init.
     */
    public function init()
    {
        if ($this->homeLink === null) {
            $this->homeLink = [
                'label' => Yii::$app->name,
                'url' => Yii::$app->getHomeUrl(),
            ];
        }

        if ($this->cssClass) {
            Html::addCssClass($this->options, $this->cssClass);
        }

        parent::init();
    }
}