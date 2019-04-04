<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Json;
use Yii;
use yii\helpers\Url;

/**
 * Class View
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property string $description
 */
class View extends \yii\web\View
{
    /**
     * @var string
     */
    public $titleTemplate;

    /**
     * @var array
     */
    private $_breadcrumbs = [];

    /**
     * @param $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        if (!$this->titleTemplate) {
            return $this->title ?: Yii::$app->name;
        }

        return strtr($this->titleTemplate, ['{title}' => $this->title, '{app}' => Yii::$app->name]);
    }

    /**
     * @param $description
     * @param bool $replace
     */
    public function setDescription($description, $replace = true)
    {
        if (empty($this->metaTags['description']) || $replace) {
            $description = preg_replace("/\n+/", " ", Html::encode($description));
            $this->registerMetaTag(['name' => 'description', 'content' => $description], 'description');
        }
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return isset($this->metaTags['description']) ? $this->metaTags['description'] : '';
    }

    /**
     * @param array $breadcrumbs
     */
    public function setBreadcrumbs($breadcrumbs)
    {
        foreach ($breadcrumbs as $key => $value) {
            if (!is_numeric($key)) {
                $this->setBreadcrumb($key, $value);
            } else {
                $this->setBreadcrumb($value);
            }
        }
    }

    /**
     * @param string $label
     * @param mixed $url
     */
    public function setBreadcrumb($label, $url = null)
    {
        $this->_breadcrumbs[] = ['label' => $label, 'url' => $url];
    }

    /**
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return $this->_breadcrumbs;
    }

    /**
     * @param string $card
     * @param string $title
     * @param string $description
     */
    public function registerTwitterCardMetaTags($card = 'summary_large_image', $title = null, $description = null)
    {
        $this->registerMetaTag(['name' => 'twitter:card', 'content' => $card], 'twitter:card');
        $this->registerMetaTag([
            'name' => 'twitter:title',
            'content' => $title ?: $this->getTitle()
        ], 'twitter:title');
        $this->registerMetaTag([
            'name' => 'twitter:description',
            'content' => $description ?: $this->getDescription()
        ], 'twitter:description');

        if (!empty(Yii::$app->params['twitter.siteName'])) {
            $this->registerMetaTag([
                'name' => 'twitter:site',
                'content' => Yii::$app->params['twitter.siteName']
            ], 'twitter:site');
        }
    }

    /**
     * @param string $type
     * @param string $title
     * @param string $description
     */
    public function registerOpenGraphMetaTags($type = 'website', $title = null, $description = null)
    {
        $this->registerMetaTag(['name' => 'og:title', 'content' => $title ?: $this->getTitle()], 'og:title');
        $this->registerMetaTag(['name' => 'og:description', 'content' => $description ?: $this->getDescription()], 'og:description');

        if ($type) {
            $this->registerMetaTag(['name' => 'og:type', 'content' => $type], 'og:type');
        }
    }

    /**
     * @param string $url
     * @param int $width
     * @param int $height
     * @param string $text
     */
    public function registerImageMetaTags($url, $width = null, $height = null, $text = null)
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

    /**
     * @param array $data
     */
    public function registerStructuredData($data)
    {
        echo Html::script(Json::htmlEncode(array_merge(['@context' => 'http://schema.org'], $data)), ['type' => 'application/ld+json']);
    }

    /**
     * @param array $links can either be an array containing "name" and "item" as key and value or an associative array.
     */
    public function registerStructuredDataBreadcrumbs($links)
    {
        $items = [];
        $pos = 1;

        foreach ($links as $url => $link) {
            if (!is_array($link)) {
                $link = [
                    'name' => $link,
                    'item' => $url,
                ];
            }

            $link['item'] = Url::to($link['item'], true);
            $items[] = array_merge(['@type' => 'ListItem', 'position' => $pos++], $link);
        }

        $this->registerStructuredData(['@type' => 'BreadcrumbList', 'itemListElement' => $items]);
    }

    /**
     * @return string
     */
    public static function getLanguage()
    {
        return substr(Yii::$app->language, 0, 2);
    }
}