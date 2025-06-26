<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\helpers\Url;

/**
 * @deprecated
 */
class ButtonDropdown extends \yii\bootstrap5\ButtonDropdown
{
    /**
     * @var string|false|null the default item label, set to `false` to disable the default item
     */
    public string|false|null $defaultItem = null;

    /**
     * @var string|null the default item parameter value
     */
    public ?string $defaultValue = null;

    /**
     * @var string|null the parameter name
     */
    public ?string $paramName = null;

    /**
     * @var array containing items as an array with "label" and optional "url" keys
     */
    public array $items = [];
    /**
     * @var bool whether the filter text field should be added to the dropdown
     */
    public bool $showFilter = false;

    /**
     * @var string|null the filter text field placeholder text
     */
    public ?string $filterPlaceholder = null;

    /**
     * @var bool whether dropdown is active, if `null` the request will be checked for `paramName`
     */
    public ?bool $isActive = null;

    public $encodeLabel = false;

    #[\Override]
    public function init(): void
    {
        $this->defaultItem ??= Yii::t('skeleton', 'Show All');
        $this->isActive ??= !$this->defaultItem || ($this->paramName && Yii::$app->getRequest()->get($this->paramName) !== null);

        if ($this->items) {
            $this->dropdown['items'] = $this->items;
        }

        if ($this->showFilter) {
            $this->filterPlaceholder ??= Yii::t('skeleton', 'Filter');

            $label = Html::tag('input', '', [
                'class' => 'dropdown-filter form-control',
                'placeholder' => $this->filterPlaceholder,
            ]);

            array_unshift(
                $this->dropdown['items'],
                ['label' => $label, 'encode' => false],
                '-'
            );
        }

        if ($this->isActive) {
            if ($this->defaultItem !== false && isset($this->dropdown['items'])) {
                array_unshift(
                    $this->dropdown['items'],
                    [
                        'label' => $this->defaultItem,
                        'url' => Url::current([$this->paramName => $this->defaultValue]),
                    ],
                    '-'
                );
            }

            $currentUrl = Url::current();
            $isActive = false;

            foreach ($this->dropdown['items'] as $key => $item) {
                if ($currentUrl === ($item['url'] ?? null)) {
                    $this->dropdown['items'][$key]['active'] = true;
                    $isActive = true;
                }
            }

            if (!$isActive && $this->defaultItem === false) {
                $this->dropdown['items'][0]['active'] = true;
            }

            Html::addCssClass($this->buttonOptions, 'active');
        }

        parent::init();

        if ($this->showFilter) {
            // @todo
            $this->getView()->registerJs("$('#{$this->options['id']}').dropdownFilter();");
        }
    }

    #[\Override]
    protected function renderDropdown(): string
    {
        return Html::tag('dialog', str_replace('class="dropdown-menu"', '', parent::renderDropdown()), [
            'class' => 'dropdown-menu',
        ]);
    }

    #[\Override]
    public function run(): string
    {
        $content = parent::run();
        return str_replace('data-bs-toggle="dropdown"', 'data-dropdown', $content);
    }
}
