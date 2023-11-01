<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use Yii;

class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
    public ?string $cssClass = null;

    public $itemTemplate = "<li class=\"breadcrumb-item\">{link}</li>\n";
    public $activeItemTemplate = "<li class=\"breadcrumb-item active\">{link}</li>\n";

    public function init(): void
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
                $this->links = [['label' => $module->name ?: Yii::t('skeleton', 'Admin'), 'url' => ['/admin/dashboard/index']], ...$this->links];
            }
        }

        if ($this->cssClass) {
            Html::addCssClass($this->options, $this->cssClass);
        }

        parent::init();
    }
}