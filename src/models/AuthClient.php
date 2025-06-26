<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\behaviors\SerializedAttributesBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\traits\TrailModelTrait;
use Yii;

/**
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property array|null $data
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property-read User|null $identity {@see static::getIdentity()}
 *
 * @mixin TrailBehavior
 */
class AuthClient extends ActiveRecord implements TrailModelInterface
{
    use TrailModelTrait;

    #[\Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
            'SerializedAttributesBehavior' => [
                'class' => SerializedAttributesBehavior::class,
                'attributes' => ['data'],
            ],
            'TimestampBehavior' => TimestampBehavior::class,
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['id', 'name', 'user_id'],
                'required',
            ],
            [
                ['user_id'],
                $this->validateUserId(...),
            ],
            [
                ['data'],
                $this->validateData(...),
            ]
        ];
    }

    public function validateUserId(): void
    {
        if (!$this->getIsNewRecord() && $this->isAttributeChanged('user_id')) {
            $this->addError('user_id', Yii::t('skeleton', 'A different user is already linked with this {client} account.', [
                'client' => $this->getClientClass()->getTitle(),
            ]));
        }
    }

    public function validateData(): void
    {
        if (isset($this->data['email'])) {
            $emailIsAlreadyRegistered = User::find()
                ->andWhereEmail($this->data['email'])
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
     * @return UserQuery<User>
     */
    public function getIdentity(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(Yii::$app->getUser()->identityClass, ['id' => 'user_id']);
        return $query;
    }

    public static function findOrCreateFromClient(ClientInterface $client): AuthClient
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

    public function getTrailParents(): array
    {
        return [$this->identity];
    }

    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'name',
            'data',
            'updated_at',
            'created_at',
        ]);
    }

    public function getTrailModelName(): string
    {
        return $this->name ? $this->getClientClass()->getTitle() : $this->getTrailModelType();
    }

    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'Client');
    }

    public function getDisplayName(): string
    {
        return $this->getClientClass()::getDisplayName($this);
    }

    /**
     * @noinspection PhpUnused
     */
    public function getExternalUrl(): string
    {
        return $this->getClientClass()::getExternalUrl($this);
    }

    public function getClientClass(): ClientInterface
    {
        /** @var ClientInterface $client */
        $client = Yii::$app->getAuthClientCollection()->getClient($this->name);
        return $client;
    }

    #[\Override]
    public static function tableName(): string
    {
        return '{{%auth_client}}';
    }
}
