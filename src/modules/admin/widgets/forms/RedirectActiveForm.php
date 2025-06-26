<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ModelTimestampTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\TypeFieldTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;

/**
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    use ModelTimestampTrait;
    use TypeFieldTrait;

    public bool $hasStickyButtons = true;

    /**
     * @uses static::typeField()
     */
    #[\Override]
    public function init(): void
    {
        $this->fields ??= [
            'type',
            'request_uri',
            'url',
        ];

        parent::init();
    }
}
