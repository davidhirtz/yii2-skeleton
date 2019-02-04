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
        'picture.type(large)',
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

        $safe = [
            'name' => ArrayHelper::getValue($attributes, 'name'),
            'first_name' => ArrayHelper::getValue($attributes, 'first_name'),
            'last_name' => ArrayHelper::getValue($attributes, 'last_name'),
            'email' => ArrayHelper::getValue($attributes, 'email'),
            'language' => ArrayHelper::getValue($attributes, 'locale'),
            'timezone' => timezone_name_from_abbr('', ArrayHelper::getValue($attributes, 'timezone', 0) * 3600, 0),
        ];

        if ($birthday = ArrayHelper::getValue($attributes, 'birthday')) {
            $safe['birthdate'] = new Date($birthday);
        }

        if ($location = ArrayHelper::getValue($attributes, 'location.name')) {
            $location = explode(', ', $location);
            $safe['country'] = array_pop($location);
            $safe['city'] = implode(', ', $location);
        }

        if ($picture = ArrayHelper::getValue($attributes, 'picture.data.url') && !ArrayHelper::getValue($attributes, 'picture.data.is_silhouette')) {
            $safe['externalPictureUrl'] = $picture;
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
        return "https://www.facebook.com/{$client->id}";
    }

    /**
     * @inheritdoc
     */
    public static function getDisplayName($client)
    {
        return "{$client->data['name']} ({$client->data['email']})";
    }
}