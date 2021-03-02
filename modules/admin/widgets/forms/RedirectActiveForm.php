<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\widgets\forms\DynamicRangeDropdown;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\widgets\ActiveField;

/**
 * Class RedirectActiveForm
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms
 *
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    use ModelTimestampTrait;

    /**
     * @var bool
     */
    public $hasStickyButtons = true;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                'type',
                'request_uri',
                'url',
            ];
        }

        parent::init();
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function typeField($options = [])
    {
        return $this->field($this->model, 'type')->widget(DynamicRangeDropdown::class, $options);
    }

    /**
     * Renders user information footer.
     */
    public function renderFooter()
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    /**
     * @return array
     */
    protected function getFooterItems(): array
    {
        return $this->getTimestampItems();
    }
}