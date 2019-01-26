<?php
namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\bootstrap4\BootstrapAsset;

/**
 * Class ListGroup.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class ListGroup extends \yii\bootstrap4\Widget
{
	/**
	 * @var array
	 */
	public $items=[];

	/**
	 * @var bool
	 */
	public $encodeLabels=true;

	/**
	 * Init.
	 */
	public function init()
	{
		Html::addCssClass($this->options, ['widget'=>'list-group']);
		parent::init();
	}

	/**
	 * @return string
	 */
	public function run()
	{
		BootstrapAsset::register($this->getView());
		return $this->renderItems();
	}

	/**
	 * @return string
	 */
	public function renderItems()
	{
		$items=[];

		foreach($this->items as $i=>$item)
		{
			if(isset($item['visible']) && !$item['visible'])
			{
				continue;
			}

			$items[]=$this->renderItem($item);
		}

		return Html::tag('div', implode('', $items), $this->options);
	}

	/**
	 * @param array $item
	 * @return string
	 */
	protected function renderItem($item)
	{
		$encodeLabel=isset($item['encode']) ? $item['encode'] : $this->encodeLabels;
		$label=$encodeLabel ? Html::encode($item['label']) : $item['label'];

		if(isset($item['icon']))
		{
			$label=Html::iconText($item['icon'], $label);
		}

		$options=ArrayHelper::getValue($item, 'options', []);
		$url=ArrayHelper::getValue($item, 'url', '#');

		Html::addCssClass($options, ['list-group-item', 'list-group-item-action']);
		return Html::a($label, $url, $options);
	}
}