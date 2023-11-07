<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\base\Model;

/**
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

    public function beforeAction($action): bool
    {
        if ($this->contentSecurityPolicy) {
            Yii::$app->getResponse()->getHeaders()->set('Content-Security-Policy', $this->contentSecurityPolicy);
        }

        return parent::beforeAction($action);
    }

    public function render($view, $params = []): string
    {
        $content = parent::render($view, $params);
        return $this->spacelessOutput ? trim(preg_replace('/>\s+</', '><', $content)) : $content;
    }

    public function error(Model|array|string $value): bool
    {
        if ($value instanceof Model) {
            $value = $value->getFirstErrors();
        }

        if ($value) {
            Yii::$app->getSession()->addFlash('error', $value);
            return true;
        }

        return false;
    }

    public function success(Model|array|string $value, ?string $message = null): bool
    {
        if ($value instanceof Model && !$value->hasErrors()) {
            $value = $message;
        }

        if ($value) {
            Yii::$app->getSession()->addFlash('success', $value);
            return true;
        }

        return false;
    }
}