<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\web\View;
use Yii;

class Breadcrumbs extends \yii\widgets\Breadcrumbs
{
    public $itemTemplate = "<li class=\"breadcrumb-item\">{link}</li>\n";
    public $activeItemTemplate = "<li class=\"breadcrumb-item active\">{link}</li>\n";

    public function init(): void
    {
        $this->homeLink ??= [
            'label' => Yii::$app->name,
            'url' => Yii::$app->getHomeUrl(),
        ];

        if (!$this->links) {
            $view = $this->getView();
            $this->links = $view instanceof View ? $view->getBreadcrumbs() : [];
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');

        // Admin module breadcrumb.
        if (Yii::$app->controller->module instanceof Module || in_array(Yii::$app->controller->module, $module->getModules())) {
            if ($module->showInBreadcrumbs) {
                $this->links = [
                    [
                        'label' => $module->getName(),
                        'url' => [$module->defaultRoute],
                    ],
                    ...$this->links
                ];
            }
        }

        parent::init();
    }
}
