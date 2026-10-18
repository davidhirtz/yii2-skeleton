<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Transports;

use Hirtz\Skeleton\Mcp\Server;

/**
 * Newline-delimited JSON: one message per line in, one response per line out, and nothing else on the output.
 */
class StdioTransport
{
    /**
     * @var resource
     */
    protected $input;

    /**
     * @var resource
     */
    protected $output;

    /**
     * @param resource|null $input
     * @param resource|null $output
     */
    public function __construct(protected Server $server, $input = null, $output = null)
    {
        $this->input = $input ?? STDIN;
        $this->output = $output ?? STDOUT;
    }

    public function run(): void
    {
        while (($line = fgets($this->input)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $response = $this->server->handleJson($line);

            if ($response !== null) {
                fwrite($this->output, $response . "\n");
                fflush($this->output);
            }
        }
    }
}
