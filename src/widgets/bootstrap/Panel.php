<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class Panel.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Panel extends Widget
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $type = 'default';

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var array
     */
    public $bodyOptions = [];

    /**
     * @var bool
     */
    public $isCollapsable = false;

    /**
     * @var bool
     */
    public $isCollapsed = true;

    /**
     * Sets default CSS class.
     */
    public function init()
    {
        if ($this->getId(false)) {
            $this->options['id'] = $this->getId();
        }

        Html::addCssClass($this->options, 'card card-' . $this->type);
        Html::addCssClass($this->bodyOptions, 'card-body');

        parent::init();

        if (!$this->content) {
            ob_start();
            ob_implicit_flush(false);
        }
    }

    /**
     * Wraps content in panel.
     */
    public function run()
    {
        if (!$this->content) {
            $this->content = ob_get_clean();
        }

        if ($this->content) {
            $collapseId = $this->getId() . '-body';
            echo Html::beginTag('div', $this->options);

            if ($this->title) {
                $title = $this->isCollapsable ? Html::a($this->title, "#{$collapseId}", ['data-toggle' => 'collapse']) : $this->title;
                echo Html::tag('div', Html::tag('h2', $title, ['class' => 'card-title']), ['class' => 'card-header']);
            }

            $body = Html::tag('div', $this->content, $this->bodyOptions);
            echo $this->isCollapsable ? Html::tag('div', $body, ['class' => $this->isCollapsed ? 'collapse' : 'collapse show', 'id' => $collapseId]) : $body;

            echo Html::endTag('div');
        }
    }
}