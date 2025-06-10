<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

class ActiveField extends \yii\bootstrap5\ActiveField
{
    protected function addAriaAttributes(&$options): void
    {
        parent::addAriaAttributes($options);

        if (!empty($options['aria-required'])) {
            $options['required'] ??= true;
        }
    }
}
