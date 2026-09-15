<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Base\Traits\ApplicationTrait;
use Hirtz\Skeleton\Rbac\DbManager;
use Override;
use Yii;
use yii\base\InvalidCallException;
use yii\debug\Module;
use yii\symfonymailer\Mailer;
use yii\web\Cookie;

/**
 * @template TUserIdentity of \Hirtz\Skeleton\Models\User
 * @extends \yii\web\Application<TUserIdentity>
 *
 * @property DbManager $authManager
 * @property Request $request
 * @property Response $response
 * @property DbSession $session
 * @property User $user
 *
 * @method DbManager getAuthManager()
 * @method Mailer getMailer()
 * @method Request getRequest()
 * @method Response getResponse()
 * @method CacheSession|DbSession getSession()
 * @method User getUser()
 */
class Application extends \yii\web\Application
{
    use ApplicationTrait;

    /**
     * `Yii::$app` is either application, so code that only ever runs under a web request asks for this one and
     * code that runs under both keeps `Yii::$app` and narrows — which is what makes a console command reaching
     * for `getUser()` or `getSession()` a static analysis error rather than a runtime surprise.
     */
    public static function current(): static
    {
        $app = Yii::$app;

        if (!$app instanceof static) {
            throw new InvalidCallException('This can only be called from a web application.');
        }

        return $app;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[Override]
    public function preInit(&$config): void
    {
        $config['basePath'] ??= realpath(dirname((string)$_SERVER['SCRIPT_FILENAME'], 2));

        $this->preInitInternal($config);
        $this->setDebugModuleConfig($config);

        parent::preInit($config);
    }

    #[Override]
    protected function bootstrap(): void
    {
        $this->setDefaultCookieConfig();
        $this->setDefaultUrlManagerRules();

        parent::bootstrap();

        $this->setDefaultEmail();
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function coreComponents(): array
    {
        return [
            ...parent::coreComponents(),
            'errorHandler' => [
                'class' => ErrorHandler::class,
            ],
            'request' => [
                'class' => Request::class,
            ],
            'response' => [
                'class' => Response::class,
            ],
            'user' => [
                'class' => User::class,
            ],
        ];
    }

    /**
     * Configures Yii2 debug module (which is currently only available for web applications) if `YII_DEBUG` is `true`.
     *
     * @param array<string, mixed> $config
     */
    protected function setDebugModuleConfig(array &$config): void
    {
        if (YII_DEBUG && !YII_ENV_TEST) {
            if (!in_array('debug', $config['bootstrap'] ?? [], true)) {
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
            $config = [
                'sameSite' => Cookie::SAME_SITE_LAX,
                'secure' => $this->getRequest()->getIsSecureConnection(),
            ];

            if ($domain = $this->params['cookieDomain'] ?? false) {
                $hostInfo = trim((string)$domain, '.');

                if (str_ends_with((string)$this->getRequest()->getHostInfo(), $hostInfo)) {
                    $config['domain'] = $domain;
                }
            }

            Yii::$container->set(Cookie::class, $config);
        }
    }
}
