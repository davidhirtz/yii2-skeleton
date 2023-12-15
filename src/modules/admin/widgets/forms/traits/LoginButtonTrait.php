<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

trait LoginButtonTrait
{
    public function loginButton(): string
    {
        $button = Html::submitButton(Yii::t('skeleton', 'Login'), [
            'class' => 'btn btn-primary btn-block',
        ]);

        return Html::tag('div', $button, [
            'class' => 'form-group',
        ]);
    }
}
