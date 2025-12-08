<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\grids;

use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\models\AuthClient;
use Hirtz\Skeleton\modules\admin\controllers\UserController;
use Hirtz\Skeleton\modules\admin\widgets\panels\AuthClientListGroup;
use Hirtz\Skeleton\widgets\grids\columns\ButtonColumn;
use Hirtz\Skeleton\widgets\grids\columns\Column;
use Hirtz\Skeleton\widgets\grids\columns\RelativeTimeColumn;
use Hirtz\Skeleton\widgets\grids\GridView;
use Hirtz\Skeleton\widgets\Modal;
use Hirtz\Skeleton\widgets\traits\UserWidgetTrait;
use Override;
use Yii;
use yii\data\ArrayDataProvider;

class AuthClientGridView extends GridView
{
    use UserWidgetTrait;

    public string $layout = '{items}{footer}';

    #[Override]
    public function configure(): void
    {
        $this->provider = new ArrayDataProvider([
            'allModels' => $this->user->authClients,
        ]);

        $this->footer ??= [
            $this->getCreateButton(),
        ];

        $this->columns ??= [
            $this->getAccountColumn(),
            $this->getNameColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getCreateButton(): string
    {
        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Clients'))
            ->content(AuthClientListGroup::make());

        return Button::make()
            ->primary()
            ->text(Yii::t('skeleton', 'Add account'))
            ->icon('plus')
            ->modal($modal)
            ->render();
    }

    protected function getAccountColumn(): Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Account'))
            ->content(fn (AuthClient $auth) => $auth->getClientClass()->getTitle());
    }

    protected function getButtonColumn(): ButtonColumn
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    /**
     * @see UserController::actionDeauthorize()
     */
    protected function getButtonColumnContent(AuthClient $auth): string
    {
        $title = $auth->getClientClass()->getTitle();

        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Are you sure your want to remove your {client} account?', ['client' => $title]))
            ->footer(Button::make()
                ->danger()
                ->text(Yii::t('skeleton', 'Remove {client}', ['client' => $title]))
                ->icon('trash-alt')
                ->post(['deauthorize', 'id' => $auth->id, 'name' => $auth->name]));

        return Button::make()
            ->danger()
            ->icon('trash-alt')
            ->modal($modal)
            ->tooltip(Yii::t('skeleton', 'Remove {client}', ['client' => $title]))
            ->render();
    }

    protected function getNameColumn(): Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Name'))
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(AuthClient $auth): string
    {
        $url = $auth->getClientClass()::getExternalUrl($auth);
        return $url ? Html::a($auth->getDisplayName(), $url, ['target' => '_blank']) : $auth->getDisplayName();
    }

    protected function getUpdatedAtColumn(): RelativeTimeColumn
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }
}
