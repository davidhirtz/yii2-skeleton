<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use yii\base\BaseObject;

/**
 * The strict `Content-Security-Policy` of the admin, as directives a project or bundle extends with `addSource()`.
 *
 * Scripts are trusted by a nonce, which `Web\View` stamps on every script it renders once a controller sends the
 * policy: `'strict-dynamic'` extends that trust to whatever a trusted script loads itself (TinyMCE's plugins, a map,
 * a script htmx swaps in), so their hosts need no listing in `script-src`, where a browser ignores them anyway.
 *
 * Styles are unrestricted unless a project adds `style-src` (or `style-src-elem`): the nonce then joins it, which
 * `Web\View` stamps on every `<style>` it renders. Not where the directive allows `'unsafe-inline'`, which a nonce
 * would switch off, `style` attributes included. A `<style>` swapped in later carries its own response's nonce, not
 * the page's, and is blocked: such a policy suits styles of full page loads.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP
 */
class ContentSecurityPolicy extends BaseObject
{
    /**
     * @var array<string, list<string>> the directives and their sources, the nonce is added to `script-src` (and a
     *     style directive) when the header is built
     */
    public array $directives = [
        'script-src' => ["'self'", "'strict-dynamic'"],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'frame-ancestors' => ["'self'"],
    ];

    private ?string $nonce = null;

    public function addSource(string $directive, string ...$sources): static
    {
        $this->directives[$directive] = array_values(array_unique([...$this->directives[$directive] ?? [], ...$sources]));
        return $this;
    }

    /**
     * @param list<string>|null $sources `null` removes the directive
     */
    public function setDirective(string $directive, ?array $sources): static
    {
        if ($sources === null) {
            unset($this->directives[$directive]);
        } else {
            $this->directives[$directive] = $sources;
        }

        return $this;
    }

    /**
     * One per request, which is why a page cached as a whole cannot send this policy: every visitor would share it.
     */
    public function getNonce(): string
    {
        return $this->nonce ??= base64_encode(random_bytes(18));
    }

    public function getPolicy(): string
    {
        $policy = [];

        foreach ($this->directives as $directive => $sources) {
            if ($directive === 'script-src' || $this->isNoncedStyleDirective($directive, $sources)) {
                $sources = [...$sources, "'nonce-{$this->getNonce()}'"];
            }

            $policy[] = trim("$directive " . implode(' ', $sources));
        }

        return implode('; ', $policy);
    }

    /**
     * @param list<string> $sources
     */
    protected function isNoncedStyleDirective(string $directive, array $sources): bool
    {
        return in_array($directive, ['style-src', 'style-src-elem'], true)
            && !in_array("'unsafe-inline'", $sources, true);
    }
}
