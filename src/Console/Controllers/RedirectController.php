<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Web\ErrorHandler;
use Override;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Cleans up broken redirect records.
 *
 * Only structural breakage is touched (a self-redirect, a cycle, a redirected target), never whether a target
 * resolves, since it may be a static file or another site.
 */
class RedirectController extends Controller
{
    /**
     * @var string comma-separated hosts a `request_uri` may be qualified by, defaults to the URL manager's; a
     * multi-tenant installation has to name them
     */
    public string $hosts = '';

    /**
     * @var bool whether to report what would change without writing anything
     */
    public bool $dryRun = false;

    /**
     * @var array<string, Redirect>
     */
    private array $redirects = [];

    /**
     * @var list<string>|null
     */
    private ?array $hostList = null;

    #[Override]
    public function options($actionID): array
    {
        return [...parent::options($actionID), 'hosts', 'dryRun'];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function optionAliases(): array
    {
        return [...parent::optionAliases(), 'd' => 'dryRun'];
    }

    /**
     * Deletes the redirects that can never resolve and shortens chains.
     *
     * A redirect never resolves when its target comes back to its own request URI or when it is a member of a cycle;
     * a target that is itself redirected is shortened to the end of its chain.
     */
    public function actionClean(): int
    {
        $this->loadRedirects();

        if (!$this->redirects) {
            $this->stdout('No redirects found.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $looping = [];
        $leading = [];
        $chained = [];

        foreach ($this->redirects as $redirect) {
            $chain = $this->follow($redirect);

            if ($this->isCycle($redirect, $chain)) {
                $looping[] = $redirect;
            } elseif ($this->leadsToCycle($chain)) {
                $leading[] = $redirect;
            } elseif ($chain) {
                $chained[$this->key($redirect->request_uri)] = $this->getChainTarget($redirect, $chain);
            }
        }

        $this->reportHosts();

        foreach ($leading as $redirect) {
            $this->warn("$redirect->request_uri -> $redirect->url leads into a cycle, left in place");
        }

        if (!$looping && !$chained) {
            $this->stdout('Nothing to clean up.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        if (!$this->confirmChanges(count($looping), count($chained))) {
            return ExitCode::OK;
        }

        $deleted = $this->deleteRedirects($looping);
        $flattened = $this->flattenRedirects($chained);

        $this->stdout(
            ($this->dryRun ? 'Would delete' : 'Deleted') . " $deleted, "
            . ($this->dryRun ? 'would shorten' : 'shortened') . " $flattened." . PHP_EOL,
            Console::FG_GREEN
        );

        return ExitCode::OK;
    }

    /**
     * Mirrors {@see ErrorHandler::redirectRequestUri()}; the last entry repeats an earlier request URI on a cycle.
     *
     * @return list<Redirect>
     */
    protected function follow(Redirect $redirect): array
    {
        $visited = [$this->key($redirect->request_uri) => true];
        $current = $redirect;
        $chain = [];

        while ($next = $this->findRedirectFor($current->url, $this->getHost($current))) {
            $chain[] = $next;

            if (isset($visited[$this->key($next->request_uri)])) {
                break;
            }

            $visited[$this->key($next->request_uri)] = true;
            $current = $next;
        }

        return $chain;
    }

    /**
     * Mirrors {@see ErrorHandler::findRedirectByRequestUri()}: a host-qualified record wins over a bare one.
     */
    protected function findRedirectFor(string $url, ?string $host): ?Redirect
    {
        if (str_contains($url, '://')) {
            $url = substr($url, strpos($url, '://') + 3);
            $host = strstr($url, '/', true) ?: $url;
            $url = (string)strstr($url, '/');
        }

        $path = trim($url, '/');

        if ($path === '') {
            return null;
        }

        $candidates = $host ? ["$host/$path", $path] : [$path];

        foreach ($candidates as $candidate) {
            if (isset($this->redirects[$this->key($candidate)])) {
                return $this->redirects[$this->key($candidate)];
            }
        }

        return null;
    }

    /**
     * A relative target resolves on the host its redirect was reached on, so one reached on another host gets it spelled out.
     *
     * @param non-empty-list<Redirect> $chain
     */
    protected function getChainTarget(Redirect $redirect, array $chain): string
    {
        $host = $this->getHost($redirect);
        $scheme = null;
        $url = $redirect->url;

        foreach ($chain as $next) {
            if ($nextHost = parse_url($url, PHP_URL_HOST)) {
                $host = $nextHost;
                $scheme = parse_url($url, PHP_URL_SCHEME);
            }

            $url = $next->url;
        }

        if (parse_url($url, PHP_URL_HOST) || $host === $this->getHost($redirect)) {
            return $url;
        }

        return ($scheme ?: 'https') . "://$host/" . ltrim($url, '/');
    }

    /**
     * @param list<Redirect> $chain
     */
    protected function isCycle(Redirect $redirect, array $chain): bool
    {
        return $chain && $this->key(end($chain)->request_uri) === $this->key($redirect->request_uri);
    }

    /**
     * @param list<Redirect> $chain
     */
    protected function leadsToCycle(array $chain): bool
    {
        $requestUris = array_map(fn (Redirect $redirect): string => $this->key($redirect->request_uri), $chain);
        return count($requestUris) !== count(array_unique($requestUris));
    }

    /**
     * @param list<Redirect> $redirects
     */
    protected function deleteRedirects(array $redirects): int
    {
        $count = 0;

        foreach ($redirects as $redirect) {
            $this->stdout("Removing $redirect->request_uri -> $redirect->url" . PHP_EOL);

            // one by one rather than `deleteAll()`, so the trail records them
            if ($this->dryRun || $redirect->delete()) {
                $count++;
                continue;
            }

            $this->warn("$redirect->request_uri could not be deleted");
        }

        return $count;
    }

    /**
     * @param array<string, string> $urls
     */
    protected function flattenRedirects(array $urls): int
    {
        $count = 0;

        foreach ($urls as $key => $url) {
            $redirect = $this->redirects[$key];
            $requestUri = $redirect->request_uri;
            $this->stdout("Shortening $requestUri -> $redirect->url to $url" . PHP_EOL);

            $redirect->url = $url;

            if ($this->dryRun || $redirect->update()) {
                $count++;
                continue;
            }

            $this->warn("$requestUri could not be shortened: " . implode(' ', $redirect->getErrorSummary(true)));
        }

        return $count;
    }

    protected function confirmChanges(int $looping, int $chained): bool
    {
        $this->stdout("Found $looping redirects that can never resolve and $chained that resolve through another." . PHP_EOL);

        return $this->dryRun || $this->confirm('Clean them up?', true);
    }

    /**
     * A bare host list is the one thing that silently narrows what this command can see, so it says so.
     */
    protected function reportHosts(): void
    {
        $hosts = $this->getHostList();

        if (!$hosts) {
            $this->warn('No hosts known, so a host-qualified request URI is read as a path. Pass --hosts to fix that.');
            return;
        }

        $this->stdout('Hosts: ' . implode(', ', $hosts) . PHP_EOL);
    }

    protected function getHost(Redirect $redirect): ?string
    {
        $host = strstr($redirect->request_uri, '/', true);
        return $host && in_array($host, $this->getHostList(), true) ? $host : null;
    }

    /**
     * The URL manager is consulted only when no host was passed, since Yii throws without a request to take one from.
     *
     * @return list<string>
     */
    protected function getHostList(): array
    {
        if ($this->hostList !== null) {
            return $this->hostList;
        }

        $hosts = array_values(array_filter(array_map(trim(...), explode(',', $this->hosts))));

        if ($hosts) {
            return $this->hostList = $hosts;
        }

        try {
            $host = parse_url((string)Yii::$app->getUrlManager()->getHostInfo(), PHP_URL_HOST);
        } catch (InvalidConfigException) {
            // a console application has no request to take the host from, and Yii refuses to guess one
            $host = null;
        }

        return $this->hostList = array_filter([$host]);
    }

    protected function loadRedirects(): void
    {
        $this->redirects = Redirect::find()
            ->indexBy(fn (Redirect $redirect): string => $this->key($redirect->request_uri))
            ->all();
    }

    /**
     * The column's collation compares request URIs case-insensitively, and so does the error handler's lookup.
     */
    protected function key(string $requestUri): string
    {
        return mb_strtolower($requestUri);
    }

    protected function warn(string $message): void
    {
        $this->stdout($message . PHP_EOL, Console::FG_YELLOW);
    }
}
