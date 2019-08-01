<?php

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\web\Controller;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * Class SitemapController.
 * @package davidhirtz\yii2\skeleton\controllers
 */
class SitemapController extends Controller
{
    /**
     * Makes sure sitemap is installed.
     */
    public function init()
    {
        if (!Yii::$app->has('sitemap')) {
            throw new NotFoundHttpException;
        }

        return parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        if (Yii::$app->sitemap->cache) {
            $sitemap = Yii::$app->sitemap;
            $behaviors[] = [
                'class' => 'yii\filters\PageCache',
                'only' => ['index'],
                'cache' => $sitemap->cache,
                'duration' => $sitemap->duration,
                'dependency' => $sitemap->dependency,
                'variations' => is_callable($sitemap->variations) ? call_user_func($sitemap->variations) : $sitemap->variations,
            ];
        }

        return $behaviors;
    }

    /**
     * Renders XML site map.
     */
    public function actionIndex()
    {
        $response = Yii::$app->getResponse();
        $response->format = $response::FORMAT_RAW;

        ob_start();
        ob_implicit_flush(false);

        try {
            $headers = $response->getHeaders();
            $headers->add('Content-Type', 'application/xml');

            /** @noinspection PhpComposerExtensionStubsInspection */
            $writer = new \XMLWriter();
            $writer->openURI('php://output');
            $writer->startDocument('1.0', 'UTF-8');

            $writer->startElement('urlset');
            $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            foreach (Yii::$app->sitemap->generateUrls() as $url) {
                if (isset($url['images'])) {
                    $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
                }

                $writer->startElement('url');
                $writer->writeElement('loc', Url::to(is_array($url) ? $url['loc'] : $url, true));

                if (isset($url['lastmod'])) {
                    $lastmod = $url['lastmod'];
                    $writer->writeElement('lastmod', $lastmod instanceof \DateTime ? $lastmod->format(DATE_W3C) : $lastmod);
                }

                if (isset($url['changefreq'])) {
                    $writer->writeElement('changefreq', $url['changefreq']);
                }

                if (isset($url['priority'])) {
                    $writer->writeElement('priority', $url['priority']);
                }

                if (isset($url['images'])) {
                    foreach ($url['images'] as $image) {
                        $writer->startElement('image:image');
                        $writer->writeElement('image:loc', Url::to(is_array($image) ? $image['loc'] : $image, true));

                        foreach (['caption', 'geo_location', 'license', 'title'] as $element) {
                            if (isset($image[$element])) {
                                $writer->writeElement('image:' . $element, $image[$element]);
                            }
                        }

                        $writer->endElement();
                    }
                }

                $writer->endElement();
            }

            $writer->endElement();
            $writer->endDocument();
            $writer->flush();
        } catch (\Exception $exception) {
            throw $exception;
        }

        return ob_get_clean();
    }
}