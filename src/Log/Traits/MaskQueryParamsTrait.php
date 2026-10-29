<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log\Traits;

trait MaskQueryParamsTrait
{
    /**
     * `maskVars` replaces a whole variable, while the token sits inside `REQUEST_URI`, `HTTP_REFERER` and `request.url`
     * (#166).
     *
     * @var list<string>
     */
    public array $maskQueryParams = ['code'];

    /**
     * Matched up to the next separator rather than parsed, so the subject may be a whole dump, and `passcode=` must not
     * match `code`.
     */
    protected function maskQueryParamValues(string $subject): string
    {
        if (!$this->maskQueryParams) {
            return $subject;
        }

        $names = implode('|', array_map(preg_quote(...), $this->maskQueryParams));
        $masked = preg_replace('~(?<![\w.-])(' . $names . ')=[^&\s\'"]*~i', '$1=***', $subject);

        return is_string($masked) ? $masked : $subject;
    }
}
