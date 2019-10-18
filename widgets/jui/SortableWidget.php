<?php

namespace davidhirtz\yii2\skeleton\widgets\jui;

use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;
use yii\base\Widget;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * Class SortableWidget.
 * @package davidhirtz\yii2\skeleton\widgets\grid
 */
class SortableWidget extends Widget
{
    use JuiWidgetTrait, WidgetConfigTrait;

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
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        if ($this->cloneHelperWidth) {
            $this->clientOptions['helper'] = new JsExpression('Skeleton.sortableHelper');
        }

        if ($this->clonePlaceholderContent) {
            $this->clientOptions['start'] = new JsExpression('function(event, ui){ui.placeholder.html(ui.item.html()).addClass("panel-placeholder");}');
        }

        if ($this->ajaxUpdateRoute) {
            $this->clientOptions['update'] = new JsExpression('function(){$.post("' . Url::to($this->ajaxUpdateRoute) . '",$(this).sortable(\'serialize\'))}');
        }

        parent::init();
    }

    /**
     * @return string|void
     */
    public function run()
    {
        $this->registerWidget('sortable');
    }
}