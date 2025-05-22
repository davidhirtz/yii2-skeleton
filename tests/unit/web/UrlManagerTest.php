<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\web;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\web\UrlManager;
use Yii;

class UrlManagerTest extends Unit
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

    public function testI18nSubdomain(): void
    {
        $manager = $this->getUrlManager([
            'i18nSubdomain' => true,
            'languages' => ['en-US', 'de'],
        ]);

        self::assertEquals('.example.com', $manager->getI18nHostInfo());

        $manager->setHostInfo('https://de.example.com');
        Yii::$app->language = 'de';

        self::assertEquals('.example.com', $manager->getI18nHostInfo());

        $url = $manager->createAbsoluteUrl(['test']);
        self::assertEquals('https://de.example.com/test', $url);

        $url = $manager->createDraftUrl(['test']);
        self::assertEquals('https://draft.de.example.com/test', $url);

        $url = $manager->createAbsoluteUrl(['test', 'language' => 'en-US']);
        self::assertEquals('https://www.example.com/test', $url);

        $url = $manager->createDraftUrl(['test', 'language' => 'en-US']);
        self::assertEquals('https://draft.example.com/test', $url);

        $manager->setHostInfo('https://www.example.com');
        Yii::$app->language = 'en-US';

        codecept_debug($manager->getHostInfo());

        $url = $manager->createAbsoluteUrl(['test', 'language' => 'de']);
        self::assertEquals('https://de.example.com/test', $url);
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

    protected function getUrlManager($config = []): UrlManager
    {
        Yii::$app->set('urlManager', [
            'class' => UrlManager::class,
            ...$config,
        ]);

        return Yii::$app->getUrlManager();
    }
}
