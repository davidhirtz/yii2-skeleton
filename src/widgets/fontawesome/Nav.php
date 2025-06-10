<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\web\View;
use Yii;
use yii\helpers\Html;

/**
 * @method View getView()
 */
class Nav extends \yii\bootstrap5\Nav
{
    /**
     * @var bool whether the widget should not be rendered if there is only a single item present.
     */
    public bool $hideOneItem = true;

    /**
     * @var string default item template can be set individually by item option `template`.
     */
    public string $itemTemplate = '<div class="icon-text">{icon}{label}{badge}</div>';

    /**
     * @var array default link HTML options, can be set individually by item options `linkOptions`.
     */
    public array $linkOptions = [];

    /**
     * @var array default icon HTML options, can be set individually by item option `iconOptions`.
     */
    public array $iconOptions = ['class' => 'fa-fw'];

    /**
     * @var array default badge HTML options, can be set individually by item option `badgeOptions`.
     */
    public array $badgeOptions = ['class' => 'badge'];

    /**
     * @var array default label HTML options can be set individually by item option `labelOptions`.
     */
    public array $labelOptions = [];

    private bool $_hasActiveItem = false;

    /**
     * Overrides default implementation for `roles` option to validate user access. And allows for the option to hide
     * nav, if only a single item is set.
     */
    public function renderItems(): string
    {
        $items = [];

        foreach ($this->items as $item) {
            if (is_string($item)) {
                $items[] = Html::tag('li', $item);
                continue;
            }

            if ($roles = ArrayHelper::remove($item, 'roles')) {
                $hasAccess = false;

                foreach ((array)$roles as $role) {
                    if ($role === '*' || Yii::$app->getUser()->can($role)) {
                        $hasAccess = true;
                        break;
                    }
                }

                if (!$hasAccess) {
                    continue;
                }
            }

            if (!($item['visible'] ?? true)) {
                continue;
            }

            $items[] = $this->renderItem($item);
        }

        return $items && (!$this->hideOneItem || count($items) > 1)
            ? Html::tag('ul', implode('', $items), $this->options) :
            '';
    }

    /**
     * Allows the addition of Font Awesome icons to nav label and
     * wraps label in additional span tag.
     *
     * Changed item options:
     *
     * - active: boolean|array|callable allows a multiple array that is checked against controller and module
     * - badge: string, optional, adds badge to item label
     * - badgeOptions: array, optional, additional html options for badge tag.
     * - icon: string, optional, the Font Awesome icon name.
     * - iconOptions: array, optional, additional html options for icon tag.
     * - items: array|callable allows submenu items to be callable
     * - label: string, optional, if icon is set, required if icon is empty.
     * - labelOptions: array, optional, additional html options for label tag
     * - roles: array {@see Nav::renderItems()}
     * - template: string, optional, use format "{icon}{label}" to change label template.
     *
     * @inheritdoc
     */
    public function renderItem($item): string
    {
        if ($this->linkOptions) {
            $item['linkOptions'] ??= $this->linkOptions;
        }

        // Icon & badge.
        $icon = $item['icon'] ?? false;
        $badge = $item['badge'] ?? false;

        if ($icon || $badge) {
            $label = $item['label'] ?? '';
            $iconOptions = $item['iconOptions'] ?? $this->iconOptions;
            $badgeOptions = $item['badgeOptions'] ?? $this->badgeOptions;
            $template = $item['template'] ?? $this->itemTemplate;

            // Only encode label.
            if ($item['encode'] ?? $this->encodeLabels) {
                $label = Html::encode($label);
                $item['encode'] = false;
            }

            $item['label'] = strtr($template, [
                '{icon}' => $icon ? Icon::tag($icon, $iconOptions)->render() : '',
                '{badge}' => $badge !== false ? Html::tag('span', $badge, $badgeOptions) : '',
                '{label}' => $label ? Html::tag('span', $label, $item['labelOptions'] ?? $this->labelOptions) : '',
            ]);
        }

        if ($items = $item['items'] ?? []) {
            if (is_callable($items)) {
                $item['items'] = call_user_func($items) ?: null;
            }
        }

        return parent::renderItem($item);
    }

    /**
     * Extends the default behavior to allow for a callable `active` option and to check against multiple routes, when
     * the `active` key is an array.
     *
     * If a given route is an array, the key represents the route and the value either GETS parameter names or name
     * value pairs that must match with the request. Routes can be reserved to prevent activating an item on a hit by
     * starting the route with "!".
     */
    protected function isItemActive($item): bool
    {
        if (!$this->activateItems || $this->_hasActiveItem) {
            return false;
        }

        if (isset($item['active'])) {
            if (is_callable($item['active'])) {
                $item['active'] = call_user_func($item['active']);
            }

            if (is_array($item['active'])) {
                $routes = $item['active'];
                $request = Yii::$app->getRequest();
                $item['active'] = false;

                foreach ($routes as $route => $params) {
                    if (is_int($route)) {
                        $route = is_array($params) ? array_shift($params) : $params;
                    }

                    $shouldSkip = ($route[0] == '!');

                    if ($shouldSkip) {
                        $route = substr((string)$route, 1);
                    }

                    if (preg_match("~$route~", (string)Yii::$app->controller->route)) {
                        if (is_array($params)) {
                            foreach ($params as $key => $value) {
                                if ((is_int($key) && !in_array($value, array_keys($request->get())))
                                    || (is_string($key) && $request->get($key) != $value)) {
                                    continue 2;
                                }
                            }
                        }

                        if ($shouldSkip) {
                            return false;
                        }

                        return $this->_hasActiveItem = true;
                    }
                }
            }

            if ($item['active']) {
                return $this->_hasActiveItem = true;
            }
        }

        if (isset($item['url'][0]) && is_array($item['url'])) {
            return $this->_hasActiveItem = $this->compareRoute($item['url']);
        }

        return false;
    }

    protected function compareRoute(array $params): bool
    {
        $route = $params[0];

        if (!str_starts_with($route, '/')) {
            $route = Yii::$app->controller->module->getUniqueId() . '/' . $route;
        }

        if (ltrim($route, '/') !== $this->route) {
            return false;
        }

        unset($params['#']);

        if (count($params) > 1) {
            unset($params[0]);

            foreach ($params as $name => $value) {
                if ($value !== null && (!isset($this->params[$name]) || $this->params[$name] != $value)) {
                    return false;
                }
            }
        }

        return true;
    }
}
