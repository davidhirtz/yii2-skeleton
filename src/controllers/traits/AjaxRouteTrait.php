<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\controllers\traits;

use davidhirtz\yii2\skeleton\web\View;
use Yii;

trait AjaxRouteTrait
{
    public function renderContent($content): string
    {
        return Yii::$app->getRequest()->getIsAjaxRoute()
            ? $this->renderAjaxRouteContent($content)
            : parent::renderContent($content);
    }

    public function renderAjaxRouteContent(string $content): string
    {
        Yii::$app->getResponse()->getHeaders()
            ->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->set('Pragma', 'no-cache');

        return $content
            . $this->renderAjaxRouteCss()
            . $this->renderAjaxRouteScripts();
    }

    protected function renderAjaxRouteCss(): string
    {
        $view = $this->getView();
        return implode('', $view->cssFiles) . implode('', $view->css);
    }

    protected function renderAjaxRouteScripts(): string
    {
        $view = $this->getView();
        $this->registerAjaxRouteDocumentTitle();

        $html = '';
        $modules = [];
        $js = [];

        foreach ($view->jsFiles as $jsFiles) {
            $html .= implode('', $jsFiles);
        }

        foreach ($view->js as $position => $scripts) {
            foreach ($scripts as $script) {
                $script = rtrim((string)$script, ';');

                if ($position == View::POS_MODULE) {
                    $modules[] = $script;
                } else {
                    $js[] = $script;
                }
            }
        }

        $modules = implode(';', $modules);
        $js = implode(';', $js);

        if ($modules) {
            $html .= "<script type='module'>$modules</script>";
        }

        if ($js) {
            $html .= "<script>$js</script>";
        }

        return $html;
    }

    protected function registerAjaxRouteDocumentTitle(): void
    {
        $view = $this->getView();

        $title = addslashes((string)preg_replace("/[\r|\n]/", '', $view->getDocumentTitle()));
        $view->registerJs("document.title=\"$title\";", $view::POS_END);
    }
}
