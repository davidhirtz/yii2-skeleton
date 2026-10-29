<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Override;
use Stringable;
use Yii;
use yii\base\Event;
use yii\base\Model;
use yii\base\Module;
use yii\web\Response;

/**
 * @template T of Module
 * @extends  \yii\web\Controller<T>
 *
 * @property Request $request
 * @method View getView()
 */
class Controller extends \yii\web\Controller
{
    final public const string EVENT_CONFIGURE = 'configure';

    public bool $spacelessOutput = false;

    /**
     * Restricts no script, so a page cached as a whole can send it.
     *
     * @link https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Clickjacking_Defense_Cheat_Sheet.md
     */
    public string|false $contentSecurityPolicy = "frame-ancestors 'self'; object-src 'none'; base-uri 'self'";

    /**
     * `null` sends it inside the admin alone; a page cached as a whole cannot, since every visitor would share its
     * nonce.
     */
    public ?bool $strictContentSecurityPolicy = null;

    /**
     * Off by default, since the web server makes it for the whole host; sent over a secure connection only, which
     * behind a TLS-terminating proxy needs `Request::$trustedHosts`.
     */
    public string|false $strictTransportSecurity = false;

    /**
     * A page whose URL carries a token sends `no-referrer` instead ({@see static::sendNoReferrer()}).
     */
    public string|false $referrerPolicy = 'strict-origin-when-cross-origin';

    public bool $noSniff = true;

    protected User $webuser;

    public function __construct($id, $module, $config = [])
    {
        $this->webuser = Application::current()->getUser();
        parent::__construct($id, $module, $config);
    }

    /**
     * Not an `EVENT_BEFORE_ACTION` handler, since listeners must run before `behaviors()` and `trigger()` attaches
     * those first.
     */
    #[Override]
    public function init(): void
    {
        parent::init();
        Event::trigger($this, self::EVENT_CONFIGURE);
    }

    #[Override]
    public function beforeAction($action): bool
    {
        if ($this->isDraftLoginRequired()) {
            // Outside the admin no login URL is set, which would answer 403 instead of the login
            $this->webuser->loginUrl ??= ['/admin/account/login'];
            $this->webuser->loginRequired();

            return false;
        }

        if ($this->strictContentSecurityPolicy ?? $this->isInAdminModule()) {
            $policy = Application::current()->getContentSecurityPolicy();
            $this->getView()->nonce = $policy->getNonce();
            $this->response->getHeaders()->set('Content-Security-Policy', $policy->getPolicy());
        } elseif ($this->contentSecurityPolicy) {
            $this->response->getHeaders()->set('Content-Security-Policy', $this->contentSecurityPolicy);
        }

        if ($this->strictTransportSecurity && $this->request->getIsSecureConnection()) {
            $this->response->getHeaders()->set('Strict-Transport-Security', $this->strictTransportSecurity);
        }

        if ($this->referrerPolicy) {
            $this->response->getHeaders()->set('Referrer-Policy', $this->referrerPolicy);
        }

        if ($this->noSniff) {
            $this->response->getHeaders()->set('X-Content-Type-Options', 'nosniff');
        }

        return parent::beforeAction($action);
    }

    /**
     * For a page whose URL carries a secret: even the site's own requests for its scripts and images would write
     * the URL into every access log as their `Referer`.
     */
    protected function sendNoReferrer(): void
    {
        $this->response->getHeaders()->set('Referrer-Policy', 'no-referrer');
    }

    #[Override]
    public function render($view, $params = []): string
    {
        $content = parent::render($view, $params);
        return $this->spacelessOutput ? $this->stripWhitespaceFromHtml($content) : $content;
    }

    /**
     * Inside the admin a signed-in user's home is the dashboard (or their account), not the website.
     */
    #[Override]
    public function goHome(): Response
    {
        return !$this->webuser->getIsGuest() && $this->isInAdminModule()
            ? $this->redirect(AdminModule::current()->getHomeRoute())
            : parent::goHome();
    }

    /**
     * The admin, the login among it, stays reachable on the draft host: the redirect would otherwise never end.
     */
    protected function isDraftLoginRequired(): bool
    {
        return Yii::$app->getUrlManager()->draftRequiresLogin
            && $this->request->getIsDraft()
            && $this->webuser->getIsGuest()
            && !$this->isInAdminModule();
    }

    protected function isInAdminModule(): bool
    {
        $module = $this->module;

        while ($module !== null) {
            if ($module instanceof AdminModule) {
                return true;
            }

            $module = $module->module;
        }

        return false;
    }

    protected function stripWhitespaceFromHtml(string $html): string
    {
        return trim((string)preg_replace('/>\s+</', '><', $html));
    }

    /**
     * @param Model|array<int|string, mixed>|string|Stringable $value
     */
    public function error(Model|array|string|Stringable $value): static
    {
        if ($value instanceof Model) {
            $value = $value->getFirstErrors();
        }

        return $this->addFlash('danger', $value);
    }

    /**
     * @param Model|array<int|string, mixed>|string|Stringable|null $value
     */
    public function success(Model|array|string|Stringable|null $value, string|Stringable|null $message = null): static
    {
        // A record that still has errors is not a success, and the session can hold no object either way.
        if ($value instanceof Model) {
            $value = $value->hasErrors() ? null : $message;
        }

        return $this->addFlash('success', $value);
    }

    /**
     * @param array<int|string, mixed>|string|Stringable|null $value
     */
    public function warning(array|string|Stringable|null $value): static
    {
        return $this->addFlash('warning', $value);
    }

    /**
     * @param Model|array<int|string, mixed>|string|Stringable $value
     */
    public function errorOrSuccess(Model|array|string|Stringable $value, string|Stringable $message): static
    {
        if ($value instanceof Model ? $value->hasErrors() : !empty($value)) {
            $this->error($value);
        } else {
            $this->success($message);
        }

        return $this;
    }

    /**
     * A flash renders as HTML, so a plain string is encoded and only a `Stringable` is trusted (#160).
     *
     * @param array<int|string, mixed>|string|Stringable|null $value
     */
    protected function addFlash(string $status, array|string|Stringable|null $value): static
    {
        $value = $this->encodeFlash($value);

        if ($value) {
            Application::current()->getSession()->addFlash($status, $value);
        }

        return $this;
    }

    /**
     * @param array<int|string, mixed>|string|Stringable|null $value
     * @return array<int|string, mixed>|string|null
     */
    protected function encodeFlash(array|string|Stringable|null $value): array|string|null
    {
        if (is_array($value)) {
            return array_map($this->encodeFlash(...), $value);
        }

        return $value instanceof Stringable ? (string)$value : ($value === null ? null : Html::encode($value));
    }
}
