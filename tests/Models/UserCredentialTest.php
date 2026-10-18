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
        $record = UserCredential::store($owner->id, 'acme', ['key' => ' sk-Grüße-東京-🎉-a3f9 ']);

        self::assertFalse($record->hasErrors());
        self::assertStringNotContainsString('a3f9', $record->secret);
        self::assertSame('…a3f9', $record->hint);

        self::assertSame(['key' => 'sk-Grüße-東京-🎉-a3f9'], UserCredential::findValues($owner->id, 'acme'));
        self::assertNotNull(UserCredential::findOne($record->id)?->last_used_at);

        self::assertNull(UserCredential::findValues($owner->id, 'other'));
        self::assertNull(UserCredential::findValues($this->getUserFromFixture('admin')->id, 'acme'));
    }

    public function testANewKeyReplacesTheOld(): void
    {
        $owner = $this->getUserFromFixture('owner');

        $first = UserCredential::store($owner->id, 'acme', ['key' => 'first-key']);
        $second = UserCredential::store($owner->id, 'acme', ['key' => 'second-key']);

        self::assertSame($first->id, $second->id);
        self::assertSame(['key' => 'second-key'], UserCredential::findValues($owner->id, 'acme'));
    }

    public function testSeveralValuesAreStoredTogetherAndHintedByTheLast(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $record = UserCredential::store($owner->id, 'firefly', ['client_id' => 'Grüße-id', 'client_secret' => 'geheim-1234']);

        self::assertSame('…1234', $record->hint);
        self::assertSame(['client_id' => 'Grüße-id', 'client_secret' => 'geheim-1234'], UserCredential::findValues($owner->id, 'firefly'));
    }

    public function testAPasswordChangeKeepsTheKey(): void
    {
        $owner = $this->getUserFromFixture('owner');
        UserCredential::store($owner->id, 'acme', ['key' => 'kept-key']);

        $owner->afterPasswordChange();

        self::assertSame(['key' => 'kept-key'], UserCredential::findValues($owner->id, 'acme'));
    }
}
