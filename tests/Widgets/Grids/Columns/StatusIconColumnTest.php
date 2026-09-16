<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Yii;
use yii\base\Model;
use yii\data\ArrayDataProvider;

/**
 * The icon becomes the button that cycles the record's status, and falls back to the plain icon wherever a click
 * must not: a grid that did not enable it, a record the model exempts, and one with no admin page of its own.
 */
class StatusIconColumnTest extends TestCase
{
    use UserFixtureTrait;

    public function testThePlainIconIsRenderedWhileTheGridDoesNotEnableIt(): void
    {
        $html = $this->render($this->createUser(), false);

        self::assertStringContainsString('<span class="fas fa-user" title="Enabled" data-tooltip=""></span>', $html);
        self::assertStringNotContainsString('hx-post', $html);
    }

    public function testTheButtonPostsToTheStatusActionBesideTheRecordsOwnRoute(): void
    {
        $html = $this->render($this->createUser(), true);

        self::assertStringContainsString('hx-post="/admin/user/status?id=1"', $html);
        self::assertStringContainsString('<span class="fas fa-user"></span>', $html);

        // The tooltip names the status a click moves to, and the aria label is that status alone.
        self::assertStringContainsString(
            'title="' . Yii::t('skeleton', 'COMMON_STATUS_BUTTON_TOOLTIP', [
                'status' => Yii::t('skeleton', 'COMMON_ENABLED'),
                'next' => Yii::t('skeleton', 'COMMON_DISABLED'),
            ]) . '"',
            $html,
        );
    }

    public function testTheSiteOwnerKeepsThePlainStar(): void
    {
        $user = $this->createUser();
        $user->is_owner = true;

        $html = $this->render($user, true);

        self::assertStringContainsString('fa-star', $html);
        self::assertStringNotContainsString('hx-post', $html);
    }

    /**
     * A record with no id answers the index route rather than its own update route, so there is no primary key to
     * act on and nothing is offered.
     */
    public function testARecordWithNoUpdateRouteKeepsThePlainIcon(): void
    {
        $html = $this->render($this->createUser(id: null), true);

        self::assertStringNotContainsString('hx-post', $html);
    }

    private function createUser(?int $id = 1): User
    {
        $user = User::create();
        $user->setAttribute('id', $id);
        $user->status = User::STATUS_ENABLED;

        return $user;
    }

    private function render(User $user, bool $enableUpdate): string
    {
        $grid = StatusIconColumnTestGridView::make()
            ->provider(new ArrayDataProvider(['allModels' => [$user]]));

        $column = StatusIconColumn::make()
            ->enableUpdate($enableUpdate)
            ->grid($grid);

        return (string)$column->renderBody($user, 0, 0);
    }
}

/**
 * @extends GridView<Model>
 */
class StatusIconColumnTestGridView extends GridView
{
}
