<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use Closure;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Html;
use Yii;

/**
 * @method View getView()
 */
class Nav extends \yii\bootstrap4\Nav
{
    /**
     * @var bool whether the widget should not be rendered if there is only a single item present.
     */
    public bool $hideOneItem = true;

    /**
     * @var string default item template can be set individually by item option `template`.
     */
    public string $itemTemplate = '{icon} {label} {badge}';

    /**
     * @var array default link html options, can be set individually by item options `linkOptions`.
     */
    public array $linkOptions = [];

    /**
     * @var array default icon html options, can be set individually by item option `iconOptions`.
     */
    public array $iconOptions = ['class' => 'fa-fw'];

    /**
     * @var array default badge html options, can be set individually by item option `badgeOptions`.
     */
    public array $badgeOptions = ['class' => 'badge'];

    /**
     * @var array default label html options can be set individually by item option `labelOptions`.
     */
    public array $labelOptions = [];

    private bool $isActive = false;

    /**
     * Overrides default implementation for `roles` option to validate user access. And allows for the option to hide
     * nav, if only a single item is set.
     *
     * @return string
     */
    public function renderItems(): string
    {
        $items = [];

        foreach ($this->items as $item) {
            if ($roles = ArrayHelper::remove($item, 'roles')) {
                $hasAccess = false;

                foreach ((array)$roles as $role) {
                    if (Yii::$app->getUser()->can($role)) {
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

        return !$this->hideOneItem || count($items) > 1 ? Html::tag('ul', implode('', $items), $this->options) : '';
    }

    /**
     * Allows the addition of Font Awesome icons to nav label and
     * wraps label in additional span tag.
     *
     * Changed item options:
     *
     * - active: boolean|array allows a multiple array that is checked against controller and module
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
                '{icon}' => $icon ? Icon::tag($icon, $iconOptions) : '',
                '{badge}' => $badge !== false ? Html::tag('span', $badge, $badgeOptions) : '',
                '{label}' => $label ? Html::tag('span', $label, $item['labelOptions'] ?? $this->labelOptions) : '',
            ]);
        }

        if ($items = $item['items'] ?? []) {
            if ($items instanceof Closure) {
                $item['items'] = call_user_func($items) ?: null;
            }
        }

        // If active is an array, treat the elements as routes and check them against the current controller route.
        // If the route itself is an array, the key represents the route and the value either GETS parameter names or
        // name value pairs that must match with the request. Routes can be reserved to prevent activating an item on
        // a hit by starting the route with "!".
        if (is_array($routes = $item['active'] ?? false)) {
            $request = Yii::$app->getRequest();
            $item['active'] = false;

            if (!$this->isActive) {
                foreach ($routes as $route => $params) {
                    if (is_int($route)) {
                        $route = $params;
                    }

                    if ($shouldSkip = ($route[0] == '!')) {
                        $route = substr((string) $route, 1);
                    }

                    if (preg_match("~$route~", (string) Yii::$app->controller->route)) {
                        if (is_array($params)) {
                            foreach ($params as $key => $value) {
                                if ((is_int($key) && !in_array($value, array_keys($request->get()))) || (is_string($key) && $request->get($key) != $value)) {
                                    continue 2;
                                }
                            }
                        }

                        if ($shouldSkip) {
                            break;
                        }

                        $this->isActive = $item['active'] = true;
                        break;
                    }
                }
            }
        }

        return parent::renderItem($item);
    }
}