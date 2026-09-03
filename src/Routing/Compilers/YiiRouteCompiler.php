<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Routing\Compilers;

use Hirtz\Skeleton\Routing\Route;
use Hirtz\Skeleton\Routing\RouteCompilerInterface;
use Hirtz\Skeleton\Routing\RouteMode;
use Override;
use yii\web\UrlRule;

/**
 * Ordering is not applied here: declarations keep their `position` key so
 * {@see \Hirtz\Skeleton\Web\UrlManager::buildRules()} sorts them together with legacy array rules.
 */
class YiiRouteCompiler implements RouteCompilerInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    #[Override]
    public function compile(Route ...$routes): array
    {
        return array_map($this->compileRoute(...), array_values($routes));
    }

    /**
     * {@see Route::$name} is deliberately not mapped onto `UrlRule::$name`: Yii defaults that property
     * to the pattern and {@see \Hirtz\Skeleton\Web\UrlManager::getImmutableRuleParams()} parses it as one.
     *
     * @return array<string, mixed>
     */
    public function compileRoute(Route $route): array
    {
        $route->validate();

        $declaration = [
            'pattern' => $this->compilePattern($route),
            'route' => $this->compilePlaceholders($route->action),
            'position' => $route->position,
        ];

        if (!$route->encodeParams) {
            $declaration['encodeParams'] = false;
        }

        if ($route->defaults !== []) {
            $declaration['defaults'] = $route->defaults;
        }

        if ($route->methods !== []) {
            $declaration['verb'] = $route->methods;
        }

        if ($route->host !== null) {
            $declaration['host'] = $route->host;
        }

        if ($route->suffix !== null) {
            $declaration['suffix'] = $route->suffix;
        }

        if ($route->mode !== RouteMode::Both) {
            $declaration['mode'] = $route->mode === RouteMode::ParseOnly
                ? UrlRule::PARSING_ONLY
                : UrlRule::CREATION_ONLY;
        }

        if ($route->ruleClass !== null) {
            $declaration = [...$declaration, ...$route->ruleOptions, 'class' => $route->ruleClass];
        }

        return $declaration;
    }

    protected function compilePattern(Route $route): string
    {
        if ($route->isRaw) {
            return $route->pattern;
        }

        return (string)preg_replace_callback(
            '/\{(\w+)}/',
            function (array $matches) use ($route): string {
                $placeholder = $matches[1];
                $constraint = $route->constraints[$placeholder] ?? null;

                return $constraint === null ? "<$placeholder>" : "<$placeholder:$constraint>";
            },
            $route->pattern
        );
    }

    protected function compilePlaceholders(string $subject): string
    {
        return (string)preg_replace('/\{(\w+)}/', '<$1>', $subject);
    }
}
