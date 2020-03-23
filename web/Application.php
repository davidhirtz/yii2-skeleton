<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\composer\Bootstrap;

/**
 * Class Application
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property \yii\authclient\Collection $authClientCollection
 * @property AssetManager $assetManager
 * @property \yii\rbac\DbManager $authManager
 * @property \davidhirtz\yii2\skeleton\i18n\I18N $i18n
 * @property Request $request
 * @property DbSession $session
 * @property Sitemap $sitemap
 * @property UrlManager $urlManager
 * @property User $user
 * @property View $view
 *
 * @method AssetManager getAssetManager()
 * @method \yii\rbac\DbManager getAuthManager()
 * @method \davidhirtz\yii2\skeleton\i18n\I18N getI18n()
 * @method \yii\swiftmailer\Mailer getMailer()
 * @method Request getRequest()
 * @method DbSession getSession()
 * @method UrlManager getUrlManager()
 * @method User getUser()
 * @method View getView()
 */
class Application extends \yii\web\Application
{
    /**
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function preInit(&$config)
    {
        if (!isset($config['basePath'])) {
            $config['basePath'] = dirname($_SERVER['SCRIPT_FILENAME'], 2);
        }

        $config = Bootstrap::preInit($config);
        parent::preInit($config);
    }

    /**
     * @return array
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => [
                'class' => 'davidhirtz\yii2\skeleton\web\Request',
            ],
            'user' => [
                'class' => 'davidhirtz\yii2\skeleton\web\User',
            ],
        ]);
    }

    /**
     * @return object|null|\yii\authclient\Collection
     * @throws \yii\base\InvalidConfigException
     */
    public function getAuthClientCollection()
    {
        return $this->get('authClientCollection', false);
    }
}