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

    /**
     * @var bool whether spaces between HTML tags should be removed from the output.
     */
    public bool $spacelessOutput = false;

    /**
     * @var string|false whether a Content-Security-Policy header should be sent, defaults to only allowing the current
     * site to frame the content. To be more strict, this can be changed to `frame-ancestors 'none'`.
     * @link https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Clickjacking_Defense_Cheat_Sheet.md
     */
    public string|false $contentSecurityPolicy = "frame-ancestors 'self'";

    /**
     * @var string|false the `Strict-Transport-Security` header, only ever sent over a secure connection — set on a
     * plain HTTP response it would be ignored, and over a proxy that terminates TLS it needs `Request::$trustedHosts`.
     */
    public string|false $strictTransportSecurity = 'max-age=31536000';

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
        if ($this->contentSecurityPolicy) {
            $this->response->getHeaders()->set('Content-Security-Policy', $this->contentSecurityPolicy);
        }

        if ($this->strictTransportSecurity && $this->request->getIsSecureConnection()) {
            $this->response->getHeaders()->set('Strict-Transport-Security', $this->strictTransportSecurity);
        }

        return parent::beforeAction($action);
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
