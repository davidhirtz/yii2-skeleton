<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\UserCredential;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class UserCredentialTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheSecretIsStoredEncryptedAndFoundByItsOwner(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $record = UserCredential::store($owner->id, 'acme', ' sk-Grüße-東京-🎉-a3f9 ');

        self::assertFalse($record->hasErrors());
        self::assertStringNotContainsString('a3f9', $record->secret);
        self::assertSame('…a3f9', $record->hint);

        self::assertSame('sk-Grüße-東京-🎉-a3f9', UserCredential::findSecret($owner->id, 'acme'));
        self::assertNotNull(UserCredential::findOne($record->id)?->last_used_at);

        self::assertNull(UserCredential::findSecret($owner->id, 'other'));
        self::assertNull(UserCredential::findSecret($this->getUserFromFixture('admin')->id, 'acme'));
    }

    public function testANewKeyReplacesTheOld(): void
    {
        $owner = $this->getUserFromFixture('owner');

        $first = UserCredential::store($owner->id, 'acme', 'first-key');
        $second = UserCredential::store($owner->id, 'acme', 'second-key');

        self::assertSame($first->id, $second->id);
        self::assertSame('second-key', UserCredential::findSecret($owner->id, 'acme'));
    }

    public function testAPasswordChangeKeepsTheKey(): void
    {
        $owner = $this->getUserFromFixture('owner');
        UserCredential::store($owner->id, 'acme', 'kept-key');

        $owner->afterPasswordChange();

        self::assertSame('kept-key', UserCredential::findSecret($owner->id, 'acme'));
    }
}
