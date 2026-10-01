<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web\Traits;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Web\Traits\UploadControllerTrait;
use Yii;
use yii\base\Module;
use yii\web\Response;

/**
 * The uploader showed the reason phrase alone, which HTTP/2 does not have (monorepo issue #410).
 */
class UploadControllerTraitTest extends TestCase
{
    public function testARefusalCarriesItsMessageBeyondTheReasonPhrase(): void
    {
        $message = 'Datei „<b>Å😀.exe</b>“ ist nicht erlaubt';
        $response = (new UploadControllerTraitTestController('upload', Yii::$app))->refuse($message);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($message, rawurldecode((string)$response->getHeaders()->get('X-Upload-Error')));
        self::assertSame('Datei „&lt;b&gt;Å😀.exe&lt;/b&gt;“ ist nicht erlaubt', $response->content);
    }
}

/**
 * @extends Controller<Module>
 */
class UploadControllerTraitTestController extends Controller
{
    use UploadControllerTrait;

    public function refuse(string $message): Response
    {
        return $this->refuseUpload(400, $message);
    }
}
