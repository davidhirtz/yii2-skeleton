<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;
use Yii;

/**
 * Class Breadcrumbs.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
    use WidgetConfigTrait;

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

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');

        // Admin module breadcrumb.
        if (Yii::$app->controller->module instanceof Module || in_array(Yii::$app->controller->module, $module->getModules())) {

            if ($module->name !== false) {
                $this->links = array_merge([['label' => $module->name ?: Yii::t('skeleton', 'Admin'), 'url' => ['/admin/dashboard/index']]], $this->links);
            }
        }

        if ($this->cssClass) {
            Html::addCssClass($this->options, $this->cssClass);
        }

        parent::init();
    }
}