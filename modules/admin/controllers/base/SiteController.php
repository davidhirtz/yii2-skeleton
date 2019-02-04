<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers\base;

use davidhirtz\yii2\skeleton\web\Controller;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HomePanelInterface;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

/**
 * Class SiteController.
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\SiteController
 */
class SiteController extends Controller
{
    /**
     * @var array
     */
    protected $excludedModules = [];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'error'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'error' => [
                'view' => '@skeleton/modules/admin/views/site/error',
            ],
        ]);
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $excludedModules = array_merge(['debug', 'gii'], (array)$this->excludedModules);
        $panels = [];

        /** @var Module $module */
        foreach (Yii::$app->getModules() as $name => $module) {
            if (!in_array($name, $excludedModules)) {
                /**  @var HomePanelInterface $className */
                if (class_exists($className = "app\\modules\\{$name}\\modules\\admin\\components\\widgets\\panels\\HomePanel")) {
                    $panels[] = $className;
                }
            }
        }

        return $this->render('@skeleton/modules/admin/views/site/index', [
            'panels' => $panels,
        ]);
    }
}
