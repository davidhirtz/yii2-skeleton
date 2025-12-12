<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\UrlManager;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Yii;
use yii\web\UrlNormalizerRedirectException;
use yii\web\UrlRule;

class UrlManagerTest extends TestCase
{
    public function testCreateUrl(): void
    {
        $manager = $this->getUrlManager();

        $url = $manager->createUrl('post/view');
        self::assertEquals('/post/view', $url);

        $url = $manager->createUrl(['post/view']);
        self::assertEquals('/post/view', $url);

        $manager = $this->getUrlManager([
            'baseUrl' => '/test/',
            'scriptUrl' => '/test',
            'enablePrettyUrl' => false,
        ]);

        $url = $manager->createUrl('post/view');
        self::assertEquals('/test?r=post%2Fview', $url);

        $url = $manager->createUrl(['post/view']);
        self::assertEquals('/test?r=post%2Fview', $url);
    }

    public function testCreateUrlWithParams(): void
    {
        $manager = $this->getUrlManager();

        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        self::assertEquals('/post/view?id=1&title=sample+post', $url);

        $manager = $this->getUrlManager([
            'baseUrl' => '/test/',
            'scriptUrl' => '/test',
        ]);

        $url = $manager->createUrl(['post/view', 'id' => 1, 'title' => 'sample post']);
        self::assertEquals('/test/post/view?id=1&title=sample+post', $url);
    }

    public function testCreateAbsoluteUrl(): void
    {
        $manager = $this->getUrlManager();

        $url = $manager->createAbsoluteUrl('post/view');
        self::assertEquals('https://www.example.com/post/view', $url);

        $url = $manager->createAbsoluteUrl(['post/view'], '');
        self::assertEquals('//www.example.com/post/view', $url);
    }

    public function testCreateDraftUrl(): void
    {
        $manager = $this->getUrlManager();

        $url = $manager->createDraftUrl('post/view');
        self::assertEquals('https://draft.example.com/post/view', $url);

        $manager->draftSubdomain = 'preview';

        $url = $manager->createDraftUrl('post/view');
        self::assertEquals('https://preview.example.com/post/view', $url);

        Yii::$app->getRequest()->setIsDraft(true);
        $manager->draftSubdomain = false;

        $url = $manager->createDraftUrl('post/view');
        self::assertEquals('https://www.example.com/post/view', $url);
    }

    public function testI18nUrl(): void
    {
        $manager = $this->getUrlManager([
            'i18nUrl' => true,
            'languages' => [
                'en-US' => 'en',
                'de' => 'de',
            ],
        ]);

        self::assertEquals('https://www.example.com', $manager->getHostInfo());

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/de',
        ]);

        $manager->parseRequest($request);

        self::assertEquals('https://www.example.com', $manager->getHostInfo());
        self::assertEquals('de', Yii::$app->language);

        $url = $manager->createAbsoluteUrl(['test']);
        self::assertEquals('https://www.example.com/de/test', $url);

        $url = $manager->createDraftUrl(['test']);
        self::assertEquals('https://draft.example.com/de/test', $url);

        $url = $manager->createAbsoluteUrl(['test', 'language' => 'en-US']);
        self::assertEquals('https://www.example.com/test', $url);

        $url = $manager->createDraftUrl(['test', 'language' => 'en-US']);
        self::assertEquals('https://draft.example.com/test', $url);

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/en/test',
        ]);

        try {
            $manager->parseRequest($request);
            self::fail('UrlNormalizerRedirectException not thrown');
        } catch (UrlNormalizerRedirectException $e) {
            self::assertEquals('https://www.example.com/test', $e->url);
        }

        $url = $manager->createAbsoluteUrl(['test', 'language' => 'de']);
        self::assertEquals('https://www.example.com/de/test', $url);

        $request = $this->getRequest([
            'hostInfo' => 'https://draft.example.com',
            'url' => '/de',
        ]);

        $manager->parseRequest($request);

        self::assertEquals('https://example.com', $manager->getHostInfo());
        self::assertEquals('de', Yii::$app->language);
        self::assertTrue($request->getIsDraft());
    }

    public function testI18nSubdomain(): void
    {
        $manager = $this->getUrlManager([
            'i18nSubdomain' => true,
            'languages' => [
                'en-US' => 'www',
                'de' => 'de',
            ],
        ]);

        self::assertEquals('https://www.example.com', $manager->getHostInfo());

        $request = $this->getRequest([
            'hostInfo' => 'https://de.example.com',
            'url' => '/',
        ]);

        $manager->parseRequest($request);

        self::assertEquals('https://www.example.com', $manager->getHostInfo());
        self::assertEquals('de', Yii::$app->language);

        $url = $manager->createAbsoluteUrl(['test']);
        self::assertEquals('https://de.example.com/test', $url);

        $url = $manager->createDraftUrl(['test']);
        self::assertEquals('https://draft.de.example.com/test', $url);

        $url = $manager->createAbsoluteUrl(['test', 'language' => 'en-US']);
        self::assertEquals('https://www.example.com/test', $url);

        $url = $manager->createDraftUrl(['test', 'language' => 'en-US']);
        self::assertEquals('https://draft.example.com/test', $url);

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/',
        ]);

        $manager->parseRequest($request);

        $url = $manager->createAbsoluteUrl(['test', 'language' => 'de']);
        self::assertEquals('https://de.example.com/test', $url);

        $request = $this->getRequest([
            'hostInfo' => 'https://draft.de.example.com',
            'url' => '/',
        ]);

        $manager->parseRequest($request);

        self::assertEquals('https://www.example.com', $manager->getHostInfo());
        self::assertEquals('de', Yii::$app->language);
        self::assertTrue($request->getIsDraft());
    }

    public function testRedirectMap(): void
    {
        $manager = $this->getUrlManager([
            'redirectMap' => [
                'old-url' => 'https://www.new-domain.com/new-url',
                [
                    'request' => ['old/*'],
                    'url' => 'temp/',
                    'code' => 302,
                ],
            ],
        ]);

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/',
        ]);

        $manager->parseRequest($request);
        self::assertEquals('https://www.example.com', $manager->getHostInfo());

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/old-url',
        ]);

        try {
            $manager->parseRequest($request);
            self::fail('UrlNormalizerRedirectException not thrown');
        } catch (UrlNormalizerRedirectException $e) {
            self::assertEquals('https://www.new-domain.com/new-url', $e->url);
        }

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/old/test',
        ]);

        try {
            $manager->parseRequest($request);
            self::fail('UrlNormalizerRedirectException not thrown');
        } catch (UrlNormalizerRedirectException $e) {
            self::assertEquals('/temp/test', $e->url);
            self::assertEquals(302, $e->statusCode);
        }
    }

    public function testLanguageUrl(): void
    {
        $manager = $this->getUrlManager([
            'languages' => [
                'en-US' => 'en',
                'de' => 'de',
            ],
        ]);

        $request = $this->getRequest([
            'hostInfo' => 'https://www.example.com',
            'url' => '/',
            'bodyParams' => [
                'language' => 'de',
            ],
        ]);

        $manager->parseRequest($request);
        self::assertEquals('de', Yii::$app->language);
    }

    public function testBaseUrl(): void
    {
        $manager = $this->getUrlManager();

        $manager->setBaseUrl('example.de');
        self::assertEquals('example.de', $manager->getBaseUrl());

        Yii::setAlias('@testAlias', 'example.de/');

        $manager->setBaseUrl('@testAlias');
        self::assertEquals('example.de', $manager->getBaseUrl());
    }

    public function testImmutableRuleParams(): void
    {
        $manager = $this->getUrlManager([
            'rules' => [
                'index' => 'site/index',
                'view/<slug>' => 'site/view',
            ],
        ]);

        self::assertEquals(['index', 'view'], $manager->getImmutableRuleParams());

        $manager = $this->getUrlManager([
            'rules' => [
                '<type:(blog|archive)>/<slug>' => 'site/view',
            ],
        ]);

        self::assertEquals(['blog', 'archive'], $manager->getImmutableRuleParams());

        $manager = $this->getUrlManager([
            'rules' => [
                '<filter:new-posts|old_posts>/<slug>' => 'site/view',
            ],
        ]);

        self::assertEquals(['new-posts', 'old_posts'], $manager->getImmutableRuleParams());
    }

    public function testLanguages(): void
    {
        Yii::$app->getI18n()->setLanguages(['de', 'en-US']);
        $manager = $this->getUrlManager();

        self::assertEquals(['de' => 'de', 'en-US' => 'en'], $manager->languages);
    }

    public function testUrlRuleWithPosition(): void
    {
        $urlManager = $this->getUrlManager();

        $config = [
            'pattern' => 'first-position',
            'route' => 'site/index',
        ];

        $urlManager->addRules([
            [
                ...$config,
                'position' => 0,
            ],
        ]);

        $urlRule = new UrlRule($config);

        self::assertEquals($urlRule->route, $urlManager->rules[0]->route);
    }

    protected function getRequest($config = []): Request
    {
        Yii::$app->set('request', [
            'class' => Request::class,
            ...$config,
        ]);

        return Yii::$app->getRequest();
    }

    protected function getUrlManager($config = []): UrlManager
    {
        Yii::$app->set('urlManager', [
            'class' => UrlManager::class,
            ...$config,
        ]);

        return Yii::$app->getUrlManager();
    }
}
