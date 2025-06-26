<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

abstract class AbstractSignupForm extends Model
{
    use ModelTrait;

    public ?string $email = null;
    public ?string $name = null;
    public ?string $password = null;

    public readonly User $user;

    public function __construct($config = [])
    {
        $this->user = User::create();
        parent::__construct($config);
    }

    protected function setUserAttributes(): void
    {
        $this->user->loadDefaultValues();

        $this->user->email = $this->email;
        $this->user->name = $this->name;
        $this->user->language = Yii::$app->language;
    }

    #[\Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->setUserAttributes();

            if (!$this->user->validate()) {
                $this->addErrors($this->user->getErrors());
            }
        }

        parent::afterValidate();
    }

    public function insert(): bool
    {
        if (!$this->validate() || !$this->beforeInsert()) {
            return false;
        }

        if ($this->user->insert(false)) {
            $this->afterInsert();
            return true;
        }

        return false;
    }

    protected function beforeInsert(): bool
    {
        $this->user->generatePasswordHash($this->password);
        $this->user->generateVerificationToken();

        return true;
    }

    protected function afterInsert(): void
    {
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            ...$this->user->attributeLabels(),
            'password' => Yii::t('skeleton', 'Password'),
        ];
    }
}
