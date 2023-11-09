<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\web\View;
use Yii;

class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
    public ?string $cssClass = 'd-none d-md-flex';

    public $itemTemplate = "<li class=\"breadcrumb-item\">{link}</li>\n";
    public $activeItemTemplate = "<li class=\"breadcrumb-item active\">{link}</li>\n";

    public $links = null;

    public function init(): void
    {
        $this->homeLink ??= [
            'label' => Yii::$app->name,
            'url' => Yii::$app->getHomeUrl(),
        ];

        if (!$this->links) {
            $view = $this->getView();
            $this->links ??= $view instanceof View ? $view->getBreadcrumbs() : [];
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
