<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Widgets\Panels\InfoList;
use Override;
use Yii;

class ServerInfo extends InfoList
{
    #[Override]
    protected function configure(): void
    {
        if (!$this->rows) {
            $this->addServerRow();
            $this->addTrustedHostsRow();
            $this->addMailerRow();
            $this->addTimeZoneRow();
        }

        parent::configure();
    }

    protected function addServerRow(): void
    {
        $software = $_SERVER['SERVER_SOFTWARE'] ?? null;

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_SERVER'),
            $this->getValue(
                is_string($software) && $software !== '' ? $software : PHP_SAPI,
                (string)php_uname('n'),
            ),
        );
    }

    /**
     * `Request::filterHeaders()` strips every `X-Forwarded-*` header while no host is trusted, so the forwarded
     * header a proxy sent is only visible in `$_SERVER` — which is what makes the misconfiguration invisible.
     */
    protected function addTrustedHostsRow(): void
    {
        $trustedHosts = Yii::$app->getRequest()->trustedHosts;
        $forwarded = $this->findForwardedHeader();

        if (!$trustedHosts && $forwarded !== null) {
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS'),
                $this->getValue(
                    Span::make()
                        ->class('badge badge-warning')
                        ->text(Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_MISSING')),
                    Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_HINT', ['header' => $forwarded]),
                ),
            );

            return;
        }

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS'),
            $this->getValue(
                $trustedHosts ? implode(' · ', $trustedHosts) : Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_NONE'),
                Yii::$app->getRequest()->getIsSecureConnection()
                    ? Yii::t('skeleton', 'SYSTEM_CONNECTION_SECURE')
                    : Yii::t('skeleton', 'SYSTEM_CONNECTION_INSECURE'),
            ),
        );
    }

    protected function findForwardedHeader(): ?string
    {
        foreach (['X-Forwarded-For', 'X-Forwarded-Proto', 'Forwarded'] as $header) {
            if (!empty($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))])) {
                return $header;
            }
        }

        return null;
    }

    /**
     * The DSN carries the credentials, so only its scheme, host and port are reported.
     */
    protected function addMailerRow(): void
    {
        $mailer = Yii::$app->getMailer();

        if ($mailer->useFileTransport) {
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_MAILER'),
                $this->getValue(Yii::t('skeleton', 'SYSTEM_MAILER_FILE_TRANSPORT'), $mailer->fileTransportPath),
            );

            return;
        }

        $dsn = Yii::$app->params['mailerDsn'] ?? 'sendmail://default';
        $parts = is_string($dsn) ? parse_url($dsn) : false;

        $transport = $parts === false || !isset($parts['scheme'])
            ? Yii::t('skeleton', 'COMMON_UNKNOWN')
            : $parts['scheme'] . '://' . ($parts['host'] ?? '')
                . (isset($parts['port']) ? ':' . $parts['port'] : '');

        $this->addRow(Yii::t('skeleton', 'SYSTEM_MAILER'), $transport);
    }

    protected function addTimeZoneRow(): void
    {
        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_TIME_ZONE'),
            $this->getValue(
                Yii::$app->timeZone,
                Yii::$app->getFormatter()->asDatetime(time(), 'php:Y-m-d H:i:s'),
            ),
        );
    }
}
