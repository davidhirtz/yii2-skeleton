<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
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
                SelectField::make()
                    ->property('type')
                    ->items(array_column($this->model::getTypes(), 'name'))
                    ->required()
                    ->empty(),
            ],
            [
                FormRow::make()
                    ->content(
                        Div::make()
                            ->class('input strong')
                            ->text('This is a random text')
                    ),
                'request_uri',
                'url',
            ],
        ];

        return parent::renderContent();
    }
}
