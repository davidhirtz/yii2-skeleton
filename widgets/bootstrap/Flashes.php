<?php
namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\bootstrap4\Html;

/**
 * Class Flashes.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class Flashes extends \yii\bootstrap4\Widget
{
	/**
	 * @var array
	 */
	public $alerts;

	/**
	 * Loads flash messages.
	 */
	public function init()
	{
		if($this->alerts===null)
		{
			$this->alerts=Yii::$app->getSession()->getAllFlashes();
		}
	}

	/**
	 * Displays flash messages.
	 */
	public function run()
	{
		if($this->alerts)
		{
			foreach($this->alerts as $status=>$alerts)
			{
				foreach((array)$alerts as $alert)
				{
					foreach((array)$alert as $message)
					{
						echo $this->renderAlert($status, $message);
					}
				}
			}
		}
	}

	/**
	 * @param string $status
	 * @param array|string $message
	 *
	 * @return string
	 */
	public function renderAlert($status, $message)
	{
		$tag=ArrayHelper::remove($this->options, 'tag', 'div');
		$message=Html::tag('div', $message, ['class'=>'alert alert-'.$status]);

		return $this->options ? Html::tag($tag, $message, $this->options) : $message;
	}
}