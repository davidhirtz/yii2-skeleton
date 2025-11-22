<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use Stringable;

/**
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    #[\Override]
    protected function renderContent(): string|Stringable
    {
        $this->rows ??= [
            'type',
            'request_uri',
            'url',
        ];

        return parent::renderContent();
    }
}
