<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use davidhirtz\yii2\skeleton\html\Icon;

class ActiveField extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField
{
    public ?string $icon = null;

    public array $iconOptions = ['class' => 'fa-fw'];
    public string $iconInputTemplate = '<div class="input-group"><div class="input-group-prepend input-group-text">{icon}</div>{input}</div>';

    public $enableClientValidation = false;
    public $options = ['class' => 'form-group'];
    public $enableError = false;

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
