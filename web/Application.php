<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\auth\rbac\DbManager;
use davidhirtz\yii2\skeleton\core\ApplicationTrait;
use davidhirtz\yii2\skeleton\i18n\I18N;
use Yii;
use yii\authclient\Collection;
use yii\base\Event;
use yii\symfonymailer\Mailer;
use yii\web\Cookie;
use yii\web\Response;

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
 * @method CacheSession|DbSession getSession()
 * @method UrlManager getUrlManager()
 * @method User getUser()
 * @method View getView()
 */
class Application extends \yii\web\Application
{
    use ApplicationTrait;

    /**
     * @param array $config
     */
    public function preInit(&$config)
    {
        $config['basePath'] = $config['basePath'] ?? dirname($_SERVER['SCRIPT_FILENAME'], 2);

        $this->preInitInternal($config);
        $this->setDebugModuleConfig($config);

        parent::preInit($config);
    }

    /**
     * @inheritDoc
     */
    protected function bootstrap()
    {
        $this->setDefaultUrlManagerRules();
        $this->setDefaultCookieConfig();
        $this->setDefaultEmail();

        $this->checkMaintenanceStatus();

        parent::bootstrap();
    }

    /**
     * @return array
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'errorHandler' => [
                'class' => 'davidhirtz\yii2\skeleton\web\ErrorHandler',
            ],
            'request' => [
                'class' => 'davidhirtz\yii2\skeleton\web\Request',
            ],
            'response' => [
                'class' => 'yii\web\Response',
                'on beforeSend' => function (Event $event) {
                    if ($this->getRequest()->getIsDraft()) {
                        /** @var Response $response */
                        $response = $event->sender;
                        $response->getHeaders()->set('X-Robots-Tag', 'none');
                    }
                }
            ],
            'user' => [
                'class' => 'davidhirtz\yii2\skeleton\web\User',
            ],
        ]);
    }

    /**
     * @return Collection|null
     */
    public function getAuthClientCollection()
    {
        return $this->get('authClientCollection', false);
    }

    /**
     * Configures Yii2 debug module (which is currently only available for web applications) if `YII_DEBUG` is `true`.
     * @param array $config
     */
    protected function setDebugModuleConfig(&$config)
    {
        if (YII_DEBUG) {
            if (!in_array('debug', $config['bootstrap'] ?? [])) {
                $config['bootstrap'][] = 'debug';
            }

            $config['modules']['debug']['class'] = $config['modules']['debug']['class'] ?? 'yii\debug\Module';
            $config['modules']['debug']['traceLine'] = $config['modules']['debug']['traceLine'] ?? '<a href="phpstorm://open?file={file}&line={line}">{file}:{line}</a>';
        }
    }

    /**
     * Checks if maintenance mode was set via config. If enabled this triggers {@link Maintenance::bootstrap()} on
     * application bootstrap.
     */
    protected function checkMaintenanceStatus()
    {
        if (!empty($this->params['maintenance']) || !empty($this->getComponents()['maintenance']['enabled'])) {
            $this->bootstrap[] = 'maintenance';
        }
    }

    /**
     * Sets default email account based on server name, this must be called after initialization.
     */
    protected function setDefaultEmail()
    {
        $this->params['email'] = $this->params['email'] ?? ('hostmaster@' . $this->getRequest()->getServerName());
    }
    
    /**
     * Sets default cookie `domain` and `sameSite` properties. The cookie domain can be set via `params` but must match
     * the actual host info, otherwise the session cookies cannot be registered.
     */
    protected function setDefaultCookieConfig()
    {
        if (!Yii::$container->has(Cookie::class)) {
            $config = ['sameSite' => Cookie::SAME_SITE_LAX];

            if ($domain = $this->params['cookieDomain'] ?? false) {
                $hostInfo = trim($domain, '.');

                if (substr($this->getRequest()->getHostInfo(), -strlen($hostInfo)) === $hostInfo) {
                    $config['domain'] = $domain;
                }
            }

            Yii::$container->set(Cookie::class, $config);
        }
    }
}