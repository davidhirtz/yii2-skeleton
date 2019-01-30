<?php
namespace davidhirtz\yii2\skeleton\widgets;
use davidhirtz\yii2\skeleton\assets\VimeoAsset;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class VimeoPlayer.
 * @package davidhirtz\yii2\skeleton\widgets
 */
class VimeoPlayer extends Widget
{
	/**
	 * @var int
	 */
	public $videoId;

	/**
	 * @var string.
	 */
	public $url;

	/**
	 * @var array the html options.
	 */
	public $options=[];

	/**
	 * @var bool
	 */
	public $registerScripts=true;

	/**
	 * @var bool
	 */
	public $autodetect=false;

	/**
	 * @var array the Vimeo player config
	 */
	public $config=[];

	/**
	 * @var string
	 */
	public $varName='player';

	/**
	 * Extracts vimeo id from url.
	 */
	public function init()
	{
		if(!$this->videoId)
		{
			$this->videoId=preg_match('/^\D*(\d+)/', parse_url($this->url, PHP_URL_PATH), $matches) ? (int)array_pop($matches) : null;
		}

		if($this->autodetect)
		{
			ArrayHelper::setDefaultValue($this->options, 'data-vimeo-id', $this->videoId);
		}

		ArrayHelper::setDefaultValue($this->options, 'id', $this->getId());
		parent::init();
	}

	/**
	 * Displays video.
	 */
	public function run()
	{
		echo Html::tag('div', '', $this->options);

		if($this->registerScripts)
		{
			VimeoAsset::register($view=$this->getView());

			if(!$this->autodetect || $this->config)
			{
				$view->registerJs(($this->varName ? "var {$this->varName}=" : '').'new Vimeo.Player("'.$this->options['id'].'"'.($this->config ? (','.Json::htmlEncode($this->config)) : '').');');
			}
		}
	}
}