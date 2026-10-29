<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\db\Migration;

class MigrationTraitMessageKeysTest extends TestCase
{
    public function testStoredPointersAreRenamedAndKeepTheirParams(): void
    {
        $renamed = Trail::createOrderTrail(null, Message::make('test', 'OLD_KEY', ['name' => 'Müller 🙂']));
        $longer = Trail::createOrderTrail(null, Message::make('test', 'OLD_KEY_LONGER'));
        $other = Trail::createOrderTrail(null, Message::make('other', 'OLD_KEY'));

        $migration = new TestMessageKeysMigration(['compact' => true]);
        $migration->addPermission('testMessageKeys', Message::make('test', 'OLD_KEY'));
        $migration->renameMessageKeys('test', ['OLD_KEY' => 'NEW_KEY']);

        self::assertSame('{"category":"test","key":"NEW_KEY","params":{"name":"Müller 🙂"}}', $this->getMessage($renamed));
        self::assertSame('{"category":"test","key":"OLD_KEY_LONGER"}', $this->getMessage($longer));
        self::assertSame('{"category":"other","key":"OLD_KEY"}', $this->getMessage($other));

        $auth = Yii::$app->getAuthManager();
        $auth->invalidateCache();

        self::assertSame('{"category":"test","key":"NEW_KEY"}', $auth->getPermission('testMessageKeys')?->description);
    }

    private function getMessage(Trail $trail): ?string
    {
        return Trail::findOne($trail->id)?->message;
    }
}

class TestMessageKeysMigration extends Migration
{
    use MigrationTrait {
        addPermission as public;
        renameMessageKeys as public;
    }
}
