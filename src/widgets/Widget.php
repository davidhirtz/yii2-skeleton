<?php

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\web\View;
use Yii;
use yii\base\BaseObject;

/**
 * @property View $view
 */
abstract class Widget extends BaseObject
{
    /**
     * @uses setView()
     */
    private ?View $_view = null;

    public function run(): string
    {
        return '';
    }

    public function getView(): View
    {
        $this->_view ??= Yii::$app->controller->getView();
        return $this->_view;
    }

    public function setView(View $view): void
    {
        $this->_view = $view;
    }

    public static function widget(array $config = []): string
    {
        return Yii::$container->get(static::class, [], $config)->run();
    }
}