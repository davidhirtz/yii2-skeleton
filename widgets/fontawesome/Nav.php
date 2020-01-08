<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Html;
use Yii;

/**
 * Class Nav.
 * @package davidhirtz\yii2\skeleton\widgets\fontawesome
 *
 * @method View getView()
 */
class Nav extends \yii\bootstrap4\Nav
{
    /**
     * @var string default item template, can be set individually by item option "template".
     */
    public $itemTemplate = '{icon} {label} {badge}';

    /**
     * @var array default link html options, can be set individually by item options "linkOptions".
     */
    public $linkOptions = [];

    /**
     * @var array default icon html options, can be set individually by item option "iconOptions".
     */
    public $iconOptions = ['class' => 'fa-fw'];

    /**
     * @var array default badge html options, can be set individually by item option "badgeOptions".
     */
    public $badgeOptions = ['class' => 'badge'];

    /**
     * @var array default label html options, can be set individually by item option "labelOptions".
     */
    public $labelOptions = [];

    /**
     * @var bool
     */
    private $isActive = false;

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
     * - template: string, optional, use format "{icon}{label}" to change label template.
     *
     * @inheritdoc
     */
    public function renderItem($item)
    {
        if ($this->linkOptions) {
            $item['linkOptions'] = $item['linkOptions'] ?? $this->linkOptions;
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
            if ($items instanceof \Closure) {
                $item['items'] = call_user_func($items) ?: null;
            }
        }

        // If active is an array, treat its' elements as routes and check them  against the current controller route.
        // If the route itself is an array, the key represents the route and the value either GET parameter names or
        // name value pairs that must match with the request.
        if (is_array($routes = $item['active'] ?? false)) {
            $request = Yii::$app->getRequest();
            $item['active'] = false;

            if (!$this->isActive) {
                foreach ($routes as $route => $params) {
                    if (is_int($route)) {
                        $route = $params;
                    }

                    if (preg_match("~{$route}~", Yii::$app->controller->route, $matches)) {
                        if (is_array($params)) {
                            foreach ($params as $key => $value) {
                                if ((is_int($key) && !in_array($value, array_keys($request->get()))) || (is_string($key) && $request->get($key) != $value)) {
                                    continue 2;
                                }
                            }
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