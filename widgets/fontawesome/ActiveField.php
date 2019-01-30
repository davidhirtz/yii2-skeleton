<?php
namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use rmrevin\yii\fontawesome\FAS;

/**
 * Class ActiveField.
 * @package davidhirtz\yii2\skeleton\widgets\fontawesome
 */
class ActiveField extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField
{
	/**
	 * @var string
	 */
	public $icon;

	/**
	 * @var array default icon html options
	 */
	public $iconOptions=['class'=>'fa-fw'];

	/**
	 * @var string
	 */
	public $iconInputTemplate='<div class="input-group"><div class="input-group-prepend"><span class="input-group-text">{icon}</span></div>{input}</div>';

	/**
	 * Wraps text field with input group and adds font awesome icon.
	 */
	public function init()
	{
		if($this->icon)
		{
			$this->inputTemplate=strtr($this->iconInputTemplate, [
				'{icon}'=>FAS::icon($this->icon, $this->iconOptions),
			]);

			if(!isset($this->inputOptions['placeholder']))
			{
				$this->inputOptions['placeholder']=$this->model->getAttributeLabel($this->attribute);
			}

			$this->labelOptions['class']='sr-only';
		}

		parent::init();
	}
}