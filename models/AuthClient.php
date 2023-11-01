<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\behaviors\SerializedAttributesBehavior;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use Yii;

/**
 * Class AuthClient
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property array $data
 * @property DateTime $updated_at
 * @property DateTime $created_at
 *
 * @property Identity $identity
 */
class AuthClient extends ActiveRecord
{
    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return [
            'DateTimeBehavior' => DateTimeBehavior::class,
            'SerializedAttributesBehavior' => [
                'class' => SerializedAttributesBehavior::class,
                'attributes' => ['data'],
            ],
            'TimestampBehavior' => TimestampBehavior::class,
            'TrailBehavior' => 'davidhirtz\yii2\skeleton\behaviors\TrailBehavior',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [
                ['id', 'name', 'user_id'],
                'required',
            ],
            [
                ['user_id'],
                /** {@link AuthClient::validateUserId()} */
                'validateUserId',
            ],
            [
                ['data'],
                /** {@link AuthClient::validateData()} */
                'validateData',
            ]
        ];
    }

    /**
     * Validates user id.
     */
    public function validateUserId()
    {
        if (!$this->getIsNewRecord() && $this->isAttributeChanged('user_id')) {
            $this->addError('user_id', Yii::t('skeleton', 'A different user is already linked with this {client} account.', [
                'client' => $this->getClientClass()->getTitle(),
            ]));
        }
    }

    /**
     * Validates data.
     */
    public function validateData()
    {
        if (isset($this->data['email'])) {
            $emailIsAlreadyRegistered = User::findByEmail($this->data['email'])
                ->andWhere(['!=', 'id', $this->user_id])
                ->exists();

            if ($emailIsAlreadyRegistered) {
                $this->addError('data', Yii::t('skeleton', 'A different user with this email already exists.', [
                    'email' => $this->data['email'],
                ]));
            }
        }
    }

    /**
     * @return UserQuery
     */
    public function getIdentity()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Identity::class, ['id' => 'user_id']);
    }

    /**
     * @param ClientInterface $client
     * @return AuthClient
     */
    public static function findOrCreateFromClient($client)
    {
        $attributes = [
            'id' => $client->getUserAttributes()['id'],
            'name' => $client->getName(),
        ];

        $auth = AuthClient::find()
            ->where($attributes)
            ->limit(1)
            ->one();

        if (!$auth) {
            $auth = AuthClient::create();
            $auth->setAttributes($attributes, false);
        }

        $auth->data = $client->getAuthData();

        return $auth;
    }

    /**
     * @return array
     */
    public function getTrailParents()
    {
        return [$this->identity];
    }

    /**
     * @return array
     */
    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'name',
            'data',
            'updated_at',
            'created_at',
        ]);
    }

    /**
     * @return string
     */
    public function getTrailModelName()
    {
        return $this->name ? $this->getClientClass()->getTitle() : $this->getTrailModelType();
    }

    /**
     * @return string
     */
    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'Client');
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getClientClass()::getDisplayName($this);
    }

    /**
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->getClientClass()::getExternalUrl($this);
    }

    /**
     * @return ClientInterface|\yii\authclient\ClientInterface
     */
    public function getClientClass()
    {
        return Yii::$app->getAuthClientCollection()->getClient($this->name);
    }

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return '{{%auth_client}}';
    }
}
