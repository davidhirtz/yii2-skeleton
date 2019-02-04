<?php
namespace davidhirtz\yii2\skeleton\modules\admin\controllers\base;

use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class UserController.
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers\base
 */
class UserController extends Controller
{
	/***********************************************************************
	 * Behaviors.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access'=>[
				'class'=>AccessControl::class,
				'rules'=>[
					[
						'allow'=>true,
						'actions'=>['create'],
						'roles'=>['userCreate'],
					],
					[
						'allow'=>true,
						'actions'=>['delete'],
						'roles'=>['userDelete'],
					],
					[
						'allow'=>true,
						'actions'=>['deauthorize', 'index', 'ownership', 'update'],
						'roles'=>['userUpdate'],
					],
				],
			],
			'verbs'=>[
				'class'=>VerbFilter::class,
				'actions'=>[
					'deauthorize'=>['post'],
					'delete'=>['post'],
				],
			],
		];
	}

	/***********************************************************************
	 * Actions.
	 ***********************************************************************/

	/**
	 * @param string $q
	 * @return string
	 */
	public function actionIndex($q=null)
	{
		$provider=new ActiveDataProvider([
			'query'=>User::find()
				->selectListAttributes()
				->matching($q),
		]);

		$provider->getSort()->defaultOrder=['last_login'=>SORT_DESC];
		$provider->getPagination()->defaultPageSize=50;

		/** @noinspection MissedViewInspection */
		return $this->render('index', [
			'provider'=>$provider,
		]);
	}

	/**
	 * @throws \Throwable
	 * @return string
	 */
	public function actionCreate()
	{
		$user=new UserForm;
		$user->status=$user::STATUS_ENABLED;

		if($user->load(Yii::$app->getRequest()->post()))
		{
			if($user->insert())
			{
				$this->success(Yii::t('app', 'The user was created.'));
				return $this->redirect(['update', 'id'=>$user->id]);
			}
		}
		else
		{
			$identity=Yii::$app->getUser()->getIdentity();
			$user->language=$identity->language;
			$user->timezone=$identity->timezone;
		}

		/** @noinspection MissedViewInspection */
		return $this->render('create',  [
			'user'=>$user,
		]);
	}

	/**
	 * @param int $id
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 * @return string
	 */
	public function actionUpdate($id)
	{
		$user=UserForm::findOne(['id'=>$id]);

		if(!$user)
		{
			throw new NotFoundHttpException;
		}

		if(!Yii::$app->getUser()->can('userUpdate', ['user'=>$user]))
		{
			throw new ForbiddenHttpException;
		}

		if($user->load(Yii::$app->getRequest()->post()))
		{
			if($user->update())
			{
				$this->success(Yii::t('app', 'The user was updated.'));
				return $this->refresh();
			}
		}

		/** @noinspection MissedViewInspection */
		return $this->render('update', [
			'user'=>$user,
		]);
	}

	/**
	 * @param int $id
	 * @throws NotFoundHttpException
	 * @throws \Throwable
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\StaleObjectException
	 * @return string
	 */
	public function actionDelete($id)
	{
		if(!$user=User::findOne(['id'=>$id]))
		{
			throw new NotFoundHttpException;
		}

		$form=new DeleteForm([
			'model'=>$user,
		]);

		if($form->load(Yii::$app->getRequest()->post()))
		{
			if($form->delete())
			{
				$this->success(Yii::t('app', 'The user was deleted.'));

				if($user->id==Yii::$app->getUser()->id)
				{
					Yii::$app->getUser()->logout(false);
					return $this->goHome();
				}

				return $this->redirect(['index']);
			}
		}

		$this->error($form->getFirstErrors());

		return $this->redirect(['update', 'id'=>$user->id]);
	}

	/**
	 * @param string $id
	 * @param string $name
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 * @throws ServerErrorHttpException
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 * @return string
	 */
	public function actionDeauthorize($id, $name)
	{
		$auth=AuthClient::find()
			->where(['auth_client.id'=>$id, 'auth_client.name'=>$name])
			->joinWith('user', true, 'JOIN')
			->limit(1)
			->one();

		if(!$auth)
		{
			throw new NotFoundHttpException;
		}

		if(!Yii::$app->getUser()->can('userUpdate', ['user'=>$auth->user]))
		{
			throw new ForbiddenHttpException;
		}

		if($auth->delete())
		{
			$client=$auth->getClientClass();

			$this->success(Yii::t('app', '{client} account "{name}" was removed from user account.', [
				'client'=>$client->getTitle(),
				'name'=>$client::getDisplayName($auth),
			]));

			return $this->redirect(['update', 'id'=>$auth->user_id]);
		}

		throw new ServerErrorHttpException;
	}

	/**
	 * @throws ForbiddenHttpException
	 * @return string
	 */
	public function actionOwnership()
	{
		if(!Yii::$app->getUser()->getIdentity()->isOwner())
		{
			throw new ForbiddenHttpException;
		}

		$form=new OwnershipForm;

		if($form->load(Yii::$app->request->post()))
		{
			if($form->transfer())
			{
				$this->success(Yii::t('app', 'The website ownership was successful transferred!'));
				return $this->goHome();
			}
		}

		/** @noinspection MissedViewInspection */
		return $this->render('ownership', [
			'form'=>$form,
		]);
	}
}