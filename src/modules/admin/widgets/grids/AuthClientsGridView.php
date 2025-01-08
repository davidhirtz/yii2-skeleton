<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\AuthClientListGroup;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Yii;
use yii\data\ArrayDataProvider;

class AuthClientsGridView extends GridView
{
    public $layout = '{items}{footer}';
    public User $user;

    public function init(): void
    {
        $this->dataProvider = new ArrayDataProvider([
            'allModels' => $this->user->authClients,
        ]);

        $this->columns = [
            $this->accountColumn(),
            $this->nameColumn(),
            $this->updatedAtColumn(),
            $this->buttonsColumn(),
        ];

        parent::init();
    }


    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->renderCreateButton(),
                    'options' => ['class' => 'col'],
                ]
            ]
        ];
    }

    protected function renderCreateButton(): string
    {
        $modal = Modal::tag()
            ->id('auth-client-modal')
            ->title(Yii::t('skeleton', 'Clients'))
            ->body(AuthClientListGroup::widget());

        return Button::primary(Yii::t('skeleton', 'Add account'))
            ->icon('plus')
            ->modal($modal)
            ->render();
    }

    protected function accountColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Account'),
            'content' => fn (AuthClient $auth) => $auth->getClientClass()->getTitle(),
        ];
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => function (AuthClient $auth) {
                $id = "deauthorize-$auth->id";
                $title = $auth->getClientClass()->getTitle();

                $modal = Modal::tag()
                    ->id($id)
                    ->title(Yii::t('skeleton', 'Remove {client}', ['client' => $title]))
                    ->body(Yii::t('skeleton', 'Are you sure your want to remove your {client} account?', ['client' => $title]))
                    ->footer(Button::danger(Yii::t('skeleton', 'Remove'))
                        ->icon('trash-alt')
                        ->post(['deauthorize', 'id' => $auth->id, 'name' => $auth->name]));

                return Button::danger()
                    ->icon('trash-alt')
                    ->modal($modal)
                    ->tooltip(Yii::t('skeleton', 'Remove {client}', ['client' => $title]))
                    ->render();
            }
        ];
    }

    protected function nameColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Name'),
            'content' => function (AuthClient $auth) {
                $url = $auth->getClientClass()::getExternalUrl($auth);
                return $url ? Html::a($auth->getDisplayName(), $url, ['target' => '_blank']) : $auth->getDisplayName();
            },
        ];
    }

    protected function updatedAtColumn(): array
    {
        return [
            'class' => TimeagoColumn::class,
            'attribute' => 'updated_at',
        ];
    }
}
