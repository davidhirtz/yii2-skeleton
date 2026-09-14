<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\AuthItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AuthItemGridView;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\data\ArrayDataProvider;

class AuthItemGridViewTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testTheDescriptionIsRenderedInTheAdminLanguage(): void
    {
        Yii::$app->language = 'de';

        self::assertStringContainsString('Benutzer verwalten', $this->render(User::AUTH_USER));

        Yii::$app->language = 'en-US';

        self::assertStringContainsString('Manage users', $this->render(User::AUTH_USER));
    }

    public function testALegacyDescriptionIsRenderedAsItStands(): void
    {
        Yii::$app->language = 'de';

        $item = AuthItem::create();
        $item->name = 'legacy';
        $item->type = 1;
        $item->description = 'Update users';

        self::assertStringContainsString('Update users', $this->renderItems($item));
    }

    public function testAnItemWithoutADescriptionFallsBackToItsName(): void
    {
        $item = AuthItem::create();
        $item->name = 'admin';
        $item->type = 1;

        self::assertStringContainsString('Admin', $this->renderItems($item));
    }

    public function testTheUserLinksToItsPermissions(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $item = AuthItem::find()
            ->withUsers()
            ->andWhere(['name' => User::AUTH_ROLE_ADMIN])
            ->one();

        self::assertInstanceOf(AuthItem::class, $item);
        self::assertStringContainsString('/admin/user-auth/index?id=' . $user->id, $this->renderItems($item));
    }

    private function render(string $name): string
    {
        $item = AuthItem::findOne(['name' => $name]);
        self::assertInstanceOf(AuthItem::class, $item);

        return $this->renderItems($item);
    }

    private function renderItems(AuthItem ...$items): string
    {
        return (string)AuthItemGridView::make()
            ->provider(new ArrayDataProvider([
                'allModels' => $items,
                'pagination' => false,
            ]));
    }
}
