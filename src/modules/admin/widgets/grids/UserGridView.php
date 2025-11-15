<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\CreateButton;
use davidhirtz\yii2\skeleton\widgets\grids\traits\StatusGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<User>
 * @property UserActiveDataProvider $provider
 */
class UserGridView extends GridView
{
    use StatusGridViewTrait;

    public function __construct()
    {
        $this->attributes['id'] ??= 'user-grid';

        $this->rowAttributes = fn (User $user) => $user->isDisabled() ? ['class' => 'disabled'] : [];

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getNameColumn(),
            $this->getEmailColumn(),
            $this->getLastLoginColumn(),
            $this->getCreatedAtColumn(),
            $this->getButtonsColumn(),
        ];

        parent::__construct();
    }

    protected function initHeader(): void
    {
        $this->header ??= [
            [
                $this->search->getToolbarItem(),
            ],
        ];
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getCreateButton(),
            ],
        ];
    }

    protected function getCreateButton(): ?Stringable
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_CREATE)
            ? CreateButton::make()
                ->label(Yii::t('skeleton', 'New User'))
                ->href(['/admin/user/create'])
                ->icon('user-plus')
            : null;
    }

    protected function getNameColumn(): Column
    {
        return DataColumn::make()
            ->attribute('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(User $user): Stringable
    {
        $name = ($name = $user->getUsername())
            ? Html::div(Html::markKeywords($name, $this->search->getKeywords()), ['class' => 'strong'])
            : Html::div(Yii::t('skeleton', 'User'), ['class' => 'text-muted']);

        $route = $this->getRoute($user);

        return $route ? A::make()->html($name)->href($route) : $name;
    }

    protected function getEmailColumn(): Column
    {
        return DataColumn::make()
            ->attribute('email')
            ->hiddenUntil('md')
            ->content($this->getEmailColumnContent(...));
    }

    protected function getEmailColumnContent(User $user): ?Stringable
    {
        $link = A::make()
            ->html(Html::markKeywords(Html::encode($user->email), $this->search->getKeywords()))
            ->mailto($user->email);

        if ($user->isUnconfirmed()) {
            $link->tooltip(Yii::t('skeleton', 'Unconfirmed email'))
                ->addClass('text-muted');
        }

        return $link;
    }

    protected function getLastLoginColumn(): DataColumn
    {
        return TimeagoColumn::make()
            ->attribute('last_login')
            ->href(fn (User $user) => ['/admin/login/index', 'user' => $user->id]);
    }

    protected function getCreatedAtColumn(): DataColumn
    {
        return TimeagoColumn::make()
            ->attribute('created_at')
            ->hiddenUntil('lg');
    }

    protected function getButtonsColumn(): ButtonsColumn
    {
        return ButtonsColumn::make()
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

        if (Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $user])) {
            return Button::make()
                ->primary()
                ->href(['/admin/auth/assign', 'user' => $user->id])
                ->icon('unlock-alt')
                ->tooltip(Yii::t('skeleton', 'Permissions'))
                ->render();
        }

        return [];
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $model])
            ? ['/admin/user/update', 'id' => $model->id, ...$params]
            : false;
    }

    #[Override]
    public function getModel(): User
    {
        return User::instance();
    }
}
