<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\rows\FormRow;
use Override;
use Stringable;

/**
 * @property Redirect $model
 */
class RedirectActiveFormNew extends ActiveForm
{
    protected function renderContent(): string|Stringable
    {
        $this->rows ??= [
            [
                'type',
            ],
            [
                FormRow::make()
                    ->content(Div::make()
                        ->class('input strong')
                        ->text('This is a random text')),
                'request_uri',
                'url',
            ],
        ];

        return parent::renderContent();
    }
}
