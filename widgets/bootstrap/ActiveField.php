<?php
namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFieldTrait;

/**
 * Class ActiveField.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class ActiveField extends \yii\bootstrap4\ActiveField
{
    use ActiveFieldTrait;

	/**
	 * @var string
	 */
	public $checkTemplate='{beginWrapper}<div class="form-check-inline">{input}{label}{error}{hint}</div>{endWrapper}';

	/**
	 * @inheritdoc
	 */
	public function checkbox($options=[], $enclosedByLabel=false)
	{
		$this->labelOptions=[]; // Removes label options, class can be removed when extension is fixed...
		return parent::checkbox($options, $enclosedByLabel);
	}
}