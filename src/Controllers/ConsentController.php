<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Controllers;

use Hirtz\Skeleton\Caching\CacheCounter;
use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\base\Module;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\TooManyRequestsHttpException;

/**
 * @extends Controller<Module>
 */
class ConsentController extends Controller
{
    /**
     * Posts per IP address and {@see $limitDuration}, `0` for no limit.
     */
    public int $limit = 30;
    public int $limitDuration = 3600;

    public $enableCsrfValidation = false;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
                ],
            ],
        ];
    }

    public function actionCreate(): void
    {
        if (!AdminModule::current()->enableConsentLog) {
            throw new NotFoundHttpException();
        }

        if ($this->limit > 0) {
            $key = [self::class, $this->request->getUserIP()];

            if (CacheCounter::increment($key, $this->limitDuration) > $this->limit) {
                throw new TooManyRequestsHttpException();
            }
        }

        $consent = Consent::create();
        $consent->uuid = $this->getPostString('id');
        $consent->version = $this->getPostString('version');
        $consent->categories = array_values(array_filter(explode(',', $this->getPostString('categories'))));

        if (!$consent->insert()) {
            throw new BadRequestHttpException();
        }

        $this->response->setStatusCode(204);
    }

    private function getPostString(string $name): string
    {
        $value = $this->request->post($name);
        return is_string($value) ? $value : '';
    }
}
