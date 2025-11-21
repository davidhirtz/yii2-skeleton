<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use Override;
use Yii;
use yii\base\Model;

/**
 * @property Request $request
 * @method View getView()
 */
class Controller extends \yii\web\Controller
{
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

    #[Override]
    public function beforeAction($action): bool
    {
        if ($this->contentSecurityPolicy) {
            $this->response->getHeaders()->set('Content-Security-Policy', $this->contentSecurityPolicy);
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

    public function error(Model|array|string $value): static
    {
        if ($value instanceof Model) {
            $value = $value->getFirstErrors();
        }

        if ($value) {
            Yii::$app->getSession()->addFlash('danger', $value);
        }

        return $this;
    }

    public function success(Model|array|string|null $value, ?string $message = null): static
    {
        if ($value instanceof Model && !$value->hasErrors()) {
            $value = $message;
        }

        if ($value) {
            Yii::$app->getSession()->addFlash('success', $value);
        }

        return $this;
    }

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
