<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Credentials;

use Closure;
use Yii;
use yii\base\Component;

/**
 * The `credentials` application component. Each bundle appends the services it calls through its default config, as
 * closures, since a label is a `Yii::t()` result.
 */
class Credentials extends Component
{
    /**
     * @var list<Closure(): (CredentialProvider|list<CredentialProvider>|null)> a closure may answer several providers,
     * or `null` for none
     */
    public array $providers = [];

    /**
     * @var array<string, CredentialProvider>|null
     */
    private ?array $resolvedProviders = null;

    public static function getComponent(): self
    {
        /** @var self $credentials */
        $credentials = Yii::$app->get('credentials');
        return $credentials;
    }

    /**
     * @return array<string, CredentialProvider>
     */
    public function getProviders(): array
    {
        if ($this->resolvedProviders === null) {
            $this->resolvedProviders = [];

            foreach ($this->providers as $closure) {
                $providers = $closure();

                foreach (is_array($providers) ? $providers : array_filter([$providers]) as $provider) {
                    $this->resolvedProviders[$provider->getName()] = $provider;
                }
            }
        }

        return $this->resolvedProviders;
    }

    public function getProvider(string $name): ?CredentialProvider
    {
        return $this->getProviders()[$name] ?? null;
    }
}
