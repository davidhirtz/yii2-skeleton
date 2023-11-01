<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use yii\helpers\Html;

class Submenu extends Nav
{
    public ?string $title = null;

    public array $cssClass = ['submenu', 'nav', 'nav-pills'];

    public function init(): void
    {
        Html::addCssClass($this->options, $this->cssClass);
        parent::init();
    }

    public function run(): string
    {
        $content = parent::run();
        return ($this->title ? Html::tag('h1', $this->title, ['class' => 'page-header']) : '') . ($content  ? Html::tag('nav', $content) : '');
    }
}