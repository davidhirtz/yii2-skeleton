<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Override;
use Yii;
use yii\base\Event;
use yii\base\Model;
use yii\base\Module;

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

    protected function stripWhitespaceFromHtml(string $html): string
    {
        return trim((string)preg_replace('/>\s+</', '><', $html));
    }

    /**
     * @param Model|array<int|string, mixed>|string $value
     */
    public function error(Model|array|string $value): static
    {
        if ($value instanceof Model) {
            $value = $value->getFirstErrors();
        }

        if ($value) {
            Application::current()->getSession()->addFlash('danger', $value);
        }

        return $this;
    }

    /**
     * @param Model|array<int|string, mixed>|string|null $value
     */
    public function success(Model|array|string|null $value, ?string $message = null): static
    {
        if ($value instanceof Model && !$value->hasErrors()) {
            $value = $message;
        }

        if ($value) {
            Application::current()->getSession()->addFlash('success', $value);
        }

        return $this;
    }

    /**
     * @param array<int|string, mixed>|string|null $value
     */
    public function warning(array|string|null $value): static
    {
        if ($value) {
            Application::current()->getSession()->addFlash('warning', $value);
        }

        return $this;
    }

    /**
     * @param Model|array<int|string, mixed>|string $value
     */
    public function errorOrSuccess(Model|array|string $value, string $message): static
    {
        if ($value instanceof Model ? $value->hasErrors() : !empty($value)) {
            $this->error($value);
        } else {
            $this->success($message);
        }

        return $this;
    }
}
