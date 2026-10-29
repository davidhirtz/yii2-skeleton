<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Setup;

use Closure;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Setup extends Component
{
    /**
     * `null` drops a seeder a bundle registered.
     *
     * @var array<string, class-string<Seeder>|array{class: class-string<Seeder>}|null>
     */
    public array $seeders = [];

    public static function getComponent(): self
    {
        /** @var self $setup */
        $setup = Yii::$app->get('setup');
        return $setup;
    }

    /**
     * @param array<array-key, mixed> $config
     * @return list<string>
     */
    public function getUnknownKeys(array $config): array
    {
        return array_map(strval(...), array_keys(array_diff_key($config, array_filter($this->seeders))));
    }

    /**
     * A closure in the config is called just before its seeder runs, so it can find what an earlier one inserted.
     *
     * @param array<array-key, mixed> $config
     * @return list<Seeder>
     */
    public function run(array $config): array
    {
        $seeders = [];

        foreach ($this->getSeeders() as $key => $seeder) {
            $transaction = Yii::$app->getDb()->beginTransaction();

            try {
                $value = $config[$key] ?? null;
                $value = $value instanceof Closure ? $value() : $value;

                if ($value !== null && !is_array($value)) {
                    throw new InvalidConfigException("The setup key \"$key\" must be an array or a closure returning one.");
                }

                $seeder->run($value);
                $transaction->commit();
            } catch (Throwable $exception) {
                $transaction->rollBack();
                throw $exception;
            }

            $seeders[] = $seeder;
        }

        return $seeders;
    }

    /**
     * @return array<string, Seeder>
     */
    protected function getSeeders(): array
    {
        $seeders = [];

        foreach (array_filter($this->seeders) as $key => $definition) {
            $seeders[$key] = Yii::createObject($definition);
        }

        $sorted = [];

        foreach (array_keys($seeders) as $key) {
            $this->sort($key, $seeders, $sorted, []);
        }

        return $sorted;
    }

    /**
     * @param array<string, Seeder> $seeders
     * @param array<string, Seeder> $sorted
     * @param list<string> $path
     */
    private function sort(string $key, array $seeders, array &$sorted, array $path): void
    {
        if (isset($sorted[$key]) || !isset($seeders[$key])) {
            return;
        }

        if (in_array($key, $path, true)) {
            throw new InvalidConfigException('The setup seeders depend on each other: ' . implode(' → ', [...$path, $key]));
        }

        foreach ($seeders[$key]->dependencies as $dependency) {
            $this->sort($dependency, $seeders, $sorted, [...$path, $key]);
        }

        $sorted[$key] = $seeders[$key];
    }
}
