<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Setup;

use Hirtz\Skeleton\Setup\Seeder;
use Hirtz\Skeleton\Setup\Setup;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use yii\base\InvalidConfigException;

class SetupTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        SetupTestSeeder::$runs = [];
    }

    public function testSeedersRunAfterTheirDependencies(): void
    {
        $setup = $this->createSetup([
            'c' => ['class' => SetupTestSeeder::class, 'dependencies' => ['b', 'missing']],
            'a' => SetupTestSeeder::class,
            'b' => ['class' => SetupTestSeeder::class, 'dependencies' => ['a']],
        ]);

        $setup->run(['a' => ['x'], 'c' => fn (): array => ['ran after ' . implode(', ', array_keys(SetupTestSeeder::$runs))]]);

        self::assertSame([
            'a' => ['x'],
            'b' => null,
            'c' => ['ran after a, b'],
        ], SetupTestSeeder::$runs);
    }

    public function testANullSeederIsDropped(): void
    {
        $setup = $this->createSetup(['a' => SetupTestSeeder::class, 'b' => null]);

        self::assertSame(['b'], $setup->getUnknownKeys(['a' => [], 'b' => []]));

        $setup->run([]);
        self::assertSame(['a'], array_keys(SetupTestSeeder::$runs));
    }

    public function testACycleIsRefused(): void
    {
        $setup = $this->createSetup([
            'a' => ['class' => SetupTestSeeder::class, 'dependencies' => ['b']],
            'b' => ['class' => SetupTestSeeder::class, 'dependencies' => ['a']],
        ]);

        $this->expectException(InvalidConfigException::class);
        $setup->run([]);
    }

    public function testAValueThatIsNoArrayIsRefused(): void
    {
        $setup = $this->createSetup(['a' => SetupTestSeeder::class]);

        $this->expectException(InvalidConfigException::class);
        $setup->run(['a' => 'x']);
    }

    /**
     * @param array<string, class-string<Seeder>|array{class: class-string<Seeder>, dependencies?: list<string>}|null> $seeders
     */
    private function createSetup(array $seeders): Setup
    {
        $setup = Setup::getComponent();
        $setup->seeders = [];

        foreach ($seeders as $key => $seeder) {
            $setup->seeders[$key] = $seeder === null ? null : [
                ...(is_string($seeder) ? ['class' => $seeder] : $seeder),
                'key' => $key,
            ];
        }

        return $setup;
    }
}

class SetupTestSeeder extends Seeder
{
    /**
     * @var array<string, array<array-key, mixed>|null>
     */
    public static array $runs = [];

    public string $key = '';

    #[Override]
    public function run(?array $value): void
    {
        self::$runs[$this->key] = $value;
    }
}
