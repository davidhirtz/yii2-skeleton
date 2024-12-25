<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

class ActiveField extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField
{
    public ?string $icon = null;

    public array $iconOptions = ['class' => 'fa-fw'];
    public string $iconInputTemplate = '<div class="input-group"><div class="input-group-prepend input-group-text">{icon}</div>{input}</div>';

    public $options = ['class' => 'form-group'];

    public function init(): void
    {
        if ($this->icon) {
            $this->inputTemplate = strtr($this->iconInputTemplate, [
                '{icon}' => Icon::tag($this->icon, $this->iconOptions),
            ]);

            $this->inputOptions['placeholder'] ??= $this->model->getAttributeLabel($this->attribute);
            $this->labelOptions['class'] = 'sr-only';
        }

        parent::init();
    }
}
