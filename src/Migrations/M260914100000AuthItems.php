<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260914100000AuthItems extends Migration
{
    use MigrationTrait;

    private const array LEGACY_USER = ['userCreate', 'userDelete', 'userUpdate'];
    private const array LEGACY_REDIRECT = ['redirectCreate'];

    public function safeUp(): void
    {
        $this->addPermission(User::AUTH_USER, $this->getUserDescription(), User::AUTH_ROLE_ADMIN);
        $this->replaceAuthItems(self::LEGACY_USER, User::AUTH_USER);

        $this->addPermission(Redirect::AUTH_REDIRECT, $this->getRedirectDescription(), User::AUTH_ROLE_ADMIN);
        $this->replaceAuthItems(self::LEGACY_REDIRECT, Redirect::AUTH_REDIRECT);

        $this->setDescription(User::AUTH_USER_ASSIGN, Message::make('skeleton', 'AUTH_AUTH_UPDATE_DESCRIPTION'));
        $this->setDescription(Trail::AUTH_TRAIL_INDEX, Message::make('skeleton', 'AUTH_TRAIL_INDEX_DESCRIPTION'));

        $this->dropRules();
    }

    public function safeDown(): void
    {
        $this->restoreAuthItems(self::LEGACY_REDIRECT, Redirect::AUTH_REDIRECT, $this->getRedirectDescription());
        $this->restoreAuthItems(self::LEGACY_USER, User::AUTH_USER, $this->getUserDescription());
    }

    /**
     * The rule the user permissions carried is policy in `Web\User` now. The table itself stays: the auth manager
     * reads it when it fills its cache.
     */
    private function dropRules(): void
    {
        $auth = $this->getAuthManager();

        $this->update($auth->itemTable, ['rule_name' => null], ['not', ['rule_name' => null]]);
        $this->delete($auth->ruleTable);

        $auth->invalidateCache();
    }

    private function setDescription(string $name, Message $description): void
    {
        $auth = $this->getAuthManager();

        $this->update($auth->itemTable, ['description' => $description->toJson()], ['name' => $name]);
        $auth->invalidateCache();
    }

    private function getUserDescription(): Message
    {
        return Message::make('skeleton', 'AUTH_USER_DESCRIPTION');
    }

    private function getRedirectDescription(): Message
    {
        return Message::make('skeleton', 'AUTH_REDIRECT_DESCRIPTION');
    }
}
