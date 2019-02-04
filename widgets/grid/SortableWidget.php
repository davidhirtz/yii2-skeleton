<?php

namespace davidhirtz\yii2\skeleton\widgets\grid;

use yii\helpers\Url;
use yii\jui\Widget;
use yii\web\JsExpression;

/**
 * Class SortableWidget.
 * @package davidhirtz\yii2\skeleton\widgets\grid
 */
class SortableWidget extends Widget
{
    /**
     * @var bool
     */
    public $cloneHelperWidth = false;

    /**
     * @var bool
     */
    public $clonePlaceholderContent = false;

    /**
     * @var string
     */
    public $ajaxUpdateRoute = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $view = $this->getView();

        if ($this->cloneHelperWidth) {
            $view->registerJs('jUiSortableHelper=function(e,t){var o=t.children(),h=t.clone();h.children().each(function(index){$(this).width(o.eq(index).outerWidth());});return h;}', $view::POS_READY, __CLASS__ . 'helper');
            $this->clientOptions['helper'] = new JsExpression('jUiSortableHelper');
        }

        if ($this->clonePlaceholderContent) {
            $this->clientOptions['start'] = new JsExpression('function(event, ui){ui.placeholder.html(ui.item.html()).addClass("panel-placeholder");}');
        }

        if ($this->ajaxUpdateRoute) {
            $this->clientOptions['update'] = new JsExpression('function(e,u){var d=$(this).sortable("serialize");$.ajax({data:d,type:"POST",url:"' . Url::to($this->ajaxUpdateRoute) . '"});}');
        }

        parent::init();
    }

    public function run()
    {
        $this->registerWidget('sortable');
    }
}