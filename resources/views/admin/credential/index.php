<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\CredentialController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $credentials
 * @var array<string, CredentialProvider> $providers
 * @var User $user
 */

use Hirtz\Skeleton\Credentials\CredentialProvider;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Option;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Select;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\CredentialGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Panels\Card;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'CREDENTIALS_TITLE'));

echo AccountHeader::make()
    ->model($user);

echo AccountSubmenu::make();

if ($credentials->getTotalCount()) {
    echo GridContainer::make()
        ->title(Yii::t('skeleton', 'CREDENTIALS_TITLE'))
        ->grid(CredentialGridView::make()
            ->provider($credentials));
}

$links = array_filter(array_map(fn (CredentialProvider $provider): ?A => $provider->getUrl()
    ? A::make()->href($provider->getUrl())->target('_blank')->text($provider->getLabel())
    : null, $providers));

echo Container::make()
    ->content(Card::make()
        ->title(Yii::t('skeleton', 'CREDENTIAL_CREATE_TITLE'))
        ->content(
            P::make()->text(Yii::t('skeleton', 'CREDENTIAL_HINT')),
            $links ? P::make()->content(Yii::t('skeleton', 'CREDENTIAL_GET_KEY') . ' ' . implode(', ', $links)) : null,
            Form::make()
                ->action(['/admin/credential/create'])
                ->content(Div::make()
                    ->addClass('input-group')
                    ->content(
                        Select::make()
                            ->addClass('input')
                            ->attribute('id', 'credential-provider')
                            ->attribute('name', 'provider')
                            ->attribute('aria-label', Yii::t('skeleton', 'CREDENTIAL_PROVIDER_LABEL'))
                            ->options(...array_values(array_map(fn (CredentialProvider $provider): Option => Option::make()
                                ->value($provider->getName())
                                ->label($provider->getLabel()), $providers))),
                        Input::make()
                            ->addClass('input')
                            ->attribute('id', 'credential-secret')
                            ->attribute('type', 'password')
                            ->attribute('name', 'credentialSecret')
                            ->attribute('autocomplete', 'off')
                            ->attribute('required', true)
                            ->attribute('aria-label', Yii::t('skeleton', 'CREDENTIAL_SECRET_LABEL'))
                            ->attribute('placeholder', Yii::t('skeleton', 'CREDENTIAL_SECRET_LABEL')),
                        Button::make()
                            ->attributes(['type' => 'submit'])
                            ->addClass('btn btn-primary')
                            ->text(Yii::t('skeleton', 'CREDENTIAL_BUTTON_SAVE')),
                    )),
        ));
