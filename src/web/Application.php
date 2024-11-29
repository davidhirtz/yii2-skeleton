<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\base\traits\ApplicationTrait;
use davidhirtz\yii2\skeleton\rbac\DbManager;
use Yii;
use yii\authclient\Collection;
use yii\base\Event;
use yii\debug\Module;
use yii\symfonymailer\Mailer;
use yii\web\Cookie;
use yii\web\Response;

/**
 * @property Collection $authClientCollection
 * @property DbManager $authManager
 * @property Request $request
 * @property DbSession $session
 * @property User $user
 *
 * @method DbManager getAuthManager()
 * @method Mailer getMailer()
 * @method Request getRequest()
 * @method CacheSession|DbSession getSession()
 * @method User getUser()
 */
class Application extends \yii\web\Application
{
    use ApplicationTrait;

    public function preInit(&$config): void
    {
        $config['basePath'] ??= dirname((string)$_SERVER['SCRIPT_FILENAME'], 2);

        $this->preInitInternal($config);
        $this->setDebugModuleConfig($config);

        parent::preInit($config);
    }

    protected function bootstrap(): void
    {
        $this->setDefaultCookieConfig();
        $this->setDefaultUrlManagerRules();

        parent::bootstrap();

        $this->setDefaultEmail();
    }

    public function coreComponents(): array
    {
        return array_merge(parent::coreComponents(), [
            'errorHandler' => [
                'class' => ErrorHandler::class,
            ],
            'request' => [
                'class' => Request::class,
            ],
            'response' => [
                'class' => Response::class,
                'on beforeSend' => function (Event $event): void {
                    if ($this->getRequest()->getIsDraft()) {
                        /** @var Response $response */
                        $response = $event->sender;
                        $response->getHeaders()->set('X-Robots-Tag', 'none');
                    }
                }
            ],
            'user' => [
                'class' => User::class,
            ],
        ]);
    }

    public function getAuthClientCollection(): ?Collection
    {
        return $this->get('authClientCollection', false);
    }

    /**
     * Configures Yii2 debug module (which is currently only available for web applications) if `YII_DEBUG` is `true`.
     */
    protected function setDebugModuleConfig(array &$config): void
    {
        if (YII_DEBUG && !YII_ENV_TEST) {
            if (!in_array('debug', $config['bootstrap'] ?? [])) {
                $config['bootstrap'][] = 'debug';
            }

            $config['modules']['debug']['class'] ??= Module::class;
            $config['modules']['debug']['panels'] ??= ['user' => false];
            $config['modules']['debug']['traceLine'] ??= '<a href="phpstorm://open?file={file}&line={line}">{file}:{line}</a>';
        }
    }

    /**
     * Sets default email address based on server name, this must be called after initialization.
     */
    protected function setDefaultEmail(): void
    {
        $this->params['email'] ??= ('hostmaster@' . $this->getRequest()->getServerName());
    }

    /**
     * Sets default cookie `domain` and `sameSite` properties. The cookie domain can be set via `params` but must match
     * the actual host info, otherwise the session cookies cannot be registered.
     */
    protected function setDefaultCookieConfig(): void
    {
        if (!Yii::$container->has(Cookie::class)) {
            $config = ['sameSite' => Cookie::SAME_SITE_LAX];

            if ($domain = $this->params['cookieDomain'] ?? false) {
                $hostInfo = trim((string)$domain, '.');

                if (str_ends_with((string) $this->getRequest()->getHostInfo(), $hostInfo)) {
                    $config['domain'] = $domain;
                }
            }

            Yii::$container->set(Cookie::class, $config);
        }
    }
}
