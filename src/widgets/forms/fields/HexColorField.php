<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\assets\HexColorInputAssetBundle;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\widgets\forms\InputGroup;
use Stringable;

class HexColorField extends Field
{
    public function getInput(): string|Stringable
    {
        $this->registerClientScript();
        return $this->getInputGroup();
    }

    protected function getInputGroup(): string|Stringable
    {
        $this->attributes['id'] ??= $this->getId();

        if ($this->attributes['value'] && !str_starts_with((string)$this->attributes['value'], '#')) {
            $this->attributes['value'] = "#{$this->attributes['value']}";
        }

        $hexValue = $this->attributes['value'] ?: '#000000';

        if (strlen((string)$hexValue) === 4) {
            $hexValue = '#' . $hexValue[1] . $hexValue[1] . $hexValue[2] . $hexValue[2] . $hexValue[3] . $hexValue[3];
        }

        return InputGroup::make()
            ->prepend(Input::make()
                ->attribute('id', "{$this->attributes['id']}-color")
                ->type('color')
                ->value($hexValue)
                ->required())
            ->content(Input::make()
                ->attributes($this->attributes)
                ->addClass('input'));
    }

    protected function registerClientScript(): void
    {
        $this->view->registerAssetBundle(HexColorInputAssetBundle::class);
    }
}
