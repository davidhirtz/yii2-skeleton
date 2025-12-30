<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Models\AuthClient;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\AuthClientListGroup;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\UserWidgetTrait;
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

    protected function getAccountColumn(): ?Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Account'))
            ->content(fn (AuthClient $auth) => $auth->getClientClass()->getTitle());
    }

    protected function getButtonColumn(): ?Column
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
