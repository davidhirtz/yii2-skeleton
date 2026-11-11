<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\SitemapUrlArrayDataProvider;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class SitemapController extends Controller
{
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
                        'actions' => ['index'],
                        'roles' => [User::ROLE_AUTHENTICATED],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?string $key = null, int $offset = 0): Response|string
    {
        $sitemap = Sitemap::getComponent();

        if (!$sitemap->isEnabled()) {
            throw new NotFoundHttpException();
        }

        $sitemaps = $sitemap->getSitemaps();
        $key ??= (string)array_key_first($sitemaps);
        $current = $sitemap->getSitemap($key);

        if (!$current || $offset < 0 || $offset >= max(1, $current->getPageCount())) {
            throw new NotFoundHttpException();
        }

        $provider = Yii::$container->get(SitemapUrlArrayDataProvider::class, config: [
            'sitemap' => $current,
            'offset' => $offset,
        ]);

        return $this->render('index', [
            'sitemap' => $sitemap,
            'provider' => $provider,
            'key' => $key,
            'offset' => $offset,
        ]);
    }
}
