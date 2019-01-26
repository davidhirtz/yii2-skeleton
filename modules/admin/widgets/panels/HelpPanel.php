<?php
namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

/**
 * Class HelpPanel.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets
 */
class HelpPanel extends Panel
{
	/**
	 * @var array
	 */
	public $wrapOptions=[
		'class'=>'row',
	];

	/**
	 * @var array
	 */
	public $contentOptions=[
		'class'=>'offset-md-4 col-md-8 col-lg-6',
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if($this->content)
		{
			$this->content=Html::tag('div', Html::tag('div', $this->content, $this->contentOptions), $this->wrapOptions);
		}

		parent::init();
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function renderHelpBlock($text)
	{
		return Html::tag('p', $text);
	}

	/**
	 * @param array|string $buttons
	 * @return string
	 */
	public function renderButtonToolbar($buttons)
	{
		if($buttons)
		{
			return Html::tag('div', Html::buttons($buttons), ['class'=>'card-buttons']);
		}
	}
}

