<?php
namespace davidhirtz\yii2\skeleton\fontawesome;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use rmrevin\yii\fontawesome\FA;
use yii\helpers\Html;
use Yii;

/**
 * Class Nav.
 * @package davidhirtz\yii2\skeleton\fontawesome
 */
class Nav extends \yii\bootstrap4\Nav
{
	/**
	 * @var string default item template, can be set individually by item option "template".
	 */
	public $itemTemplate='{icon} {label} {badge}';

	/**
	 * @var array default link html options, can be set individually by item options "linkOptions".
	 */
	public $linkOptions=[];

	/**
	 * @var array default icon html options, can be set individually by item option "iconOptions".
	 */
	public $iconOptions=['class'=>'fa-fw'];

	/**
	 * @var array default badge html options, can be set individually by item option "badgeOptions".
	 */
	public $badgeOptions=['class'=>'badge'];

	/**
	 * @var array default label html options, can be set individually by item option "labelOptions".
	 */
	public $labelOptions=[];

	/**
	 * @var bool
	 */
	private $isActive=false;

	/**
	 * Allows the addition of Font Awesome icons to nav label and
	 * wraps label in additional span tag.
	 *
	 * Changed item options:
	 *
	 * - active: boolean|array allows a multiple array that is checked against controller and module
	 * - badge: string, optional, adds badge to item label
	 * - badgeOptions: array, optional, additional html options for badge tag.
	 * - icon: string, optional, the Font Awesome icon name.
	 * - iconOptions: array, optional, additional html options for icon tag.
	 * - items: array|callable allows submenu items to be callable
	 * - label: string, optional, if icon is set, required if icon is empty.
	 * - labelOptions: array, optional, additional html options for label tag
	 * - template: string, optional, use format "{icon}{label}" to change label template.
	 *
	 * @inheritdoc
	 */
	public function renderItem($item)
	{
		/**
		 * Link options.
		 */
		if($this->linkOptions)
		{
			ArrayHelper::setDefaultValue($item, 'linkOptions', $this->linkOptions);
		}

		/**
		 * Icon & badge.
		 */
		$icon=ArrayHelper::getValue($item, 'icon');
		$badge=ArrayHelper::getValue($item, 'badge', false);

		if($icon || $badge)
		{
			$label=ArrayHelper::getValue($item, 'label');
			$iconOptions=ArrayHelper::getValue($item, 'iconOptions', $this->iconOptions);
			$badgeOptions=ArrayHelper::getValue($item, 'badgeOptions', $this->badgeOptions);
			$template=ArrayHelper::getValue($item, 'template', $this->itemTemplate);

			if(ArrayHelper::getValue($item, 'encode', $this->encodeLabels))
			{
				$label=Html::encode($label);
				$item['encode']=false;
			}

			$item['label']=strtr($template, [
				'{icon}'=>$icon ? FA::icon($icon, $iconOptions) : '',
				'{badge}'=>$badge!==false ? Html::tag('span', $badge, $badgeOptions) : '',
				'{label}'=>$label ? Html::tag('span', $label, ArrayHelper::getValue($item, 'labelOptions', $this->labelOptions)) : '',
			]);
		}

		/**
		 * Items.
		 */
		if($items=ArrayHelper::getValue($item, 'items'))
		{
			if($items instanceof \Closure)
			{
				$item['items']=call_user_func($items) ?: null;
			}
		}

		/**
		 * Active.
		 */
		if(is_array($routes=ArrayHelper::getValue($item, 'active')))
		{
			$item['active']=false;

			if(!$this->isActive)
			{
				foreach($routes as $route)
				{
					if(preg_match("~{$route}~", Yii::$app->controller->route, $matches))
					{
						$this->isActive=$item['active']=true;
						break;
					}
				}
			}
		}

		return parent::renderItem($item);
	}
}