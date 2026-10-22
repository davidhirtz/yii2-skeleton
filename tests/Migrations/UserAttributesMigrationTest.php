<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use RuntimeException;
use Yii;
use yii\db\Command;
use yii\db\Query;

/**
 * The DDL commits the test transaction: the fixture rows are unloaded by hand, the columns dropped in `tearDown()`.
 */
class UserAttributesMigrationTest extends TestCase
{
    use UpgradeMigrationTrait;
    use UserFixtureTrait;

    private const string MIGRATION = 'Hirtz\\Skeleton\\Migrations\\M260913100000UserAttributes';

    private const array LEGACY_COLUMNS = [
        'first_name' => 'varchar(50) NULL',
        'last_name' => 'varchar(50) NULL',
        'birthdate' => 'date NULL',
        'city' => 'varchar(50) NULL',
        'country' => 'varchar(2) NULL',
        'picture' => 'varchar(50) NULL',
    ];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireUpgradeMigration('yii2-skeleton', self::MIGRATION);

        foreach (self::LEGACY_COLUMNS as $column => $type) {
            Yii::$app->getDb()->createCommand()
                ->addColumn(User::tableName(), $column, $type)
                ->execute();
        }

        Yii::$app->getDb()->createCommand()
            ->update(User::tableName(), [
                'first_name' => 'Åsa',
                'last_name' => "Mu\u{308}ller 😀",
                'birthdate' => '1990-02-03',
                'city' => 'Москва',
                'country' => 'SE',
                'picture' => 'portrait.jpg',
            ], ['id' => 1])
            ->execute();
    }

    #[Override]
    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();
        $schema = $db->getTableSchema(User::tableName(), true);

        foreach (array_keys(self::LEGACY_COLUMNS) as $column) {
            if ($schema?->getColumn($column)) {
                $db->createCommand()
                    ->dropColumn(User::tableName(), $column)
                    ->execute();
            }
        }

        InterruptedCommand::$drops = 0;

        parent::tearDown();
    }

    public function testTheProfileColumnsBecomeCustomAttributes(): void
    {
        $this->migrate();

        self::assertSame([
            'birthdate' => '1990-02-03',
            'city' => 'Москва',
            'country' => 'SE',
            'first_name' => 'Åsa',
            'last_name' => "Mu\u{308}ller 😀",
        ], self::findCustomAttributes(1));

        self::assertNull(self::findCustomAttributes(2));

        $schema = Yii::$app->getDb()->getTableSchema(User::tableName(), true);

        foreach (array_keys(self::LEGACY_COLUMNS) as $column) {
            self::assertNull($schema?->getColumn($column), $column);
        }
    }

    public function testARunInterruptedBetweenTwoDropsLosesNothing(): void
    {
        $db = clone Yii::$app->getDb();
        $db->commandClass = InterruptedCommand::class;

        try {
            $this->migrate(['db' => $db]);
            self::fail('The migration was not interrupted.');
        } catch (RuntimeException) {
        }

        $db->close();
        $this->migrate();

        self::assertSame('Åsa', self::findCustomAttributes(1)['first_name'] ?? null);
        self::assertSame('Москва', self::findCustomAttributes(1)['city'] ?? null);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function migrate(array $config = []): void
    {
        $this->createUpgradeMigration('yii2-skeleton', self::MIGRATION, ['compact' => true, ...$config])->safeUp();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findCustomAttributes(int $id): ?array
    {
        $value = (new Query())
            ->select(['custom_attributes'])
            ->from(User::tableName())
            ->where(['id' => $id])
            ->scalar(Yii::$app->getDb());

        if (!is_string($value)) {
            return null;
        }

        $values = json_decode($value, true);
        self::assertIsArray($values);
        ksort($values);

        return $values;
    }
}

final class InterruptedCommand extends Command
{
    public static int $drops = 0;

    #[Override]
    public function execute()
    {
        if (str_contains($this->getRawSql(), 'DROP COLUMN') && ++self::$drops === 2) {
            throw new RuntimeException('Interrupted.');
        }

        return parent::execute();
    }
}
