<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use Override;

/**
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            'type',
            'request_uri',
            'url',
        ];

        parent::configure();
    }
}
