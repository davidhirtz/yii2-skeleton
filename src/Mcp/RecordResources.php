<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Hirtz\Skeleton\Mcp\Tools\FindRecordsTool;
use Hirtz\Skeleton\Mcp\Tools\GetRecordTool;
use Hirtz\Skeleton\Mcp\Tools\ListResourcesTool;

/**
 * The records of the registered resources as MCP resources, `record://entry/8`. Everything goes through the read
 * tools, so a record is offered, listed and read under exactly the permissions `get_record` checks.
 */
class RecordResources
{
    final public const string SCHEME = 'record';

    private const string MIME_TYPE = 'application/json';

    public function __construct(
        protected readonly Mcp $mcp,
        protected readonly int $listLimit = 10,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTemplates(): array
    {
        return array_map(fn (array $resource): array => [
            'uriTemplate' => self::SCHEME . "://{$resource['name']}/{id}",
            'name' => $resource['name'],
            'title' => $resource['label'] ?? $resource['name'],
            'mimeType' => self::MIME_TYPE,
        ], $this->getResources());
    }

    /**
     * The most recently updated records of each resource, so a client has something to offer before a template is
     * filled in.
     *
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $tool = new FindRecordsTool($this->mcp);
        $records = [];

        foreach ($this->getResources() as $resource) {
            $result = $tool->call(['resource' => $resource['name'], 'limit' => $this->listLimit, 'order' => '-updated_at']);

            if ($result->isError) {
                $result = $tool->call(['resource' => $resource['name'], 'limit' => $this->listLimit]);
            }

            /** @var list<array<string, mixed>> $summaries */
            $summaries = $result->isError ? [] : ($result->structuredContent['records'] ?? []);

            foreach ($summaries as $summary) {
                $id = $summary['id'] ?? null;

                if (!is_int($id) && !is_string($id)) {
                    continue;
                }

                $name = $summary['name'] ?? null;

                $records[] = array_filter([
                    'uri' => self::getUri($resource['name'], $id),
                    'name' => "{$resource['name']}/$id",
                    'title' => is_string($name) && $name !== '' ? $name : null,
                    'mimeType' => self::MIME_TYPE,
                    'annotations' => is_string($summary['updated_at'] ?? null)
                        ? ['lastModified' => $summary['updated_at']]
                        : null,
                ], fn (mixed $value): bool => $value !== null);
            }
        }

        return $records;
    }

    /**
     * @return list<array{uri: string, mimeType: string, text?: string, blob?: string}>
     * @throws JsonRpcException
     */
    public function read(string $uri): array
    {
        $parts = self::parseUri($uri);
        $result = $parts ? (new GetRecordTool($this->mcp))->call(['resource' => $parts[0], 'id' => $parts[1]]) : null;

        if ($result === null || $result->isError) {
            throw new JsonRpcException(JsonRpcException::INVALID_PARAMS, 'Resource not found', ['uri' => $uri]);
        }

        return [
            [
                'uri' => $uri,
                'mimeType' => self::MIME_TYPE,
                'text' => Server::encode($result->structuredContent),
            ],
            ...array_map(fn (array $block): array => [
                'uri' => $uri,
                'mimeType' => $block['mimeType'],
                'blob' => $block['data'],
            ], $result->content),
        ];
    }

    public static function getUri(string $resource, int|string $id): string
    {
        return self::SCHEME . '://' . rawurlencode($resource) . '/' . rawurlencode((string)$id);
    }

    /**
     * @return array{string, string}|null
     */
    public static function parseUri(string $uri): ?array
    {
        if (!preg_match('#^' . self::SCHEME . '://([^/]+)/([^/?\#]+)$#', $uri, $matches)) {
            return null;
        }

        return [rawurldecode($matches[1]), rawurldecode($matches[2])];
    }

    /**
     * @return list<array{name: string, label?: string}>
     */
    protected function getResources(): array
    {
        $result = (new ListResourcesTool($this->mcp))->call([]);

        /** @var list<array{name: string, label?: string}> $resources */
        $resources = $result->isError ? [] : ($result->structuredContent['resources'] ?? []);
        return $resources;
    }
}
