<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class Toolbar
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Toolbar extends \yii\base\Widget
{
    /**
     * @var ActiveRecord
     */
    public $model;

    /**
     * @var array|string containing the buttons on the left side of the toolbar
     */
    public $actions;

    /**
     * @var array|string containing the links on the right side of the toolbar
     */
    public $links;

    /**
     * @var array containing the navbar options.
     */
    public $wrapperOptions = [
        'class' => 'toolbar',
    ];

    /**
     * @var array containing the navbar options.
     */
    public $containerOptions = [
        'class' => 'container',
    ];

    /**
     * @var array containing the navbar options.
     */
    public $toolbarOptions = [
        'class' => 'btn-toolbar justify-content-between',
    ];

    /**
     * @return string
     */
    public function run()
    {
        $actions = $this->renderActions($this->actions);
        $links = $this->renderLinks($this->links);

        if ($actions || $links) {
            $content = Html::tag('div', $actions . $links, $this->toolbarOptions);
            return Html::tag('div', Html::tag('div', $content, $this->containerOptions), $this->wrapperOptions);
        }

        return '';
    }

    /**
     * @param array|string $items
     * @param array|null $options
     * @return string
     */
    protected function renderActions($items, $options = null)
    {
        if ($options === null) {
            $options = ['class' => $this->hasForm() ? 'col offset-md-3' : 'col'];
        }

        return $this->renderItems($items, $options);
    }

    /**
     * @param array|string $items
     * @param array|null $options
     * @return string
     */
    protected function renderLinks($items, $options = null)
    {
        if ($options === null) {
            $options = ['class' => 'col text-right'];
        }

        return $this->renderItems($items, $options);
    }

    /**
     * @param array|string $items
     * @param array $options
     * @return string
     */
    protected function renderItems($items, $options = [])
    {
        if ($items = array_filter((array)$items)) {
            return Html::tag('div', implode('', $items), $options);
        }

        return '';
    }

    /**
     * @param string|null $formName
     * @return string
     */
    protected function getFormSubmitButton($formName = null)
    {
        return Html::submitButton($this->model->getIsNewRecord() ? Yii::t('skeleton', 'Create') : Yii::t('skeleton', 'Update'), [
            'class' => 'btn btn-primary btn-submit',
            'form' => $formName ?: strtolower($this->model->formName()),
        ]);
    }

    /**
     * @return bool
     */
    public function hasForm(): bool
    {
        return (bool)$this->model;
    }
}