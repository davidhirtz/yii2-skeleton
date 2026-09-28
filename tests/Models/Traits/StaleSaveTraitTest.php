<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface;
use Hirtz\Skeleton\Models\Traits\StaleSaveTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

/**
 * Two editors on one record: the one saving second is told, instead of overwriting the first.
 */
class StaleSaveTraitTest extends TestCase
{
    use UserFixtureTrait;

    public function testASaveOverAnotherOneMadeSinceIsRefused(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
        $id = $this->getUserFromFixture('admin')->id;

        $first = StaleSaveUser::findOne($id);
        $second = StaleSaveUser::findOne($id);
        self::assertInstanceOf(StaleSaveUser::class, $first);
        self::assertInstanceOf(StaleSaveUser::class, $second);

        $first->name = 'firsteditor';
        self::assertTrue($first->save(), print_r($first->getErrors(), true));

        $second->name = 'secondeditor';
        $second->loadedAt = time() - 5;

        self::assertFalse($second->save());
        self::assertArrayHasKey('loadedAt', $second->getErrors());

        $second->loadedAt = null;

        self::assertTrue($second->save(), print_r($second->getErrors(), true));
    }
}

class StaleSaveUser extends User implements StaleSaveInterface
{
    use StaleSaveTrait;

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getStaleSaveRules(),
        ];
    }
}
