<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use yii\base\BaseObject;

/**
 * A nonced `style-src` blocks every `<style>` swapped in later (it carries its own response's nonce), so it suits
 * full page loads alone; `'strict-dynamic'` makes hosts in `script-src` unnecessary.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP
 */
class ContentSecurityPolicy extends BaseObject
{
    /**
     * The nonce is added to `script-src` (and a style directive) when the header is built.
     *
     * @var array<string, list<string>>
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
     * `null` removes the directive.
     *
     * @param list<string>|null $sources
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
