<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\base\traits\ContainerTrait;
use davidhirtz\yii2\skeleton\web\View;
use Deprecated;
use Stringable;
use Yii;
use yii\base\BaseObject;
use yii\base\ViewContextInterface;

/**
 * @property View $view
 */
abstract class Widget extends BaseObject implements Stringable, ViewContextInterface
{
    use ContainerTrait;

    protected View $view;
    protected ?string $viewPath = null;

    public function __construct($config = [])
    {
        $this->view = Yii::$app->getView();
        parent::__construct($config);
    }


    public function getViewPath(): ?string
    {
        if ($this->viewPath === null) {
            $controllerId = Yii::$app->controller->id;
            $this->setViewPath("@views/$controllerId/");
        }

        return $this->viewPath;
    }

    #[Deprecated]
    public static function widget(array $config = []): string
    {
        return Yii::$container->get(static::class, [], $config)->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    abstract public function render(): string;
}
