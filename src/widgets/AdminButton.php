<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\html\A;
use Stringable;
use Yii;
use yii\helpers\Html;

class AdminButton extends Widget
{
    public string $buttonColor = '#000';
    public string $buttonColorActive = '#FA7D7E';
    public string $buttonPosition = 'right';

    public string $icon = '<svg viewBox="0 0 64 64"><path d="M25,26c0-0.6,0.4-1,1-1h6c0.6,0,1-0.4,1-1s-0.4-1-1-1h-6c-1.7,0-3,1.3-3,3v12c0,1.7,1.3,3,3,3h12c1.7,0,3-1.3,3-3v-6c0-0.6-0.4-1-1-1s-1,0.4-1,1v6c0,0.6-0.4,1-1,1H26c-0.6,0-1-0.4-1-1V26z"/><path d="M37.9,21.7c0.9-0.9,2.5-0.9,3.4,0l1,1c0.9,0.9,0.9,2.5,0,3.4l-8.1,8.1c-0.2,0.2-0.4,0.3-0.6,0.3L30.1,35c-0.3,0-0.6-0.1-0.8-0.3c-0.2-0.2-0.3-0.5-0.3-0.8l0.5-3.5c0-0.2,0.1-0.4,0.3-0.6L37.9,21.7z"/></svg>';
    public string $iconColor = '#fff';
    public string $iconColorActive = '#000';

    public string $adminLinkZIndex = '3';
    public string $overlayBackgroundColor = '#f8afaf80';

    private static bool $is_Registered = false;

    /**
     * @var bool whether to toggle the button opacity on hover, if `false` the button will always be visible
     */
    public bool $toggleButtonOpacity = true;

    public function init(): void
    {
        $this->registerCss();
        parent::init();
    }

    protected function renderContent(): Stringable
    {
        return A::make()
            ->content($this->icon)
            ->href('/admin')
            ->class('admin-btn')
            ->attribute('onclick', 'document.documentElement.classList.toggle(\'is-admin\');return false')
            ->target('_blank');
    }

    public function registerCss(): void
    {
        if (self::$is_Registered) {
            return;
        }

        self::$is_Registered = true;

        if ($this->toggleButtonOpacity) {
            $btnToggle = <<<CSS
.admin-btn {
            opacity: 0
        }
    
        .admin-btn:hover {
            opacity: 1
        }
CSS;
        } else {
            $btnToggle = '';
        }

        Yii::$app->getView()->registerCss(
            <<<CSS
:root {
    --admin-btn: 40px;
    --admin-pos: 15px
}

.admin-btn {
    position: fixed;
    bottom: var(--admin-pos);
    $this->buttonPosition: var(--admin-pos);
    width: var(--admin-btn);
    height: var(--admin-btn);
    border-radius: 50%;
    background-color: $this->buttonColor;
    transition: background-color .2s, opacity .2s;
    z-index: 1000
}

.admin-btn svg {
    width: 100%;
    height: 100%
}

.admin-btn path {
    fill: $this->iconColor;
    transition: fill .2s
}

.is-admin .admin-btn {
    background-color: $this->buttonColorActive;
    opacity: 1
}

.is-admin .admin-btn path {
    fill: $this->iconColorActive;
}

.admin {
    display: none
}

.is-admin .admin {
    display: block;
    background-color: $this->overlayBackgroundColor;
    z-index: $this->adminLinkZIndex
}

@media (min-width: 768px) {
    :root {
        --admin-btn: 64px;
        --admin-pos: 20px
    }
}

@media (hover: hover) {
    $btnToggle

    .is-admin .admin {
        opacity: 0
    }

    .is-admin .admin:hover {
        opacity: 1
    }
}
CSS
        );
    }
}
