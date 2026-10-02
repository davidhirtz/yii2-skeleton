<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Override;
use Stringable;
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

    /**
     * @var bool whether spaces between HTML tags should be removed from the output.
     */
    public bool $spacelessOutput = false;

    /**
     * @var string|false the `Content-Security-Policy` header of a page outside the admin. It restricts nothing a page
     * runs, so a page cached as a whole can send it: only the site itself may frame the content (`frame-ancestors
     * 'none'` for nobody), no plugin is loaded and no foreign `<base>` resolves the page's links.
     * @link https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Clickjacking_Defense_Cheat_Sheet.md
     */
    public string|false $contentSecurityPolicy = "frame-ancestors 'self'; object-src 'none'; base-uri 'self'";

    /**
     * @var bool|null whether the application's `contentSecurityPolicy` is sent instead, allowing only the scripts
     * `Web\View` stamps with its nonce; `null` sends it inside the admin alone. A page cached as a whole cannot: every
     * visitor would get the nonce it was cached with.
     */
    public ?bool $strictContentSecurityPolicy = null;

    /**
     * @var string|false the `Strict-Transport-Security` header, e.g. `max-age=31536000`. Off by default: it is a
     * promise about the whole host, which the web server makes for every response — sent here as well, the browser
     * receives it twice and a scanner reads neither. Only ever sent over a secure connection, which behind a proxy
     * terminating TLS needs `Request::$trustedHosts`.
     */
    public string|false $strictTransportSecurity = false;

    /**
     * @var string|false the `Referrer-Policy` header: by default the full URL never leaves the site, so a token in it
     * reaches no other host. A page carrying one sends `no-referrer`, see {@see static::sendNoReferrer()}.
     */
    public string|false $referrerPolicy = 'strict-origin-when-cross-origin';

    /**
     * @var bool whether `X-Content-Type-Options: nosniff` is sent, which keeps a browser from running a response as
     * something other than its declared type.
     */
    public bool $noSniff = true;

    protected User $webuser;

    public function __construct($id, $module, $config = [])
    {
        $this->webuser = Application::current()->getUser();
        parent::__construct($id, $module, $config);
    }

    /**
     * Listeners must run before `behaviors()` is evaluated, so this cannot be an `EVENT_BEFORE_ACTION` handler:
     * `Component::trigger()` attaches the behaviors before it calls any handler, and a class-level handler is called
     * after the instance-level ones an `ActionFilter` registers.
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
     * Inside the admin a signed-in user's home is the dashboard, not the website.
     */
    #[Override]
    public function goHome(): Response
    {
        return !$this->webuser->getIsGuest() && $this->isInAdminModule()
            ? $this->redirect(['/admin/dashboard/index'])
            : parent::goHome();
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
     * A flash is rendered as HTML, so a plain string is encoded here and only a `Stringable` — something that
     * built its own markup and knows what it escaped — is trusted (monorepo issue #160). The session holds a
     * string either way, since a flash survives a redirect by being serialized into it.
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
