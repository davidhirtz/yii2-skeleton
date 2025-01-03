<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\Widget;
use yii\bootstrap5\Html;

class Flashes extends Widget
{
    /**
     * @var array|null containing a message, leave empty for default implementation
     */
    public ?array $alerts = null;

    /**
     * @var array containing alert element HTML options
     */
    public array $options = [
        'class' => 'alert',
    ];

    /**
     * @var array containing wrapper element HTML options
     */
    public array $wrapperOptions = [
        'id' => 'flashes',
    ];

    /**
     * @var string the status CSS class prefix
     */
    public string $statusCssClass = 'alert-';

    public function init(): void
    {
        $this->alerts ??= Yii::$app->getSession()->getAllFlashes();

        parent::init();
    }

    public function run(): string
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

        $tag = ArrayHelper::remove($this->wrapperOptions, 'tag', 'div');
        return $this->wrapperOptions ? Html::tag($tag, $content, $this->wrapperOptions) : $content;
    }

    public function renderAlert(string $status, string $message): string
    {
        $options = $this->options;

        if ($status === 'error') {
            $status = 'danger';
        }

        $tag = ArrayHelper::remove($options, 'tag', 'div');
        Html::addCssClass($options, $this->statusCssClass . $status);

        return Html::tag($tag, $message, $options);
    }
}
