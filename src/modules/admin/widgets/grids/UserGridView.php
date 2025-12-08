<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\grids;

use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\modules\admin\data\UserActiveDataProvider;
use Hirtz\Skeleton\widgets\grids\columns\ButtonColumn;
use Hirtz\Skeleton\widgets\grids\columns\Column;
use Hirtz\Skeleton\widgets\grids\columns\DataColumn;
use Hirtz\Skeleton\widgets\grids\columns\RelativeTimeColumn;
use Hirtz\Skeleton\widgets\grids\GridView;
use Hirtz\Skeleton\widgets\grids\toolbars\CreateButton;
use Hirtz\Skeleton\widgets\grids\traits\StatusGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @template T of User
 * @extends GridView<T>
 * @property UserActiveDataProvider $provider
 */
class UserGridView extends GridView
{
    use StatusGridViewTrait;

    #[Override]
    public function configure(): void
    {
        $this->model ??= User::instance();

        $this->attributes['id'] ??= 'user-grid';

        $this->header ??= [
            $this->search->getToolbarItem(),
        ];

        $this->rowAttributes = fn (User $user) => $user->isDisabled() ? ['class' => 'disabled'] : [];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getNameColumn(),
            $this->getEmailColumn(),
            $this->getLastLoginColumn(),
            $this->getCreatedAtColumn(),
            $this->getButtonsColumn(),
        ];

        $this->footer ??= [
            $this->getCreateButton(),
        ];

        parent::configure();
    }

    protected function getCreateButton(): ?Stringable
    {
        return $this->webuser->can(User::AUTH_USER_CREATE)
            ? CreateButton::make()
                ->icon('user-plus')
                ->text(Yii::t('skeleton', 'New User'))
                ->href(['/admin/user/create'])
            : null;
    }

    protected function getNameColumn(): Column
    {
        return DataColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(User $user): Stringable
    {
        $name = ($name = $user->getUsername())
            ? Div::make()
                ->content(Html::markKeywords($name, $this->search->getKeywords()))
                ->class('strong')
            : Div::make()
                ->content(Yii::t('skeleton', 'User'))
                ->class('text-muted');

        $route = $this->getRoute($user);

        return $route ? A::make()->content($name)->href($route) : $name;
    }

    protected function getEmailColumn(): Column
    {
        return DataColumn::make()
            ->property('email')
            ->content($this->getEmailColumnContent(...))
            ->hiddenForSmallDevices();
    }

    protected function getEmailColumnContent(User $user): ?Stringable
    {
        $link = A::make()
            ->content(Html::markKeywords(Html::encode($user->email), $this->search->getKeywords()))
            ->mailto($user->email);

        if ($user->isUnconfirmed()) {
            $link->tooltip(Yii::t('skeleton', 'Unconfirmed email'))
                ->addClass('text-muted');
        }

        return $link;
    }

    protected function getLastLoginColumn(): DataColumn
    {
        return RelativeTimeColumn::make()
            ->property('last_login')
            ->url(fn (User $user) => ['/admin/login/index', 'user' => $user->id]);
    }

    protected function getCreatedAtColumn(): DataColumn
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->hiddenForMediumDevices();
    }

    protected function getButtonsColumn(): ButtonColumn
    {
        return ButtonColumn::make()
            ->content($this->getButtonsColumnContent(...));
    }

    protected function getButtonsColumnContent(User $user): array|string
    {
        if ($route = $this->getRoute($user)) {
            return Button::make()
                ->primary()
                ->href($route)
                ->icon('wrench')
                ->render();
        }

        if ($this->webuser->can(User::AUTH_USER_ASSIGN, ['user' => $user])) {
            return Button::make()
                ->primary()
                ->href(['/admin/auth/assign', 'user' => $user->id])
                ->icon('unlock-alt')
                ->tooltip(Yii::t('skeleton', 'Permissions'))
                ->render();
        }

        return [];
    }

    /**
     * @param T $model
     */
    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return $this->webuser->can(User::AUTH_USER_UPDATE, ['user' => $model])
            ? ['/admin/user/update', 'id' => $model->id, ...$params]
            : false;
    }
}
