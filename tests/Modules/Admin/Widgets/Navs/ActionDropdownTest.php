<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

/**
 * The dropdown lays its items out through `.dropdown-item > .btn`, so a trigger that carries no button class has no
 * styling at all.
 */
class ActionDropdownTest extends TestCase
{
    use UserFixtureTrait;

    public function testEveryUserActionIsStyledAsAButton(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->assertModalTriggersAreButtons(Yii::$app->runAction('admin/user/update', ['id' => $user->id]));
    }

    public function testEveryAccountActionIsStyledAsAButton(): void
    {
        $this->login();

        $this->assertModalTriggersAreButtons(Yii::$app->runAction('admin/account/update'));
    }

    private function assertModalTriggersAreButtons(string $html): void
    {
        preg_match_all('~<li class="dropdown-item">(.*?)</li>~s', $html, $items);
        self::assertNotEmpty($items[1]);

        $triggers = [];

        foreach ($items[1] as $item) {
            preg_match_all('~<button[^>]*data-modal="#[^"]+"[^>]*>~', $item, $matches);
            $triggers = [...$triggers, ...$matches[0]];
        }

        self::assertNotEmpty($triggers);

        foreach ($triggers as $trigger) {
            self::assertStringContainsString('class="btn ', $trigger);
        }
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
