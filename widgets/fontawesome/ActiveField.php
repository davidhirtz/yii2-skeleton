<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

class ActiveField extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField
{
    /**
     * @var string|null
     */
    public ?string $icon = null;

    /**
     * @var array default icon html options
     */
    public array $iconOptions = ['class' => 'fa-fw'];

    /**
     * @var string
     */
    public string $iconInputTemplate = '<div class="input-group"><div class="input-group-prepend"><span class="input-group-text">{icon}</span></div>{input}</div>';

    /**
     * Wraps text field with an input group and adds font awesome icon.
     */
    public function init(): void
    {
        if ($this->icon) {
            $this->inputTemplate = strtr($this->iconInputTemplate, [
                '{icon}' => Icon::tag($this->icon, $this->iconOptions),
            ]);

            if (!isset($this->inputOptions['placeholder'])) {
                $this->inputOptions['placeholder'] = $this->model->getAttributeLabel($this->attribute);
            }

            $this->labelOptions['class'] = 'sr-only';
        }

        parent::init();
    }
}