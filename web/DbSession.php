<?php
namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\base\ErrorHandler;
use yii\db\Query;
use yii\web\Session;

/**
 * Class DbSession.
 * @package davidhirtz\yii2\skeleton\web
 */
class DbSession extends \yii\web\DbSession
{
	/**
	 * @var int
	 */
	public $updateInterval=60;

	/**
	 * @var array
	 */
	private $_data;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if(!$this->writeCallback)
		{
			$this->writeCallback=function()
			{
				return [
					'ip'=>sprintf('%u', ip2long(Yii::$app->getRequest()->getUserIP())),
				];
			};
		}

		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function regenerateID($deleteOldSession=false)
	{
		$oldId=$this->getId();

		if(empty($oldId))
		{
			return;
		}

		$session=$this->getData();

		Session::regenerateID(false);
		$this->setData(['id'=>$this->getId()]);

		if($session)
		{
			if($deleteOldSession)
			{
				$this->db->createCommand()
					->update($this->sessionTable, ['id'=>$this->getId()], ['id'=>$oldId])
					->execute();
			}
			else
			{
				$this->db->createCommand()
					->insert($this->sessionTable, $this->getData())
					->execute();
			}
		}
		else
		{
			$this->db->createCommand()
				->insert($this->sessionTable, $this->composeFields($this->getId(), ''))
				->execute();
		}
	}

	/**
	 * @inheritdoc
	 */
	public function readSession($id)
	{
		$session=$this->getData();

		if(empty($session['expire']) || $session['expire']<time())
		{
			return '';
		}

		return $this->readCallback!==null ? $this->extractData($session) : $session['data'];
	}

	/**
	 * @inheritdoc
	 */
	public function writeSession($id, $data)
	{
		try
		{
			$session=$this->getData();
			$fields=$this->composeFields($id, $data);

			if($session)
			{
				$fields=$this->getChangedFields($fields, $session);
				unset($fields['id']);

				if($fields)
				{
					$this->db->createCommand()
						->update($this->sessionTable, $fields, ['id'=>$id])
						->execute();
				}
			}
			else
			{
				$this->db->createCommand()
					->insert($this->sessionTable, $fields)
					->execute();
			}

			return true;
		}
		catch(\Exception $e)
		{
			$exception=ErrorHandler::convertExceptionToString($e);
			error_log($exception);
			echo $exception;

			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function destroySession($id)
	{
		$this->setData(null);
		return parent::destroySession($id);
	}

	/**
	 * @inheritdoc
	 */
	public function gcSession($maxLifetime)
	{
		$this->db->createCommand()
			->delete('session_auth_key', '[[expire]]<:expired', [':expired'=>time()])
			->execute();

		return parent::gcSession($maxLifetime);
	}

	/**
	 * @param array $fields
	 * @param array $session
	 * @return array
	 */
	protected function getChangedFields($fields, $session)
	{
		$columns=[];

		foreach($fields as $name=>$value)
		{
			if(!array_key_exists($name, $session) || ($name=='expire' && $value-$session['expire']>=$this->updateInterval) || ($name!='expire' && $value!=$session[$name]))
			{
				$columns[$name]=$value;
			}
		}

		if($columns)
		{
			$columns['expire']=$fields['expire'];
		}

		return $columns;
	}

	/**
	 * @return array|false
	 */
	public function getData()
	{
		if($this->_data===null)
		{
			$this->_data=(new Query())
				->from($this->sessionTable)
				->where(['id'=>$this->getId()])
				->one();
		}

		return $this->_data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->_data=is_array($this->_data) && is_array($data)? array_merge($this->_data, $data) : $data;
	}
}