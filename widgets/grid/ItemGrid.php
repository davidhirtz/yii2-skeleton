<?php

namespace davidhirtz\yii2\skeleton\widgets\grid;

use davidhirtz\yii2\skeleton\widgets\forms\FileUpload;
use Yii;
use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * Class SortableGrid.
 * @package davidhirtz\yii2\skeleton\widgets\grid
 */
class ItemGrid extends Widget
{
    /**
     * @var ActiveRecord[]
     */
    public $models = [];

    /**
     * @var string
     */
    public $itemViewFile = '_view';

    /**
     * @var string
     */
    public $uploadViewFile = '@skeleton/components/widgets/grid/views/_upload';

    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var bool
     */
    public $enabledFileUpload = true;

    /**
     * @var bool
     */
    public $fileUploadOptions = [];

    /**
     * @var bool
     */
    public $enabledSortable = true;

    /**
     * @var bool
     */
    public $sortableOptions = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!isset($options['id'])) {
            $this->options = [
                'id' => $this->getId(false) ?: 'files',
                'class' => 'row',
            ];
        }

        parent::init();
    }

    public function run()
    {
        echo Html::beginTag('div', $this->options);

        $this->renderEmptyInfo();
        $this->renderItems();

        echo Html::endTag('div');

        $this->renderForm();

        $this->registerClientScripts();
        parent::run();
    }

    /**
     * Renders items.
     */
    protected function renderItems()
    {
        $param = null;

        foreach ($this->models as $model) {
            if (!$param) {
                $param = lcfirst($model->formName());
            }

            echo $this->render($this->itemViewFile, [
                $param => $model,
            ]);
        }
    }

    /**
     * Renders upload form.
     */
    protected function renderForm()
    {
        if ($this->enabledFileUpload) {
            echo $this->render($this->uploadViewFile, [
                'upload' => FileUpload::widget($this->fileUploadOptions),
            ]);
        }
    }

    /**
     * Registers client scripts.
     */
    protected function registerClientScripts()
    {
        if ($this->enabledSortable) {
            $defaultSortableOptions = [
                'id' => $this->options['id'],
                'ajaxUpdateRoute' => ['order'],
                'clonePlaceholderContent' => true,
                'clientOptions' => [
                    'items' => '.item',
                ],
            ];

            SortableWidget::widget(array_merge($defaultSortableOptions, $this->sortableOptions));
        }

        $this->getView()->registerJs('$("#' . $this->options['id'] . '").on("submit", "form", function(e){var f=$(this);$.post(f.attr("action"), function(){f.parents(".item").fadeOut(function(){$(this).remove()})});e.preventDefault()});');
    }

    /**
     * Renders the info.
     */
    public function renderEmptyInfo()
    {
        if ($this->enabledFileUpload) {
            $info = Html::tag('div', $this->message ?: Yii::t('skeleton', 'Did you know you can also drag and drop files here to upload them instantly?'), ['class' => 'alert alert-info']);
            echo Html::tag('div', $info, ['class' => 'col visible-if-empty']);
        }
    }
}