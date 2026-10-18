<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\GrantController::actionAuthorize()
 *
 * @var View $this
 * @var AuthorizationRequest $request
 * @var User $user
 */

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Checkbox;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Mcp\OAuth\AuthorizationRequest;
use Hirtz\Skeleton\Mcp\OAuth\RedirectUri;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Buttons\ButtonGroup;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;

$client = $request->client->clientName ?? $request->client->getHost();
$redirectHost = (string)parse_url($request->redirectUri, PHP_URL_HOST);
$isReadOnly = $request->getScope() === OAuthGrant::SCOPE_READ;

$this->title(Yii::t('skeleton', 'GRANT_AUTHORIZE_TITLE', [
    'client' => $client,
]));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(
            P::make()->content(Yii::t('skeleton', 'GRANT_AUTHORIZE_TEXT', [
                'client' => Html::tag('strong', Html::encode($client)),
                'host' => Html::encode($request->client->getHost()),
                'site' => Html::encode(Yii::$app->name),
                'user' => Html::encode($user->getUsername()),
            ])),
            P::make()->content(Yii::t('skeleton', 'GRANT_AUTHORIZE_REDIRECT', [
                'host' => Html::tag('strong', Html::encode($redirectHost)),
            ])),
            RedirectUri::isLoopback($request->redirectUri)
                ? Alert::make()
                    ->warning()
                    ->content(Yii::t('skeleton', 'GRANT_AUTHORIZE_LOOPBACK_WARNING'))
                : null,
            Form::make()
                ->addClass('form')
                ->attribute('hx-boost', 'false')
                ->content(
                    Div::make()
                        ->addClass('form-checkbox')
                        ->content(
                            Div::make()
                                ->addClass('checkbox')
                                ->content(Checkbox::make()
                                    ->addClass('input')
                                    ->attribute('id', 'grant-read-only')
                                    ->name('readOnly')
                                    ->value('1')
                                    ->checked($isReadOnly)
                                    ->disabled($isReadOnly)),
                            Label::make()
                                ->addClass('label')
                                ->for('grant-read-only')
                                ->text(Yii::t('skeleton', 'GRANT_READ_ONLY_LABEL')),
                        ),
                    Div::make()
                        ->addClass('form-buttons')
                        ->content(ButtonGroup::make()->content(
                            Button::make()
                                ->attributes(['type' => 'submit', 'name' => 'approve', 'value' => '1'])
                                ->addClass('btn btn-primary')
                                ->text(Yii::t('skeleton', 'GRANT_BUTTON_APPROVE')),
                            Button::make()
                                ->attributes(['type' => 'submit', 'name' => 'deny', 'value' => '1'])
                                ->addClass('btn btn-secondary')
                                ->text(Yii::t('skeleton', 'GRANT_BUTTON_DENY')),
                        )),
                )
        ));
