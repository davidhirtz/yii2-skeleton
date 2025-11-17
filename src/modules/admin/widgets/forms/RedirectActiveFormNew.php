<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveFieldNew;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use Override;
use Stringable;

/**
 * @property Redirect $model
 */
class RedirectActiveFormNew extends ActiveForm
{
    protected function renderContent(): string|Stringable
    {
        $this->fields ??= [
//            ActiveFieldNew::make()
//                ->property('type'),
            'request_uri',
            'url',
        ];

        return parent::renderContent();
    }
}
