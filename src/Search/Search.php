<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Modules\Admin\Module;
use Yii;
use yii\base\Component;

/**
 * The `search` application component. Searchable classes are registered from each bundle's `Bootstrap` via
 * `extendComponent()`, never discovered by scanning: a model nobody configured would be missed either way.
 */
class Search extends Component
{
    /**
     * @var array<array-key, class-string<SearchableInterface>|float> the searchable classes, either as a list or
     * keyed by class with a weight overriding {@see SearchableInterface::getSearchWeight()}
     */
    public array $models = [];

    /**
     * @var class-string<SearchDriverInterface>|array<string, mixed>
     */
    public string|array $driver = MysqlDriver::class;

    private ?SearchDriverInterface $driverInstance = null;

    /**
     * @var list<class-string<SearchableInterface>>|null
     */
    private ?array $modelClasses = null;

    /**
     * @var array<class-string<SearchableInterface>, float>|null
     */
    private ?array $weights = null;

    public static function getComponent(): self
    {
        /** @var self $search */
        $search = Yii::$app->get('search');
        return $search;
    }

    /**
     * Read off the module instance, which the console application registers too.
     */
    public function isEnabled(): bool
    {
        $module = Yii::$app->getModule('admin');
        return $module instanceof Module && $module->enableSearch;
    }

    public function getDriver(): SearchDriverInterface
    {
        /** @var SearchDriverInterface $driver */
        $driver = $this->driverInstance ??= Yii::createObject($this->driver);
        return $driver;
    }

    /**
     * @return list<class-string<SearchableInterface>>
     */
    public function getModelClasses(): array
    {
        $this->resolveModels();
        return $this->modelClasses;
    }

    /**
     * @param class-string<SearchableInterface> $modelClass
     */
    public function getWeight(string $modelClass): ?float
    {
        $this->resolveModels();
        return $this->weights[$modelClass] ?? null;
    }

    public function index(SearchableInterface ...$models): void
    {
        $documents = [];

        foreach ($models as $model) {
            $documents = [...$documents, ...$model->getSearchDocuments()];
        }

        $this->getDriver()->index(...$documents);
    }

    public function search(SearchRequest $request): SearchResultSet
    {
        return $this->getDriver()->search($request);
    }

    public function count(SearchRequest $request): int
    {
        return $this->getDriver()->count($request);
    }

    /**
     * @phpstan-assert !null $this->modelClasses
     * @phpstan-assert !null $this->weights
     */
    private function resolveModels(): void
    {
        if ($this->modelClasses !== null) {
            return;
        }

        $this->modelClasses = [];
        $this->weights = [];

        foreach ($this->models as $key => $value) {
            if (is_string($key)) {
                /** @var class-string<SearchableInterface> $key */
                $this->modelClasses[] = $key;
                $this->weights[$key] = (float)$value;
                continue;
            }

            /** @var class-string<SearchableInterface> $value */
            $this->modelClasses[] = $value;
        }

        $this->modelClasses = array_values(array_unique($this->modelClasses));
    }
}
