<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\UserIdentityTrait;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Transports\StdioTransport;
use Override;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Serves the content tools to an agent over MCP.
 */
class McpController extends Controller
{
    use UserIdentityTrait;

    /**
     * @var bool whether to offer only the tools that read
     */
    public bool $readOnly = false;

    /**
     * @param string $actionID
     * @return list<string>
     */
    #[Override]
    public function options($actionID): array
    {
        return array_values([...parent::options($actionID), 'user', 'readOnly']);
    }

    #[Override]
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action) && $this->setUserIdentity();
    }

    /**
     * Answers MCP messages on STDIN until it closes, as the given user.
     */
    public function actionServe(): int
    {
        // A notice on STDOUT would corrupt the stream
        ini_set('display_errors', 'stderr');

        $transport = new StdioTransport(Mcp::getComponent()->createServer($this->readOnly));
        $transport->run();

        return ExitCode::OK;
    }
}
