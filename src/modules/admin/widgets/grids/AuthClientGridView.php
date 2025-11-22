<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\AuthClientListGroup;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\Modal;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use Override;
use Stringable;
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

    protected function getUpdatedAtColumn(): TimeagoColumn
    {
        return TimeagoColumn::make()
            ->property('updated_at');
    }
}
