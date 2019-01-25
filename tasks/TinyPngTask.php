<?php
namespace davidhirtz\yii2\skeleton\tasks;
use Yii;
use yii\base\Exception;
use yii\base\BaseObject;

/**
 * Class TinyPngTask.
 * @package davidhirtz\yii2\skeleton\tasks
 */
class TinyPngTask extends BaseObject implements TaskInterface
{
	/**
	 * @var string
	 */
	public $file;

	/**
	 * @var string
	 */
	public $dest;

	/**
	 * @var array
	 */
	public $resize;

	/**
	 * @var string
	 */
	private $_message;

	/**
	 * Makes sure params are set.
	 */
	public function init()
	{
		if(!$this->dest)
		{
			$this->dest=$this->file;
		}

		parent::init();
	}

	/**
	 * Connects to TinyPNG Api to resize given image.
	 */
	public function run()
	{
		if(empty(Yii::$app->params['tinyPng.apiKey']))
		{
			$this->setMessage('A valid API key must be set to use the TinyPNG web service.');
			return false;
		}

		if(!$this->dest)
		{
			$this->setMessage('A valid file path must be set to use the TinyPNG web service.');
			return false;
		}

		try
		{
			/**
			 * Make sure file still exists.
			 */
			if(!file_exists($this->file))
			{
				$this->setMessage(Yii::t('app', 'File {filename} could not be compressed.', [
					'filename'=>$this->file,
				]));

				return false;
			}

			/**
			 * Load Tinify API.
			 */
			\Tinify\setKey(Yii::$app->params['tinyPng.apiKey']);
			$source=\Tinify\fromFile($this->file);
			$filesize=@filesize($this->file);

			if($this->resize)
			{
				$source->resize($this->resize);
			}

			if($newFilesize=$source->toFile($this->dest))
			{
				$this->setMessage(Yii::t('app', 'File {filename} was compressed by {filesize} ({percent}).', [
					'filename'=>$this->dest,
					'filesize'=>Yii::$app->getFormatter()->asSize($filesize-$newFilesize),
					'percent'=>Yii::$app->getFormatter()->asPercent(($filesize-$newFilesize)/$filesize, 1),
				]));

				return true;
			}
		}
		catch(Exception $ex)
		{
			$this->setMessage($ex->getMessage());
		}

		return false;
	}

	/**
	 * @param $message
	 */
	public function setMessage($message)
	{
		$this->_message=$message;
	}

	/**
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}
}