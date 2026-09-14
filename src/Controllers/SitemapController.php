<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Controllers;

use DateTimeInterface;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Web\Controller;
use Override;
use XMLWriter;
use Yii;
use yii\filters\PageCache;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SitemapController extends Controller
{
    /**
     * @see https://www.sitemaps.org/protocol.html
     */
    private const string XML_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private const string IMAGE_XML_NAMESPACE = 'http://www.google.com/schemas/sitemap-image/1.1';

    #[Override]
    public function init(): void
    {
        if (!Yii::$app->has('sitemap')) {
            throw new NotFoundHttpException();
        }

        parent::init();
    }

    #[Override]
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $sitemap = $this->getSitemap();

        if ($sitemap->cache) {
            $variations = $sitemap->getVariations();

            if ($sitemap->useSitemapIndex) {
                $variations[] = (string)$this->request->get('key');
                $variations[] = (string)$this->request->get('offset');
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

    #[Override]
    public function beforeAction($action): bool
    {
        if ($this->request->isDraftRequest()) {
            $this->redirect(Yii::$app->getUrlManager()->getHostInfo() . $this->request->getUrl())->send();
            return false;
        }

        return parent::beforeAction($action);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionIndex(?string $key = null, int $offset = 0): string
    {
        $sitemap = $this->getSitemap();
        $isIndex = $sitemap->useSitemapIndex && $key === null;

        if ($sitemap->useSitemapIndex && !$isIndex) {
            $requested = $sitemap->getSitemap($key);

            if (!$requested || $offset < 0 || $offset >= $requested->getPageCount()) {
                throw new NotFoundHttpException();
            }
        }

        $urls = $isIndex ? $sitemap->generateIndexUrls() : $sitemap->generateUrls($key, $offset);

        $this->response->format = Response::FORMAT_RAW;
        $this->response->getHeaders()->add('Content-Type', 'application/xml');

        ob_start();
        ob_implicit_flush(false);

        $writer = new XMLWriter();
        $writer->openUri('php://output');
        $writer->startDocument('1.0', 'UTF-8');

        $this->writeUrlset($writer, $urls, $isIndex);

        $writer->endDocument();
        $writer->flush();

        return (string)ob_get_clean();
    }

    private function writeUrlset(XMLWriter $writer, array $urls, bool $isIndex = false): void
    {
        $writer->startElement($isIndex ? 'sitemapindex' : 'urlset');
        $writer->writeAttribute('xmlns', self::XML_NAMESPACE);

        // XMLWriter drops an attribute written after the first child, so the image namespace cannot wait for the
        // first URL that carries images
        if ($this->hasImages($urls)) {
            $writer->writeAttribute('xmlns:image', self::IMAGE_XML_NAMESPACE);
        }

        foreach ($urls as $url) {
            $writer->startElement($isIndex ? 'sitemap' : 'url');
            $writer->writeElement('loc', Url::to(is_array($url) ? $url['loc'] : $url, true));

            foreach (['lastmod', 'changefreq', 'priority'] as $element) {
                $value = is_array($url) ? ($url[$element] ?? null) : null;

                if ($value !== null && $value !== '') {
                    $writer->writeElement($element, $this->formatValue($value));
                }
            }

            foreach ((is_array($url) ? $url['images'] ?? [] : []) as $image) {
                $writer->startElement('image:image');
                $writer->writeElement('image:loc', Url::to(is_array($image) ? $image['loc'] : $image, true));

                foreach (['caption', 'geo_location', 'license', 'title'] as $element) {
                    if (!empty($image[$element])) {
                        $writer->writeElement('image:' . $element, (string)$image[$element]);
                    }
                }

                $writer->endElement();
            }

            $writer->endElement();
        }

        $writer->endElement();
    }

    private function hasImages(array $urls): bool
    {
        foreach ($urls as $url) {
            if (is_array($url) && !empty($url['images'])) {
                return true;
            }
        }

        return false;
    }

    private function formatValue(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_W3C) : (string)$value;
    }

    private function getSitemap(): Sitemap
    {
        return Sitemap::getComponent();
    }
}
