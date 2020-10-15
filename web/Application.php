<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\composer\Bootstrap;
use davidhirtz\yii2\skeleton\i18n\I18N;
use yii\authclient\Collection;
use yii\rbac\DbManager;
use yii\swiftmailer\Mailer;
use yii\web\Cookie;

/**
 * Class Application
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property Collection $authClientCollection
 * @property AssetManager $assetManager
 * @property DbManager $authManager
 * @property I18N $i18n
 * @property Request $request
 * @property DbSession $session
 * @property Sitemap $sitemap
 * @property UrlManager $urlManager
 * @property User $user
 * @property View $view
 *
 * @method AssetManager getAssetManager()
 * @method DbManager getAuthManager()
 * @method I18N getI18n()
 * @method Mailer getMailer()
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
     */
    public function preInit(&$config)
    {
        if (!isset($config['basePath'])) {
            $config['basePath'] = dirname($_SERVER['SCRIPT_FILENAME'], 2);
        }

        $config = Bootstrap::preInit($config);
        $this->setCookieConfig($config);

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
     * @return object|null|Collection
     */
    public function getAuthClientCollection()
    {
        return $this->get('authClientCollection', false);
    }

    /**
     * @param array $config
     */
    protected function setCookieConfig(&$config)
    {
        $cookieConfig = [
            'domain' => $this->params['cookieDomain'] ?? '',
            'sameSite' => PHP_VERSION_ID >= 70300 ? Cookie::SAME_SITE_LAX : null,
        ];

        $config['container']['definitions']['yii\web\Cookie'] = array_merge($cookieConfig, $config['container']['definitions']['yii\web\Cookie'] ?? []);
    }
}