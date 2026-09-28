<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\UrlManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;
use yii\web\BadRequestHttpException;

class AllowedHostsTest extends TestCase
{
    use UserFixtureTrait;

    public function testAnEmptyListAllowsAnyHost(): void
    {
        self::assertTrue($this->getRequest('https://www.attacker.com')->isAllowedHost());
    }

    #[DataProvider('hostDataProvider')]
    public function testTheHostIsMatchedAgainstTheList(string $host, bool $allowed): void
    {
        $request = $this->getRequest("https://$host", ['www.example.com', '*.example.org']);

        self::assertSame($allowed, $request->isAllowedHost());
    }

    /**
     * @return list<array{string, bool}>
     */
    public static function hostDataProvider(): array
    {
        return [
            ['www.example.com', true],
            ['example.com', false],
            ['draft.example.org', true],
            ['www.attacker.com', false],
            ['www.example.com.attacker.com', false],
            // a local host never reaches a mailbox anyone else reads
            ['www.test.localhost', true],
        ];
    }

    public function testTheListDefaultsToTheParams(): void
    {
        $this->config['params']['allowedHosts'] = ['www.example.com'];
        $this->reloadApplication();

        self::assertSame(['www.example.com'], $this->getWebRequest()->allowedHosts);
    }

    public function testAddedHostsAreKeptOnce(): void
    {
        $request = $this->getRequest('https://www.example.com', ['www.example.com']);
        $request->addAllowedHosts('www.example.com', 'www.example.org');

        self::assertSame(['www.example.com', 'www.example.org'], $request->allowedHosts);
    }

    public function testAnUnknownHostIsRefusedBeforeRouting(): void
    {
        $request = $this->getRequest('https://www.attacker.com', ['www.example.com']);

        $this->expectException(BadRequestHttpException::class);
        Yii::$app->handleRequest($request);
    }

    public function testAConfiguredHostSurvivesTheRequest(): void
    {
        $manager = $this->getUrlManager(['hostInfo' => 'https://www.example.com']);
        $manager->parseRequest($this->getRequest('https://www.attacker.com'));

        self::assertSame('https://www.example.com', $manager->getHostInfo());
        self::assertFalse($manager->isHostInfoFromRequest());
    }

    public function testWithoutAConfiguredHostTheRequestsIsUsed(): void
    {
        $manager = $this->getUrlManager();
        $manager->parseRequest($this->getRequest('https://www.example.com'));

        self::assertSame('https://www.example.com', $manager->getHostInfo());
        self::assertTrue($manager->isHostInfoFromRequest());

        $manager->setHostInfo('https://www.example.org');

        self::assertFalse($manager->isHostInfoFromRequest());
    }

    public function testAPasswordResetLinkNamesTheConfiguredHost(): void
    {
        $manager = $this->getUrlManager(['hostInfo' => 'https://www.example.com']);
        $manager->parseRequest($this->getRequest('https://www.attacker.com'));

        $user = User::findOne($this->getUserFixture()->data['admin']['id']);
        self::assertInstanceOf(User::class, $user);

        self::assertStringStartsWith('https://www.example.com/', $user->createPasswordResetUrl());
    }

    /**
     * @param list<string> $allowedHosts
     */
    private function getRequest(string $hostInfo, array $allowedHosts = []): Request
    {
        Yii::$app->set('request', [
            'class' => Request::class,
            'allowedHosts' => $allowedHosts,
            'hostInfo' => $hostInfo,
        ]);

        return $this->getWebRequest();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function getUrlManager(array $config = []): UrlManager
    {
        // The tenant bundle points the container at its own subclass, which pins the host of the tenant it resolves.
        Yii::$container->clear(UrlManager::class);

        Yii::$app->set('urlManager', [
            'class' => UrlManager::class,
            ...$config,
        ]);

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);

        return $manager;
    }
}
