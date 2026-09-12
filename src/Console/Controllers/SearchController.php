<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Search\Search;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manages the fulltext search index.
 */
class SearchController extends Controller
{
    use ControllerTrait;

    /**
     * @var int the number of records read and indexed at a time
     */
    public int $batchSize = 500;

    /**
     * Rebuilds the search index. Not a single transaction, so a crash leaves a partial index rather than a locked
     * table, and the fulltext index sees the batches as they commit.
     *
     * @param string|null $models a comma-separated list of class or short names, defaults to every registered class
     */
    public function actionRebuild(?string $models = null): int
    {
        if (!$this->isEnabled()) {
            return ExitCode::CONFIG;
        }

        $driver = $this->getSearch()->getDriver();

        foreach ($this->getModelClasses($models) as $modelClass) {
            $this->interactiveStartStdout("Indexing $modelClass ... ");
            $driver->clear($modelClass);

            $count = 0;

            foreach ($modelClass::findSearchable()->batch($this->batchSize) as $records) {
                $documents = [];

                foreach ($records as $record) {
                    if ($record instanceof SearchableInterface && $record->isSearchable()) {
                        $documents = [...$documents, ...$record->getSearchDocuments($modelClass)];
                    }
                }

                if ($documents) {
                    $driver->index(...$documents);
                    $count += count($documents);
                }
            }

            $this->interactiveStdout(Yii::$app->getFormatter()->asInteger($count) . ' documents');
            $this->interactiveDoneStdout();
        }

        return ExitCode::OK;
    }

    /**
     * Removes the search index.
     *
     * @param string|null $models a comma-separated list of class or short names, defaults to every registered class
     */
    public function actionClear(?string $models = null): int
    {
        if (!$this->isEnabled()) {
            return ExitCode::CONFIG;
        }

        $driver = $this->getSearch()->getDriver();

        if ($models === null) {
            $this->interactiveStartStdout('Clearing search index ... ');
            $driver->clear();
            $this->interactiveDoneStdout();

            return ExitCode::OK;
        }

        foreach ($this->getModelClasses($models) as $modelClass) {
            $this->interactiveStartStdout("Clearing $modelClass ... ");
            $driver->clear($modelClass);
            $this->interactiveDoneStdout();
        }

        return ExitCode::OK;
    }

    protected function isEnabled(): bool
    {
        if ($this->getSearch()->isEnabled()) {
            return true;
        }

        $this->stderr('The search index is disabled, set `enableSearch` on the admin module to use it.' . PHP_EOL, Console::FG_RED);
        return false;
    }

    /**
     * @return list<class-string<ActiveRecord&SearchableInterface>> the registered classes, resolved through the
     * container so they match what the behavior writes
     */
    protected function getModelClasses(?string $models): array
    {
        $names = $models !== null ? array_filter(array_map(trim(...), explode(',', $models))) : [];
        $classes = [];

        foreach ($this->getSearch()->getModelClasses() as $modelClass) {
            /** @var ActiveRecord&SearchableInterface $model */
            $model = Yii::createObject($modelClass);

            if (!$names || $this->matchesName($model::class, $names)) {
                $classes[] = $model::class;
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * @param list<string> $names
     */
    protected function matchesName(string $modelClass, array $names): bool
    {
        $shortName = substr((string)strrchr("\\$modelClass", '\\'), 1);

        foreach ($names as $name) {
            if (strcasecmp($name, $modelClass) === 0 || strcasecmp($name, $shortName) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function getSearch(): Search
    {
        return Search::getComponent();
    }
}
