<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\bootstrap4\Html;

/**
 * Class Flashes.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Flashes extends \yii\base\Widget
{
    /**
     * @var array containing message, leave empty for default implementation
     */
    public $alerts;

    /**
     * @var array containing alert element HTML options
     */
    public $options = ['class' => 'alert'];

    /**
     * @var array containing wrapper element HTML options
     */
    public $wrapperOptions = [];

    /**
     * @var string the status css class prefix
     */
    public $statusCssClass = 'alert-';

    /**
     * Loads alerts.
     */
    public function init()
    {
        if ($this->alerts === null) {
            $this->alerts = Yii::$app->getSession()->getAllFlashes();
        }

        parent::init();
    }

    /**
     * Renders all alerts.
     * @return string
     */
    public function run()
    {
        $content = '';

        if ($this->alerts) {
            foreach ($this->alerts as $status => $alerts) {
                foreach ((array)$alerts as $alert) {
                    foreach ((array)$alert as $message) {
                        $content .= $this->renderAlert($status, $message);
                    }
                }
            }
        }

        if ($content) {
            $tag = ArrayHelper::remove($this->wrapperOptions, 'tag', 'div');
            return $this->wrapperOptions ? Html::tag($tag, $content, $this->wrapperOptions) : $content;
        }

        return '';
    }

    /**
     * Renders alert.
     *
     * @param string $status
     * @param string $message
     *
     * @return string
     */
    public function renderAlert($status, $message)
    {
        $tag = ArrayHelper::remove($this->options, 'tag', 'div');
        Html::addCssClass($this->options, $this->statusCssClass . $status);

        return Html::tag($tag, $message, $this->options);
    }
}