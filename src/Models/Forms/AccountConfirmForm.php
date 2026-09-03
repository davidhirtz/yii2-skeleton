<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Override;
use Yii;
use yii\base\Model;

class AccountConfirmForm extends Model
{
    use IdentityTrait;

    public string $code;

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['email'],
                'trim',
            ],
            [
                ['email', 'code'],
                'required',
            ],
            [
                ['email'],
                $this->validateEmail(...),
            ],
            [
                ['code'],
                'string',
                'length' => 32,
                'notEqual' => Yii::t('yii', '{attribute} is invalid.'),
                'skipOnError' => true,
            ],
            [
                ['code'],
                $this->validateCode(...),
                'when' => fn () => !$this->hasErrors(),
            ]
        ];
    }

    protected function validateCode(): void
    {
        if ($this->user->verification_token !== $this->code) {
            $this->addError('code', Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->getAttributeLabel('code'),
            ]));
        }
    }

    public function confirm(): bool
    {
        return $this->validate() && $this->user->updateAttributes(['verification_token' => null]);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'name' => Lang::t('skeleton', 'ACCOUNT_CONFIRM_NAME_LABEL'),
            'code' => Lang::t('skeleton', 'ACCOUNT_CONFIRM_CODE_LABEL'),
        ];
    }
}
