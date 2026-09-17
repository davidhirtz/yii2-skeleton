<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log\Traits;

trait MaskQueryParamsTrait
{
    /**
     * Query parameters whose value is a credential, masked wherever one appears in what a target is about to
     * write out. `yii\log\Target::$maskVars` cannot do this: it replaces a whole variable, and the token sits
     * inside `_SERVER.REQUEST_URI` — the one thing that says which page failed — beside `HTTP_REFERER` and
     * Sentry's `request.url` (monorepo issue #166).
     *
     * @var list<string>
     */
    public array $maskQueryParams = ['code'];

    /**
     * The value is matched up to the next separator rather than parsed out, so the subject may be a URL, a bare
     * query string or a whole dump of several, and nothing else in it is re-encoded on the way through. What
     * precedes the name is only ever asked *not* to be part of a longer one — anchoring on `?` and `&` misses a
     * bare `QUERY_STRING` where a dump has quoted it, while `passcode=` must not match `code`.
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
