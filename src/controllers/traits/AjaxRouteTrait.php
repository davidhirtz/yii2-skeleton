<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\controllers\traits;

use Hirtz\Skeleton\web\View;
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
        $js = [];

        foreach ($view->jsFiles as $jsFiles) {
            $html .= implode('', $jsFiles);
        }

        $html .= $view->renderJsModules();
        unset($view->js[View::POS_IMPORT], $view->js[View::POS_MODULE]);

        foreach ($view->js as $scripts) {
            foreach ($scripts as $script) {
                $script = rtrim((string)$script, ';');
                $js[] = $script;
            }
        }

        $js = implode(';', $js);

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
