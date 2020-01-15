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
     * Sets default CSS class.
     */
    public function init()
    {
        if ($this->getId(false)) {
            $this->options['id'] = $this->getId();
        }

        Html::addCssClass($this->options, 'card card-' . $this->type);
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
            echo Html::beginTag('div', $this->options);

            if ($this->title) {
                echo Html::tag('div', Html::tag('h2', $this->title, ['class' => 'card-title']), ['class' => 'card-header']);
            }

            echo Html::tag('div', $this->content, ['class' => 'card-body']);
            echo Html::endTag('div');
        }
    }
}