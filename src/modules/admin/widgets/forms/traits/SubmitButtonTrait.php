<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

trait SubmitButtonTrait
{
    public function loginButton(): string
    {
        return $this->submitButton(Yii::t('skeleton', 'Login'));
    }

    public function submitButton(string $label, array $options = []): string
    {
        $wrapOptions = ArrayHelper::remove($options, 'wrapOptions', []);

        Html::addCssClass($options, ['btn', 'btn-primary', 'btn-block']);
        Html::addCssClass($wrapOptions, 'form-group');

        $button = Html::submitButton($label, $options);
        return Html::tag('div', $button, $wrapOptions);
    }
}
