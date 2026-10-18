<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Transports\StdioTransport;
use Hirtz\Skeleton\Test\TestCase;

class StdioTransportTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testEveryRequestIsAnsweredOnOneLine(): void
    {
        $output = $this->serve(
            '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{}}}',
            '{"jsonrpc":"2.0","method":"notifications/initialized"}',
            '',
            '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"list_resources","arguments":{}}}',
            'not json',
            '{"jsonrpc":"2.0","id":"three","method":"ping"}',
        );

        $lines = explode("\n", $output);

        self::assertSame('', array_pop($lines));
        self::assertCount(4, $lines);

        $responses = array_map(fn (string $line): mixed => json_decode($line, true, 512, JSON_THROW_ON_ERROR), $lines);

        self::assertSame([1, 2, null, 'three'], array_column($responses, 'id'));
        self::assertSame(-32700, $responses[2]['error']['code'] ?? null);
    }

    public function testANewlineInContentStaysEscaped(): void
    {
        $output = $this->serve('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"nope\nline"}}');

        self::assertSame(1, substr_count($output, "\n"));
        self::assertStringContainsString('Unknown tool: nope\nline', $output);
    }

    private function serve(string ...$lines): string
    {
        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');

        self::assertNotFalse($input);
        self::assertNotFalse($output);

        fwrite($input, implode("\n", $lines) . "\n");
        rewind($input);

        $transport = new StdioTransport(Mcp::getComponent()->createServer(), $input, $output);
        $transport->run();

        rewind($output);
        return (string)stream_get_contents($output);
    }
}
