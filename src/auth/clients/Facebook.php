<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\auth\clients;

use davidhirtz\yii2\datetime\Date;
use davidhirtz\yii2\skeleton\models\AuthClient;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

class Facebook extends \yii\authclient\clients\Facebook implements ClientInterface
{
    public $attributeNames = [
        'id',
        'name',
        'first_name',
        'last_name',
        'link',
        'birthday',
        'location',
        'email',
        'timezone',
        'locale',
        'picture.width(2000).height(2000)',
    ];

    public function init(): void
    {
        if (!$this->clientId) {
            if (!isset(Yii::$app->params['facebookClientId'])) {
                throw new InvalidConfigException(self::class . '::$clientId must be defined');
            }

            $this->clientId = Yii::$app->params['facebookClientId'];
        }

        if (!$this->clientSecret) {
            if (!isset(Yii::$app->params['facebookClientSecret'])) {
                throw new InvalidConfigException(self::class . '::$clientSecret must be defined');
            }

            $this->clientSecret = Yii::$app->params['facebookClientSecret'];
        }

        parent::init();
    }

    public function getSafeUserAttributes(): array
    {
        $attributes = $this->getUserAttributes();
        $safe = [];

        foreach (['name', 'first_name', 'last_name', 'email'] as $key) {
            if (isset($attributes[$key])) {
                $safe[$key] = $attributes[$key];
            }
        }

        if (isset($attributes['locale'])) {
            $safe['language'] = $attributes['locale'];
        }

        if (isset($attributes['timezone'])) {
            $safe['timezone'] = timezone_name_from_abbr('', $attributes['timezone'] * 3600, 0);
        }

        if (isset($attributes['birthday'])) {
            $safe['birthdate'] = new Date($attributes['birthday']);
        }

        if (isset($attributes['location']['name'])) {
            $pos = strpos((string) $attributes['location']['name'], ',');
            $safe['country'] = mb_substr((string) $attributes['location']['name'], 0, $pos, Yii::$app->charset);
            $safe['city'] = trim(mb_substr((string) $attributes['location']['name'], $pos + 1, null, Yii::$app->charset));
        }

        if (isset($attributes['picture']['data']) && !$attributes['picture']['data']['is_silhouette']) {
            $safe['externalPictureUrl'] = $attributes['picture']['data']['url'];
        }

        return $safe;
    }

    public function getAuthData(): array
    {
        $attributes = $this->getUserAttributes();

        return [
            'name' => ArrayHelper::getValue($attributes, 'name'),
            'email' => ArrayHelper::getValue($attributes, 'email'),
            'token' => $this->getAccessToken(),
        ];
    }

    public static function getExternalUrl(AuthClient $client): string
    {
        return "https://www.facebook.com/profile.php?$client->id";
    }

    public static function getDisplayName(AuthClient $client): string
    {
        return "{$client->data['name']} ({$client->data['email']})";
    }
}
