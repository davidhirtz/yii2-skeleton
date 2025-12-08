<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets;

use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Span;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagLinkTrait;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\widgets\traits\UserWidgetTrait;
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
