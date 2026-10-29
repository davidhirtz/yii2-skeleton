<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Panels\InfoList;
use Override;
use Stringable;
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

    /**
     * Behind {@see Module::AUTH_SYSTEM}, since `php_uname()` names the machine rather than the site.
     */
    protected function addServerRow(): void
    {
        $software = $_SERVER['SERVER_SOFTWARE'] ?? null;

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_SERVER_LABEL'),
            $this->getValue(
                is_string($software) && $software !== '' ? $software : PHP_SAPI,
                $this->webuser->can(Module::AUTH_SYSTEM) ? (string)php_uname('n') : null,
            ),
        );
    }

    /**
     * Reads `$_SERVER`, since `Request::filterHeaders()` strips every `X-Forwarded-*` header while no host is trusted.
     */
    protected function addTrustedHostsRow(): void
    {
        $trustedHosts = Application::current()->getRequest()->trustedHosts;
        $forwarded = $this->findForwardedHeader();

        if (!$trustedHosts && $forwarded !== null) {
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_LABEL'),
                $this->getValue(
                    Span::make()
                        ->class('badge badge-warning')
                        ->text(Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_MISSING_TEXT')),
                    Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_HINT', ['header' => $forwarded]),
                ),
            );

            return;
        }

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_LABEL'),
            $this->getValue(
                $trustedHosts ? $this->getTrustedHostsValue($trustedHosts) : Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_NONE_TEXT'),
                Application::current()->getRequest()->getIsSecureConnection()
                    ? Yii::t('skeleton', 'SYSTEM_CONNECTION_SECURE_TEXT')
                    : Yii::t('skeleton', 'SYSTEM_CONNECTION_INSECURE_TEXT'),
            ),
        );
    }

    /**
     * A host is a value (every secure header trusted) or a key holding its trusted headers.
     *
     * @param array<int|string, mixed> $trustedHosts
     */
    protected function getTrustedHostsValue(array $trustedHosts): string|Stringable
    {
        $groups = [];

        foreach ($trustedHosts as $host => $headers) {
            if (!is_array($headers)) {
                $host = $headers;
                $headers = null;
            }

            $headers = $headers !== null ? implode(', ', array_map(strval(...), $headers)) : '';
            $groups[$headers][] = (string)$host;
        }

        if (array_keys($groups) === ['']) {
            return implode("\u{00A0}· ", $groups['']);
        }

        $content = [];

        foreach ($groups as $headers => $hosts) {
            $content[] = Div::make()->text(implode("\u{00A0}· ", $hosts));

            if ($headers !== '') {
                $content[] = Div::make()
                    ->class('form-hint')
                    ->text(Yii::t('skeleton', 'SYSTEM_TRUSTED_HOSTS_HEADERS_TEXT', ['headers' => $headers]));
            }
        }

        return Div::make()->content(...$content);
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
     * Only scheme, host and port, since the DSN carries the credentials.
     */
    protected function addMailerRow(): void
    {
        $mailer = Yii::$app->getMailer();

        if ($mailer->useFileTransport) {
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_MAILER_LABEL'),
                $this->getValue(Yii::t('skeleton', 'SYSTEM_MAILER_FILE_TRANSPORT_TEXT'), $mailer->fileTransportPath),
            );

            return;
        }

        $dsn = Yii::$app->params['mailerDsn'] ?? 'sendmail://default';
        $parts = is_string($dsn) ? parse_url($dsn) : false;

        $transport = $parts === false || !isset($parts['scheme'])
            ? Yii::t('skeleton', 'SYSTEM_UNKNOWN_TEXT')
            : $parts['scheme'] . '://' . ($parts['host'] ?? '')
                . (isset($parts['port']) ? ':' . $parts['port'] : '');

        $error = $mailer->getTransportError();

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_MAILER_LABEL'),
            $error === null
                ? $transport
                : $this->getValue(Span::make()->class('badge badge-error')->text($transport), $error),
        );
    }

    protected function addTimeZoneRow(): void
    {
        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_TIME_ZONE_LABEL'),
            $this->getValue(
                Yii::$app->timeZone,
                Yii::$app->getFormatter()->asDatetime(time(), 'php:Y-m-d H:i:s'),
            ),
        );
    }
}
