<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\behaviors\SerializedAttributesBehavior;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use Yii;

/**
 * Class AuthClient.
 * @package davidhirtz\yii2\skeleton\models
 *
 * @property string $id
 * @property integer $user_id
 * @property string $name
 * @property array $data
 * @property \davidhirtz\yii2\datetime\DateTime $updated_at
 * @property \davidhirtz\yii2\datetime\DateTime $created_at
 *
 * @property Identity $identity
 */
class AuthClient extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
            'SerializedAttributesBehavior' => [
                'class' => SerializedAttributesBehavior::class,
                'attributes' => ['data'],
            ],
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

            if($emailIsAlreadyRegistered) {
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
        return $this->hasOne(Identity::class, ['id' => 'user_id']);
    }

    /**
     * @param ClientInterface $client
     * @return AuthClient
     */
    public static function findOrCreateFromClient($client)
    {
        $attributes = [
            'id' => $client->getSafeUserAttributes()['id'],
            'name' => $client->getName(),
        ];

        $auth = AuthClient::find()
            ->where($attributes)
            ->limit(1)
            ->one();

        if (!$auth) {
            $auth = new AuthClient;
            $auth->setAttributes($attributes, false);
        }

        $auth->data = $client->getAuthData();

        return $auth;
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
     * @return \davidhirtz\yii2\skeleton\auth\clients\ClientInterface|\yii\authclient\ClientInterface
     */
    public function getClientClass()
    {
        return Yii::$app->getAuthClientCollection()->getClient($this->name);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth_client}}';
    }
}
