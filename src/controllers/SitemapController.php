<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\controllers;

use DateTime;
use davidhirtz\yii2\skeleton\web\Controller;
use XMLWriter;
use Yii;
use yii\filters\PageCache;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SitemapController extends Controller
{
    private ?XMLWriter $writer = null;

    public function init(): void
    {
        if (!Yii::$app->has('sitemap')) {
            throw new NotFoundHttpException();
        }

        parent::init();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $sitemap = Yii::$app->sitemap;

        if ($sitemap->cache) {
            $variations = $sitemap->variations;

            if ($sitemap->useSitemapIndex) {
                $variations[] = Yii::$app->getRequest()->get('key');
                $variations[] = Yii::$app->getRequest()->get('offset');
            }

            $behaviors[] = [
                'class' => PageCache::class,
                'only' => ['index'],
                'cache' => $sitemap->cache,
                'duration' => $sitemap->duration,
                'dependency' => $sitemap->dependency,
                'variations' => $variations,
            ];
        }

        return $behaviors;
    }

    public function actionIndex(?string $key = null, int $offset = 0): string|bool
    {
        $sitemap = Yii::$app->sitemap;
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        ob_start();
        ob_implicit_flush(false);

        $headers = $response->getHeaders();
        $headers->add('Content-Type', 'application/xml');

        $this->writer = new XMLWriter();
        $this->writer->openUri('php://output');
        $this->writer->startDocument('1.0', 'UTF-8');

        if ($sitemap->useSitemapIndex && $key === null) {
            $this->writeUrlset(Yii::$app->sitemap->generateIndexUrls(), true);
        } else {
            $this->writeUrlset($sitemap->generateUrls($key, $offset));
        }

        $this->writer->endDocument();
        $this->writer->flush();

        return ob_get_clean();
    }

    private function writeUrlset(array $urls, bool $isIndex = false): void
    {
        $this->writer->startElement($isIndex ? 'sitemapindex' : 'urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($urls as $url) {
            if (isset($url['images'])) {
                $this->writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
            }

            $this->writer->startElement($isIndex ? 'sitemap' : 'url');
            $this->writer->writeElement('loc', Url::to(is_array($url) ? $url['loc'] : $url, true));

            if (isset($url['lastmod'])) {
                $lastmod = $url['lastmod'];
                $this->writer->writeElement('lastmod', $lastmod instanceof DateTime ? $lastmod->format(DATE_W3C) : $lastmod);
            }

            if (isset($url['changefreq'])) {
                $this->writer->writeElement('changefreq', $url['changefreq']);
            }

            if (isset($url['priority'])) {
                $this->writer->writeElement('priority', $url['priority']);
            }

            if (isset($url['images'])) {
                foreach ($url['images'] as $image) {
                    $this->writer->startElement('image:image');
                    $this->writer->writeElement('image:loc', Url::to(is_array($image) ? $image['loc'] : $image, true));

                    foreach (['caption', 'geo_location', 'license', 'title'] as $element) {
                        if (!empty($image[$element])) {
                            $this->writer->writeElement('image:' . $element, $image[$element]);
                        }
                    }

                    $this->writer->endElement();
                }
            }

            $this->writer->endElement();
        }

        $this->writer->endElement();
    }
}
