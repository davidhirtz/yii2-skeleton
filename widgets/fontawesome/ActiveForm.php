<?php
namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

/**
 * Class ActiveField.
 * @package davidhirtz\yii2\skeleton\widgets\fontawesome
 */
class ActiveForm extends \yii\bootstrap4\ActiveForm
{
	/**
	 * @var string
	 */
	public $fieldClass='davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField';

	/**
	 * @var string
	 */
	public $validationStateOn=self::VALIDATION_STATE_ON_CONTAINER;
}