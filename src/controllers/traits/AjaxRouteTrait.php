<?php

namespace davidhirtz\yii2\skeleton\controllers\traits;

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
        return implode('', $this->getView()->css);
    }

    protected function renderAjaxRouteScripts(): string
    {
        $this->registerAjaxRouteDocumentTitle();

        $js = [];

        foreach ($this->getView()->js as $jsArray) {
            foreach ($jsArray as $jsString) {
                $js[] = rtrim((string)$jsString, ';');
            }
        }

        $js = implode(';', $js);

        return $js ? "<script>$js</script>" : '';
    }

    protected function registerAjaxRouteDocumentTitle(): void
    {
        $view = $this->getView();

        $title = addslashes((string) preg_replace("/[\r|\n]/", '', $view->getDocumentTitle()));
        $view->registerJs("document.title=\"$title\";", $view::POS_END);
    }
}
