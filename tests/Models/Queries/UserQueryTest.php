<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Queries;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class UserQueryTest extends TestCase
{
    use UserFixtureTrait;

    public function testAndWhereEmailIgnoresCase(): void
    {
        $user = User::find()
            ->andWhereEmail('OWNER@Domain.com')
            ->one();

        self::assertSame('owner@domain.com', $user?->email);
    }

    public function testAndWhereNameIgnoresCase(): void
    {
        $user = User::find()
            ->andWhereName('OWNER')
            ->one();

        self::assertSame('owner', $user?->name);
    }

    public function testMatchingASingleNumberLooksUpTheId(): void
    {
        $id = $this->getUserFromFixture('owner')->id;

        $users = User::find()
            ->matching((string)$id)
            ->all();

        self::assertCount(1, $users);
        self::assertNotFalse($user = reset($users));
        self::assertSame($id, $user->id);
    }

    public function testMatchingAnAddressSearchesTheEmail(): void
    {
        $users = User::find()
            ->matching('disabled@domain')
            ->all();

        self::assertCount(1, $users);
        self::assertNotFalse($user = reset($users));
        self::assertSame('disabled@domain.com', $user->email);
    }

    public function testMatchingANameSearchesNameAndEmail(): void
    {
        $users = User::find()
            ->matching('owner')
            ->all();

        self::assertCount(1, $users);
        self::assertNotFalse($user = reset($users));
        self::assertSame('owner', $user->name);
    }

    public function testMatchingSeveralKeywordsJoinsThemWithASpace(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['name' => 'Jane Doe']);

        self::assertCount(1, User::find()->matching('Jane  Doe')->all());
        self::assertCount(1, User::find()->matching('Jane, Doe')->all());
        self::assertCount(0, User::find()->matching('Doe Jane')->all());
    }

    /**
     * `sanitizeSearchString()` drops the `%`, so a search made only of wildcards is no filter rather than a
     * `LIKE '%%'` that reaches every row through the index.
     */
    public function testMatchingStripsTheWildcard(): void
    {
        $sql = User::find()
            ->matching('%')
            ->createCommand()
            ->getRawSql();

        self::assertStringNotContainsString('LIKE', $sql);
    }

    public function testMatchingWithoutASearchTermIsNoFilter(): void
    {
        $count = User::find()->count();

        self::assertCount((int)$count, User::find()->matching(null)->all());
        self::assertCount((int)$count, User::find()->matching('')->all());
    }

    public function testNameAttributesOnlySelectsWhatTheUsernameNeeds(): void
    {
        $query = User::find()
            ->nameAttributesOnly()
            ->where(['id' => $this->getUserFromFixture('owner')->id]);

        self::assertSame('owner', $query->one()?->getUsername());

        $sql = $query->createCommand()->getRawSql();

        self::assertStringContainsString('`user`.`name`', $sql);
        self::assertStringNotContainsString('`user`.`email`', $sql);
        self::assertStringNotContainsString('password_hash', $sql);
    }

    public function testSelectListAttributesSelectsWhatTheGridNeeds(): void
    {
        $query = User::find()
            ->selectListAttributes()
            ->where(['id' => $this->getUserFromFixture('owner')->id]);

        self::assertNotNull($query->one());

        $sql = $query->createCommand()->getRawSql();

        self::assertStringContainsString('`user`.`email`', $sql);
        self::assertStringNotContainsString('password_hash', $sql);
        self::assertStringNotContainsString('two_factor_secret', $sql);
    }

    /**
     * `ActiveQuery::whereStatus()` would put the related modules into draft mode, so `UserQuery` compares the
     * column itself.
     */
    public function testEnabledExcludesDisabledUsers(): void
    {
        $names = array_map(fn (User $user): string => $user->name, User::find()->enabled()->all());

        self::assertContains('owner', $names);
        self::assertNotContains('disabled', $names);
    }
}
