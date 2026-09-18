<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Registry\RegistryClient;
use Hirtz\Skeleton\Registry\Report;
use Override;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\Json;

/**
 * Reports this installation to the version registry.
 */
class RegistryController extends Controller
{
    /**
     * @var string|null the installation's URL, for a console application whose URL manager has no `hostInfo`
     */
    public ?string $url = null;

    /**
     * @var bool whether an unreachable or refusing registry fails the command. A deploy runs the push last and
     * must not go red because the registry is down, so the default is a warning and exit code 0.
     */
    public bool $strict = false;

    #[Override]
    public function options($actionID): array
    {
        return [
            ...parent::options($actionID),
            'url',
            ...($actionID === 'push' ? ['strict'] : []),
        ];
    }

    /**
     * Posts the report to `params.registryUrl`, authenticated with `params.registryKey`.
     */
    public function actionPush(): int
    {
        $url = Yii::$app->params['registryUrl'] ?? null;
        $key = Yii::$app->params['registryKey'] ?? null;

        if (!is_string($url) || $url === '' || !is_string($key) || $key === '') {
            $missing = is_string($url) && $url !== '' ? 'registryKey' : 'registryUrl';
            $this->stdout("Registry not configured: `$missing` is not set in params, nothing sent." . PHP_EOL);

            return ExitCode::OK;
        }

        /** @var RegistryClient $client */
        $client = Yii::$container->get(RegistryClient::class, [], ['url' => $url, 'key' => $key]);
        $report = $this->createReport();
        $response = $client->push($report);

        if (!$response->isSuccess()) {
            $this->stderr("Registry push failed: {$response->getMessage()}" . PHP_EOL, Console::FG_RED);
            return $this->strict ? ExitCode::UNAVAILABLE : ExitCode::OK;
        }

        $data = $report->toArray();
        $installation = $response->data['installation'] ?? null;
        $project = $response->data['project'] ?? null;

        $this->stdout(sprintf(
            'Reported %s (%s) to %s%s%s.' . PHP_EOL,
            $data['name'],
            $data['url'] ?? "{$data['hostname']}:{$data['base_path']}",
            $client->getHost(),
            is_string($project) ? " as project \"$project\"" : '',
            is_scalar($installation) ? ", installation #$installation" : '',
        ), Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Prints the report as JSON without sending it.
     */
    public function actionShow(): int
    {
        $this->stdout(Json::encode($this->createReport()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        return ExitCode::OK;
    }

    protected function createReport(): Report
    {
        return Report::create($this->url !== null ? ['url' => $this->url] : []);
    }
}
