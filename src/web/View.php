<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\modules\admin\helpers\Html;
use yii\helpers\Json;
use Yii;
use yii\helpers\Url;

/**
 * @property string $description {@see static::setMetaDescription()}
 */
class View extends \yii\web\View
{
    public const HREF_LANG_KEY = 'hreflang_';
    public const CANONICAL_KEY = 'canonical';
    public const DESCRIPTION_KEY = 'description';

    public ?string $titleTemplate = null;
    private array $_breadcrumbs = [];
    private string|array|null $_description = null;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDocumentTitle(): string
    {
        if (!$this->titleTemplate) {
            return $this->title ?: Yii::$app->name;
        }

        return strtr($this->titleTemplate, ['{title}' => $this->title, '{app}' => Yii::$app->name]);
    }

    public function setMetaDescription(string $description, bool $replace = true): void
    {
        if (empty($this->metaTags[static::DESCRIPTION_KEY]) || $replace) {
            $this->_description = preg_replace("/\n+/", ' ', Html::encode($description));
            $this->registerMetaTag(['name' => 'description', 'content' => $this->_description], static::DESCRIPTION_KEY);
        }
    }

    public function getMetaDescription(): string
    {
        return $this->_description;
    }

    public function setBreadcrumbs(array $breadcrumbs): void
    {
        foreach ($breadcrumbs as $key => $value) {
            if (!is_numeric($key)) {
                $this->setBreadcrumb($key, $value);
            } else {
                $this->setBreadcrumb($value);
            }
        }
    }

    public function setBreadcrumb(?string $label, array|string $url = null): void
    {
        if ($label) {
            $this->_breadcrumbs[] = ['label' => $label, 'url' => $url];
        }
    }

    public function getBreadcrumbs(): array
    {
        return $this->_breadcrumbs;
    }

    public function registerTwitterCardMetaTags(string $card = 'summary_large_image', ?string $title = null, ?string $description = null): void
    {
        $this->registerMetaTag(['name' => 'twitter:card', 'content' => $card], 'twitter:card');
        $this->registerMetaTag([
            'name' => 'twitter:title',
            'content' => $title ?: $this->getDocumentTitle()
        ], 'twitter:title');
        $this->registerMetaTag([
            'name' => 'twitter:description',
            'content' => $description ?: $this->getMetaDescription()
        ], 'twitter:description');

        if (!empty(Yii::$app->params['twitter.siteName'])) {
            $this->registerMetaTag([
                'name' => 'twitter:site',
                'content' => Yii::$app->params['twitter.siteName']
            ], 'twitter:site');
        }
    }

    public function registerOpenGraphMetaTags(string $type = 'website', ?string $title = null, ?string $description = null): void
    {
        $this->registerMetaTag(['name' => 'og:title', 'content' => $title ?: $this->getDocumentTitle()], 'og:title');
        $this->registerMetaTag(['name' => 'og:description', 'content' => $description ?: $this->getMetaDescription()], 'og:description');

        if ($type) {
            $this->registerMetaTag(['name' => 'og:type', 'content' => $type], 'og:type');
        }
    }

    public function registerImageMetaTags(string $url, ?int $width = null, ?int $height = null, ?string $text = null): void
    {
        $url = Url::to($url, true);

        $this->registerMetaTag(['property' => 'og:image', 'content' => $url]);

        if ($width) {
            $this->registerMetaTag(['property' => 'og:image:width', 'content' => $width]);
        }

        if ($height) {
            $this->registerMetaTag(['property' => 'og:image:height', 'content' => $height]);
        }

        if ($text) {
            $this->registerMetaTag(['property' => 'twitter:image:alt', 'content' => $text]);
        }

        $this->registerMetaTag(['name' => 'twitter:image', 'content' => $url]);
        $this->registerLinkTag(['rel' => 'image_src', 'href' => $url]);
    }

    public function registerStructuredData(array $data): void
    {
        echo Html::script(Json::htmlEncode(['@context' => 'https://schema.org', ...$data]), [
            'type' => 'application/ld+json',
        ]);
    }

    /**
     * @param array $links can either be an array containing "name" and "item" as a key and value or an associative array.
     * @noinspection PhpUnused
     */
    public function registerStructuredDataBreadcrumbs(array $links): void
    {
        $items = [];
        $pos = 1;

        foreach ($links as $name => $item) {
            if (!isset($item['item'])) {
                $item = [
                    'name' => $name,
                    'item' => $item,
                ];
            }

            $item['item'] = Url::to($item['item'], true);
            $items[] = array_merge(['@type' => 'ListItem', 'position' => $pos++], $item);
        }

        if ($items) {
            $this->registerStructuredData(['@type' => 'BreadcrumbList', 'itemListElement' => $items]);
        }
    }

    /** @noinspection PhpUnused */
    public function registerHrefLangLinkTags(array $languages = [], ?string $default = null): void
    {
        if (!$languages) {
            $languages = Yii::$app->getUrlManager()->languages;
        }

        foreach ($languages as $language) {
            $this->registerHrefLangLinkTag($language, Url::current(['language' => $language], true));
        }

        if ($default !== false) {
            $this->registerDefaultHrefLangLinkTag($default);
        }
    }

    public function registerHrefLangLinkTag(string $language, string $url): void
    {
        $this->registerLinkTag(['rel' => 'alternate', 'hreflang' => $language, 'href' => $url], static::HREF_LANG_KEY . $language);
    }

    public function registerCanonicalTag(string $url): void
    {
        $this->registerLinkTag(['rel' => 'canonical', 'href' => $url], static::CANONICAL_KEY);
    }

    public function registerDefaultHrefLangLinkTag(?string $language = null): void
    {
        if (!$language) {
            $language = Yii::$app->sourceLanguage;
        }

        if (isset($this->linkTags[static::HREF_LANG_KEY . $language])) {
            $this->linkTags[static::HREF_LANG_KEY . 'default'] = str_replace('hreflang="' . $language . '"', 'hreflang="x-default"', (string)$this->linkTags[static::HREF_LANG_KEY . $language]);
        }
    }

    public function getFilenameWithVersion(string $filename): string
    {
        return "/$filename?" . filemtime(Yii::getAlias('@webroot/' . $filename));
    }

    public function getHtmlLangAttribute(): string
    {
        return match (Yii::$app->language) {
            'zh-TW' => 'zh-Hant',
            'zh-CN' => 'zh-Hans',
            default => substr(Yii::$app->language, 0, 2),
        };
    }
}