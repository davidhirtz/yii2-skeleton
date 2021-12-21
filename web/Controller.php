<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\base\Model;
use yii\helpers\Html;

/**
 * Class Controller.
 * @package davidhirtz\yii2\skeleton\web
 *
 * @method View getView()
 */
class Controller extends \yii\web\Controller
{
    /**
     * @var bool
     */
    public $spacelessOutput = false;

    /**
     * Omits layout and asset rendering for AJAX route requests.
     *
     * Remove spaces for output, this is not recommended for performance, but is currently
     * a criteria for Google PageSpeed. IMPORTANT: This also removes white-spaces in text
     * areas and <pre> tags.
     *
     * @param string $content
     * @return string
     */
    public function renderContent($content)
    {
        $content = Yii::$app->getRequest()->getIsAjaxRoute() ? $this->renderAjaxRouteContent($content) : parent::renderContent($content);
        return $this->spacelessOutput ? trim(preg_replace('/>\s+</', '><', $content)) : $content;
    }

    /**
     * Renders AJAX route requests.
     * @param string $content
     * @return string
     */
    public function renderAjaxRouteContent($content)
    {
        Yii::$app->getResponse()->getHeaders()
            ->set('Cache-Control', ['no-store, no-cache, must-revalidate, max-age=0', 'post-check=0, pre-check=0'])
            ->set('Pragma', 'no-cache');

        return $content . $this->renderAjaxRouteScripts();
    }

    /**
     * Adds inline CSS and JS for AJAX route requests.
     * @return string
     */
    public function renderAjaxRouteScripts()
    {
        $view = $this->getView();
        $view->registerJs('document.title="' . addslashes(preg_replace("/[\r|\n]/", "", $view->getTitle())) . '";');

        return implode('', $view->css) . Html::script(implode('', call_user_func_array('array_merge', $view->js)), ['type' => 'text/javascript']);
    }

    /**
     * Shorthand method for returning JSON data.
     * @param array $data
     */
    public function setJsonResponseData($data = [])
    {
        $response = Yii::$app->getResponse();
        $response->format = $response::FORMAT_JSON;

        if ($data) {
            $response->data = is_array($response->data) ? array_merge($response->data, $data) : $data;
        }
    }

    /**
     * Shorthand method for adding an error flash. If `$value` is an instance of {@link Model} the first errors will be
     * set if found.
     *
     * @param Model|array|string $value
     */
    public function error($value)
    {
        if ($value instanceof Model) {
            $value = $value->getFirstErrors();
        }

        if ($value) {
            Yii::$app->getSession()->addFlash('error', $value);
        }
    }

    /**
     * Shorthand method for adding a success flash.
     * @param array|string $value
     */
    public function success($value)
    {
        if ($value) {
            Yii::$app->getSession()->addFlash('success', $value);
        }
    }
}