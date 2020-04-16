<?php

namespace davidhirtz\yii2\skeleton\auth\clients;

use davidhirtz\yii2\datetime\Date;
use Yii;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;

/**
 * Class Facebook.
 * @package davidhirtz\yii2\skeleton\auth\clients
 */
class Facebook extends \yii\authclient\clients\Facebook implements ClientInterface
{
    /**
     * @var array
     */
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

    /**
     * Sets login information from application params.
     * @throws \yii\base\NotSupportedException
     */
    public function init()
    {
        if (!$this->clientId) {
            if (!isset(Yii::$app->params['facebookClientId'])) {
                throw new NotSupportedException;
            }

            $this->clientId = Yii::$app->params['facebookClientId'];
        }

        if (!$this->clientSecret) {
            if (!isset(Yii::$app->params['facebookClientSecret'])) {
                throw new NotSupportedException;
            }

            $this->clientSecret = Yii::$app->params['facebookClientSecret'];
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getSafeUserAttributes()
    {
        $attributes = $this->getUserAttributes();
        $safe = [];

        foreach (['id', 'name', 'first_name', 'last_name', 'email'] as $key) {
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
            $pos = strpos($attributes['location']['name'], ',');
            $safe['country'] = mb_substr($attributes['location']['name'], 0, $pos, Yii::$app->charset);
            $safe['city'] = trim(mb_substr($attributes['location']['name'], $pos + 1, null, Yii::$app->charset));
        }

        if (isset($attributes['picture']['data']) && !$attributes['picture']['data']['is_silhouette']) {
            $safe['externalPictureUrl'] = $attributes['picture']['data']['url'];
        }

        return $safe;
    }

    /**
     * @inheritdoc
     */
    public function getAuthData()
    {
        $attributes = $this->getUserAttributes();

        return [
            'name' => ArrayHelper::getValue($attributes, 'name'),
            'email' => ArrayHelper::getValue($attributes, 'email'),
            'token' => $this->getAccessToken(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getExternalUrl($client)
    {
        return "https://www.facebook.com/profile.php?{$client->id}";
    }

    /**
     * @inheritdoc
     */
    public static function getDisplayName($client)
    {
        return "{$client->data['name']} ({$client->data['email']})";
    }
}