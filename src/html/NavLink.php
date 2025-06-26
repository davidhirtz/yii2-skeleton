<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use Yii;
use yii\web\Controller;

class NavLink extends Link
{
    use TagVisibilityTrait;

    protected array $attributes = [
        'class' => 'nav-link',
    ];

    public function active(bool|callable $active): static
    {
        if (is_callable($active)) {
            $active = call_user_func($active);
        }

        if ($active) {
            $this->addClass('active');
        }

        return $this;
    }

    public function label(string $text): static
    {
        $this->addHtml('<span class="d-none d-lg-block">' . Html::encode($text) . '</span>');
        return $this;
    }

    public function routes(array $routes): static
    {
        $request = Yii::$app->getRequest();

        if (Yii::$app->controller instanceof Controller) {
            foreach ($routes as $route => $params) {
                if (is_int($route)) {
                    $route = is_array($params) ? array_shift($params) : $params;
                }

                $shouldSkip = ($route[0] == '!');

                if ($shouldSkip) {
                    $route = substr((string)$route, 1);
                }

                if (preg_match("~$route~", Yii::$app->controller->route)) {
                    if (is_array($params)) {
                        foreach ($params as $key => $value) {
                            if ((is_int($key) && !in_array($value, array_keys($request->get())))
                                || (is_string($key) && $request->get($key) != $value)) {
                                continue 2;
                            }
                        }
                    }

                    if ($shouldSkip) {
                        break;
                    }

                    return $this->active(true);
                }
            }
        }

        return $this->active(false);
    }

    protected function prepareAttributes(): void
    {
        $url = $this->attributes['href'] ?? null;

        if (Yii::$app->getRequest()->url === $url) {
            $this->addClass('active');
        }

        parent::prepareAttributes();
    }

    #[\Override]
    protected function getName(): string
    {
        return array_key_exists('href', $this->attributes) ? 'a' : 'button';
    }
}
