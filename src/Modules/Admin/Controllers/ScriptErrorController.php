<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Logged as an error, the one level `Log\SentryTarget` takes by default.
 *
 * @extends Controller<Module>
 */
class ScriptErrorController extends Controller
{
    final public const string LOG_CATEGORY = 'js';

    private const array FIELDS = [
        'message' => 1000,
        'file' => 2000,
        'line' => 10,
        'column' => 10,
        'stack' => 8000,
        'url' => 2000,
        'userAgent' => 500,
    ];

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
                ],
            ],
        ];
    }

    public function actionCreate(): Response
    {
        $values = [];

        foreach (self::FIELDS as $name => $length) {
            $value = $this->request->post($name);
            $values[$name] = is_string($value) ? mb_substr($value, 0, $length) : '';
        }

        if ($values['message'] === '') {
            throw new BadRequestHttpException();
        }

        Yii::error(implode("\n", [
            $values['message'],
            "at {$values['file']}:{$values['line']}:{$values['column']}",
            "URL: {$values['url']}",
            "User agent: {$values['userAgent']}",
            '',
            $values['stack'],
        ]), self::LOG_CATEGORY);

        $this->response->setStatusCode(204);
        return $this->response;
    }
}
