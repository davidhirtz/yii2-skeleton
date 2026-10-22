<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Actions\RestoreTrail;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\log\Logger;
use yii\web\NotFoundHttpException;

/**
 * The trail rebuilds a deleted record as a new one carrying its id, so neither a restore nor a link may take it at
 * its word.
 */
class TrailDeletedRecordTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheUpdateTrailOfADeletedRecordIsNotRestorable(): void
    {
        [, $trail] = $this->createUpdateAndDelete();

        self::assertFalse(RestoreTrail::isRestorable($trail));
    }

    public function testRestoringTheUpdateTrailOfADeletedRecordIsNotFound(): void
    {
        [, $trail] = $this->createUpdateAndDelete();
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = $this->getWebRequest();
        $request->setBodyParams(['id' => $trail->id, $request->csrfParam => $request->getCsrfToken()]);

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/trail/restore', ['id' => $trail->id]);
    }

    public function testTheTrailHeaderDoesNotLinkADeletedRecord(): void
    {
        [$redirect] = $this->createUpdateAndDelete();
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $html = Yii::$app->runAction('admin/trail/index', ['model' => Redirect::class . '@' . $redirect->id]);

        self::assertIsString($html);
        self::assertStringNotContainsString("redirect/update?id=$redirect->id", $html);
    }

    public function testATrailOfARemovedClassLogsNoError(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $trail = Trail::create();
            $trail->type = Trail::TYPE_UPDATE;
            $trail->model_class = 'App\\Models\\RemovedRecord';
            $trail->model_id = (string)$i;
            $trail->data = ['name' => ['old', 'neu – Ä']];
            self::assertTrue($trail->insert(), print_r($trail->getErrors(), true));
        }

        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        $this->logger->isRecording = true;
        $this->logger->messages = [];

        self::assertIsString(Yii::$app->runAction('admin/trail/index'));

        $errors = array_filter($this->logger->messages, fn (array $message): bool => $message[1] === Logger::LEVEL_ERROR);
        self::assertSame([], $errors);
    }

    /**
     * @return array{Redirect, Trail}
     */
    private function createUpdateAndDelete(): array
    {
        $redirect = Redirect::create();
        $redirect->type = Redirect::TYPE_MOVED_PERMANENTLY;
        $redirect->request_uri = 'audit-ä';
        $redirect->url = 'audit-x';
        self::assertTrue($redirect->insert(), print_r($redirect->getErrors(), true));

        $redirect->request_uri = 'audit-b';
        $redirect->url = 'audit-y';
        self::assertSame(1, $redirect->update());
        self::assertSame(1, $redirect->delete());

        TrailModelCollection::reset();

        $trail = Trail::find()
            ->where(['model_class' => Redirect::class, 'model_id' => (string)$redirect->id, 'type' => Trail::TYPE_UPDATE])
            ->one();

        self::assertNotNull($trail);

        return [$redirect, $trail];
    }
}
