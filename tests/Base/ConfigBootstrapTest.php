<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Base;

use Hirtz\Skeleton\Base\ConfigBootstrapInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\Component;

class ConfigBootstrapTest extends TestCase
{
    public function testTheComponentIsRegisteredByTheExtension(): void
    {
        $this->reloadWith();

        $component = Yii::$app->get(TestConfigBootstrap::ID);

        self::assertInstanceOf(TestBootstrapComponent::class, $component);
        self::assertSame('bundle', $component->value);
    }

    /**
     * #325: without a class, `ServiceLocator::set()` threw while the application was built, before a `Bootstrap`
     * could add it.
     */
    public function testAnApplicationConfiguresTheComponentWithoutItsClass(): void
    {
        $this->reloadWith([
            'components' => [
                TestConfigBootstrap::ID => ['value' => 'application'],
            ],
        ]);

        $component = Yii::$app->get(TestConfigBootstrap::ID);

        self::assertInstanceOf(TestBootstrapComponent::class, $component);
        self::assertSame('application', $component->value);
    }

    public function testTheApplicationsClassWins(): void
    {
        $this->reloadWith([
            'components' => [
                TestConfigBootstrap::ID => ['class' => TestApplicationComponent::class],
            ],
        ]);

        $component = Yii::$app->get(TestConfigBootstrap::ID);

        self::assertInstanceOf(TestApplicationComponent::class, $component);
        self::assertSame('bundle', $component->value);
    }

    public function testContainerDefinitionsAndParamsAreDefaults(): void
    {
        $this->reloadWith([
            'container' => [
                'definitions' => [
                    TestApplicationComponent::class => ['value' => 'application'],
                ],
            ],
            'params' => [
                'configBootstrapTest' => 'application',
            ],
        ]);

        self::assertSame('bundle', Yii::createObject(TestBootstrapComponent::class)->value);
        self::assertSame('application', Yii::createObject(TestApplicationComponent::class)->value);
        self::assertSame('application', Yii::$app->params['configBootstrapTest']);
    }

    public function testABundleOverridesTheCoreAndListsAppend(): void
    {
        $this->reloadWith();

        self::assertSame('_bundle', $this->getWebSession()->getName());

        $search = Yii::$app->get('search');
        self::assertInstanceOf(Search::class, $search);

        $models = $search->models;

        self::assertContains(User::class, $models);
        self::assertContains(TestApplicationComponent::class, $models);
    }

    public function testABootstrapOfTheConfigurationCounts(): void
    {
        $this->config['bootstrap'] = [TestConfigBootstrap::class];
        $this->config['components'][TestConfigBootstrap::ID] = ['value' => 'application'];
        $this->reloadApplication();

        self::assertInstanceOf(TestBootstrapComponent::class, Yii::$app->get(TestConfigBootstrap::ID));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function reloadWith(array $config = []): void
    {
        $this->config = [
            ...$this->config,
            ...$config,
            'components' => [...$this->config['components'] ?? [], ...$config['components'] ?? []],
            'extensions' => [
                ...Yii::$app->extensions,
                'test/config-bootstrap' => [
                    'name' => 'test/config-bootstrap',
                    'version' => '1.0.0',
                    'bootstrap' => TestConfigBootstrap::class,
                ],
            ],
        ];

        $this->reloadApplication();
    }
}

class TestConfigBootstrap implements ConfigBootstrapInterface
{
    public const string ID = 'configBootstrapTest';

    #[Override]
    public static function getDefaultConfig(): array
    {
        return [
            'components' => [
                self::ID => [
                    'class' => TestBootstrapComponent::class,
                    'value' => 'bundle',
                ],
                'search' => [
                    'models' => [TestApplicationComponent::class],
                ],
                'session' => [
                    'name' => '_bundle',
                ],
            ],
            'container' => [
                'definitions' => [
                    TestBootstrapComponent::class => ['value' => 'bundle'],
                    TestApplicationComponent::class => ['value' => 'bundle'],
                ],
            ],
            'params' => [
                'configBootstrapTest' => 'bundle',
            ],
        ];
    }

    #[Override]
    public function bootstrap($app): void
    {
    }
}

class TestBootstrapComponent extends Component
{
    public string $value = '';
}

class TestApplicationComponent extends TestBootstrapComponent
{
}
