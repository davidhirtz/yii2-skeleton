<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Tools\CreateRecordTool;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class RedirectResourceTest extends TestCase
{
    use UserFixtureTrait;

    public function testARedirectIsCreatedOfItsType(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $result = (new CreateRecordTool(Mcp::getComponent()))->call([
            'resource' => 'redirect',
            'type' => Redirect::TYPE_FOUND,
            'attributes' => ['request_uri' => 'alt/åsa-東京', 'url' => 'neu'],
        ]);

        self::assertFalse($result->isError, json_encode($result->structuredContent, JSON_UNESCAPED_UNICODE) ?: '');

        $redirect = Redirect::findOne($result->structuredContent['id'] ?? 0);

        self::assertSame(Redirect::TYPE_FOUND, $redirect?->type);
        self::assertSame('neu', $redirect->url);
    }
}
