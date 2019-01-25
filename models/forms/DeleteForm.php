<?php
namespace davidhirtz\yii2\skeleton\models\forms;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\NotFoundHttpException;

/**
 * Class DeleteForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class DeleteForm extends Model
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $attribute='name';

	/**
	 * @var \davidhirtz\yii2\skeleton\db\ActiveRecord
	 * @see DeleteForm::getModel()
	 */
	private $_model;

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		if($this->attribute!==false)
		{
			return [
				[
					['name'],
					'filter',
					'filter'=>'trim',
				],
				[
					['name'],
					'required',
				],
				[
					['name'],
					'compare',
					'compareValue'=>$this->getModel()->getAttribute($this->attribute),
				],
			];
		}

		return [];
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * @return bool
	 * @throws InvalidConfigException
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
	public function delete()
	{
		return $this->validate() ? $this->getModel()->delete() : false;
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return mixed
	 * @throws InvalidConfigException
	 */
	public function getId()
	{
		return $this->getModel()->getPrimaryKey();
	}

	/**
	 * @return \davidhirtz\yii2\skeleton\db\ActiveRecord
	 * @throws InvalidConfigException
	 */
	public function getModel()
	{
		if(!$this->_model)
		{
			throw new InvalidConfigException;
		}

		return $this->_model;
	}

	/**
	 * @param \davidhirtz\yii2\skeleton\db\ActiveRecord $model
	 * @throws NotFoundHttpException
	 */
	public function setModel($model)
	{
		if(!$model)
		{
			throw new NotFoundHttpException;
		}

		$this->_model=$model;
	}

	/***********************************************************************
	 * Model.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'name'=>$this->getModel()->getAttributeLabel($this->attribute),
		];
	}
}