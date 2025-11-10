<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Override;
use Yii;

class Form extends Tag
{
    use TagContentTrait;

    protected array $attributes = [
        'method' => 'post',
    ];

    public function action(array|string|null $action): static
    {
        return $this->attribute('action', $action !== null ? Url::to($action) : null);
    }

    protected function renderContent(): string
    {
        if ($this->attributes['method'] === 'post') {
            $this->content[] = Input::make()
                ->type('hidden')
                ->name(Yii::$app->request->csrfParam)
                ->value(Yii::$app->request->getCsrfToken());
        }

        return implode('', $this->content);
    }

    #[Override]
    protected function getName(): string
    {
        return 'form';
    }
}
