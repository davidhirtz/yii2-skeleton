<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\I18n\Lang;
use DateTime;
use DateTimeZone;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;
use yii\web\Session;

class TimezoneModal extends Widget
{
    protected Session $session;

    final protected const string SESSION_KEY = 'timezone_validated';

    public function __construct(array $config = [])
    {
        $this->session = Yii::$app->getSession();
        parent::__construct($config);
    }

    protected function renderContent(): string|Stringable
    {
        return $this->showModal() ? $this->getModal() : '';
    }

    protected function showModal(): bool
    {
        if ($this->webuser->getIsGuest() || $this->session->get(self::SESSION_KEY)) {
            return false;
        }

        $this->session->set(self::SESSION_KEY, true);
        return true;
    }

    public function getModal(): ?Modal
    {
        $timeZone = new DateTimeZone(Yii::$app->getFormatter()->timeZone);
        $utcDateTime = new DateTime('now', new DateTimeZone('UTC'));

        return Modal::make()
            ->attribute('data-timezone-offset', $timeZone->getOffset($utcDateTime))
            ->content($this->getModalContent())
            ->footer($this->getModalFooter())
            ->title($this->getModalTitle());
    }

    protected function getModalTitle(): string
    {
        return Lang::t('skeleton', 'TIMEZONE_MODAL_TIMEZONE_CHANGE_DETECTED');
    }

    protected function getModalContent(): string|Stringable
    {
        return P::make()
            ->content(Lang::t('skeleton', 'TIMEZONE_MODAL_WE_HAVE_DETECTED_A_CHANGE_IN', [
                'timezone' => Span::make()
                    ->text(Yii::$app->getTimeZone())
                    ->class('strong'),
                'tag' => Span::make()
                    ->attribute('data-timezone', '')
                    ->class('strong'),
            ]));
    }

    /**
     * @see AccountController::actionTimezone()
     */
    protected function getModalFooter(): string|Stringable
    {
        return Button::make()
            ->primary()
            ->content(Lang::t('skeleton', 'TIMEZONE_MODAL_UPDATE_TIMEZONE'))
            ->post(['/admin/account/timezone', 'redirect' => Yii::$app->getRequest()->getUrl()])
            ->attribute('data-timezone-button', '');
    }
}
