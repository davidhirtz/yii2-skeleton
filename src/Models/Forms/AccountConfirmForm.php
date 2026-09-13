<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Override;
use Yii;
use yii\base\Model;

class AccountConfirmForm extends Model
{
    public ?string $code = null;
    public ?User $user = null;

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['code'],
                'required',
            ],
            [
                ['code'],
                $this->validateCode(...),
                'when' => fn () => !$this->hasErrors(),
            ],
        ];
    }

    protected function validateCode(): void
    {
        $this->user ??= UserToken::find()
            ->whereType(UserToken::TYPE_VERIFICATION)
            ->whereToken((string)$this->code)
            ->unexpired()
            ->selectWith('user')
            ->limit(1)
            ->one()?->user;

        if (!$this->user) {
            $this->addError('code', Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->getAttributeLabel('code'),
            ]));
        }
    }

    public function confirm(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->user->confirmEmail();

        return true;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'ACCOUNT_CONFIRM_NAME_LABEL'),
            'code' => Yii::t('skeleton', 'ACCOUNT_CONFIRM_CODE_LABEL'),
        ];
    }
}
