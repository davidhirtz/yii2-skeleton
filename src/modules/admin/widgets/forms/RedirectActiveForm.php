<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ModelTimestampTrait;
use davidhirtz\yii2\skeleton\widgets\forms\DynamicRangeDropdown;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;

/**
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    use ModelTimestampTrait;

    public bool $hasStickyButtons = true;

    public function init(): void
    {
        $this->fields ??= [
            ['type', DynamicRangeDropdown::class],
            'request_uri',
            'url',
        ];

        parent::init();
    }

    public function renderFooter(): void
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    protected function getFooterItems(): array
    {
        return $this->getTimestampItems();
    }
}