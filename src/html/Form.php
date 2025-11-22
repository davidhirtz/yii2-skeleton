<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Override;
use Yii;

class Form extends Tag
{
    use TagContentTrait;

    public function action(array|string|null $action): static
    {
        return $this->attribute('action', $action !== null ? Url::to($action) : null);
    }

    public function method(string $method): static
    {
        return $this->attribute('method', strtolower($method));
    }

    #[\Override]
    protected function before(): string
    {
        $this->attributes['method'] ??= 'post';
        return parent::before();
    }

    #[Override]
    protected function renderContent(): string
    {
        if ('post' === $this->attributes['method']) {
            $this->content = [
                TextInput::make()
                    ->type('hidden')
                    ->name(Yii::$app->request->csrfParam)
                    ->value(Yii::$app->request->getCsrfToken()),
                ...$this->content
            ];
        }

        return implode('', $this->content);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'form';
    }
}
