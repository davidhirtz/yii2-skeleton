<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Span;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use Stringable;
use Yii;

class Username extends Widget
{
    use TagAttributesTrait;
    use TagLinkTrait;
    use UserWidgetTrait;

    public function clickable(): static
    {
        if ($this->user && Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user])) {
            $this->attributes['href'] ??= $this->user->getAdminRoute();
        }

        return $this;
    }

    protected function prepareAttributes(): void
    {
        if (!$this->user) {
            $this->addClass('text-invalid');
        }
    }

    protected function renderContent(): string|Stringable
    {
        $text = $this->user?->getUsername() ?? Yii::t('skeleton', 'Deleted');

        return array_key_exists('href', $this->attributes)
            ? A::make()
                ->attributes($this->attributes)
                ->text($text)
            : Span::make()
                ->attributes($this->attributes)
                ->content($text);
    }
}
