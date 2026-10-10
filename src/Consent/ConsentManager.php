<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent;

use Closure;
use Hirtz\Skeleton\Consent\Categories\AnalyticsCategory;
use Hirtz\Skeleton\Consent\Categories\ExternalCategory;
use Hirtz\Skeleton\Consent\Categories\RequiredCategory;
use Hirtz\Skeleton\Consent\Services\GoogleAnalytics;
use Hirtz\Skeleton\Consent\Services\GoogleTagManager;
use Hirtz\Skeleton\Consent\Services\YouTube;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Modules\Admin\Module;
use Override;
use Yii;
use yii\base\Component;

class ConsentManager extends Component
{
    /**
     * @var list<Category>|Closure(self): list<Category>|null
     */
    public array|Closure|null $categories = null;

    /**
     * At most 23 characters: the log's column holds 32 including the fingerprint.
     */
    public string $version = '1';

    public ?string $tagId = null;

    public string $privacyUrl = '/privacy';
    public string $imprintUrl = '/imprint';

    /**
     * @var list<Category>|null
     */
    private ?array $resolvedCategories = null;

    public static function current(): self
    {
        /** @var self $consent */
        $consent = Yii::$app->get('consent');
        return $consent;
    }

    #[Override]
    public function init(): void
    {
        $tagId = Yii::$app->params['gtagId'] ?? null;
        $this->tagId ??= is_string($tagId) && $tagId !== '' ? $tagId : null;

        parent::init();
    }

    /**
     * @return list<Category>
     */
    public function getCategories(): array
    {
        if ($this->resolvedCategories === null) {
            $categories = $this->categories instanceof Closure
                ? ($this->categories)($this)
                : $this->categories ?? $this->getDefaultCategories();

            $this->resolvedCategories = array_values(array_filter(
                $categories,
                fn (Category $category): bool => $category->isRequired() || $category->getServices(),
            ));
        }

        return $this->resolvedCategories;
    }

    /**
     * @return list<Category>
     */
    public function getDefaultCategories(): array
    {
        return [
            RequiredCategory::make(),
            AnalyticsCategory::make()->services(...$this->getTagServices()),
            ExternalCategory::make()->services(YouTube::make()),
        ];
    }

    /**
     * @return list<Service>
     */
    public function getTagServices(): array
    {
        if (!$this->tagId) {
            return [];
        }

        return str_starts_with($this->tagId, 'GTM-')
            ? [GoogleTagManager::make(), GoogleAnalytics::make()]
            : [GoogleAnalytics::make()];
    }

    public function getVersion(): string
    {
        $fingerprint = [];

        foreach ($this->getCategories() as $category) {
            foreach ($category->getServices() as $service) {
                $cookies = array_map(
                    fn (Cookie $cookie): string => $cookie->getName() . ':' . $cookie->getDuration(),
                    $service->getCookies(),
                );

                $fingerprint[] = implode('|', [$category->getId(), $service->getProvider(), ...$cookies]);
            }
        }

        return $this->version . '-' . substr(md5(implode("\n", $fingerprint)), 0, 8);
    }

    /**
     * @return array<string, mixed>
     */
    public function getClientConfig(): array
    {
        $cookies = [];
        $declared = [];

        foreach ($this->getCategories() as $category) {
            $names = $category->getFirstPartyCookieNames();
            $declared = [...$declared, ...$names];

            if (!$category->isRequired()) {
                $cookies[$category->getId()] = $names;
            }
        }

        return [
            'version' => $this->getVersion(),
            'logUrl' => Module::current()->enableConsentLog ? Url::to(['/consent/create']) : null,
            'cookies' => $cookies,
            'declared' => $declared,
            'debug' => YII_DEBUG,
        ];
    }
}
