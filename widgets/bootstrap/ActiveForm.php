<?php
namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class ActiveForm.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 *
 * @method static ActiveForm begin($config=[])
 */
class ActiveForm extends \yii\bootstrap4\ActiveForm
{
	/**
	 * @inheritdoc
	 */
	public $fieldConfig=[
		'horizontalCssClasses'=>[
			'wrapper'=>'col-md-8 col-lg-6',
			'field'=>'form-group row',
			'label'=>'col-form-label col-md-4',
			'offset'=>'offset-md-4 col-md-8 col-lg-6',
		],
	];

	/**
	 * @inheritdoc
	 */
	public $options=[
		'role'=>'form',
	];

	/**
	 * @inheritdoc
	 */
	public $layout='horizontal';

	/**
	 * @var string
	 */
	public $fieldClass = 'davidhirtz\yii2\skeleton\bootstrap\ActiveField';

	/**
	 * @inheritdoc
	 */
	public $validationStateOn=self::VALIDATION_STATE_ON_CONTAINER;

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Renders fields and removes form generation if action is set to false.
	 */
	public function run()
	{
		if($this->action===false)
		{
			// This can probably be removed in mid 2019.
			throw new \Exception('Setting action to "false" is no longer supported.');
		}

		$this->renderFields();
		parent::run();
	}

	/**
	 * Renders the form fields.
	 */
	public function renderFields()
	{
	}

	/**
	 * @return string
	 */
	public function renderHorizontalLine()
	{
		return '<hr>';
	}

	/**
	 * @param \davidhirtz\yii2\skeleton\db\ActiveRecord|string $label
	 * @param array $options
	 * @param array $rowOptions
	 *
	 * @return string
	 */
	public function submitButton($label, $options=['class'=>'btn-primary'], $rowOptions=[])
	{
		if($label instanceof ActiveRecord)
		{
			$label=$label->getIsNewRecord() ? Yii::t('app', 'Create') : Yii::t('app', 'Update');
		}

		Html::addCssClass($options, ['btn', 'btn-submit']);
		return $this->buttonRow(Html::button(ArrayHelper::remove($options, 'label', $label), array_merge($options, ['type'=>'submit'])), $rowOptions);
	}

	/**
	 * @param array|string $buttons
	 * @param array $options
	 * @return string
	 */
	public function buttonRow($buttons, $options=[])
	{
		return $this->row($this->offset(Html::buttons($buttons, $options)), ['class'=>'form-group-buttons']);
	}

	/**
	 * @param string $content
	 * @param array $options
	 * @return string
	 */
	public function textRow($content, $options=[])
	{
		return $this->row($this->offset(Html::formText($content, $options)));
	}

	/**
	 * @param array $items
	 * @param array $options
	 * @return string
	 */
	public function listRow($items, $options=[])
	{
		if(!$options)
		{
			$options=[
				'class'=>'list-unstyled small text-muted',
				'encode'=>false,
			];
		}

		return $this->renderHorizontalLine().$this->textRow(Html::ul($items, $options));
	}

	/**
	 * @param string $content
	 * @param array $options
	 * @return string
	 */
	public function offset($content, $options=[])
	{
		Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['offset']);
		return Html::tag('div', $content, $options);
	}

	/**
	 * @param string $content
	 * @param array $options
	 * @return string
	 */
	public function row($content, $options=[])
	{
		Html::addCssClass($options, $this->fieldConfig['horizontalCssClasses']['field']);
		return Html::tag('div', $content, $options);
	}
}