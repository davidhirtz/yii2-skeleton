<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Test\Models\UploadRecord;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class UploadControllerTest extends TestCase
{
    private Upload $upload;
    private string $path;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function fixtures(): array
    {
        return [
            'user' => UserFixture::class,
        ];
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->upload = Upload::getComponent();
        $this->path = Yii::getAlias('@runtime/test-uploads') . '/';

        FileHelper::createDirectory($this->path);

        // A run that left files behind would otherwise be counted as this test's own.
        FileHelper::removeDirectory($this->upload->tempPath);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->path);
        FileHelper::removeDirectory($this->upload->tempPath);

        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(UploadRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(UploadRecord::tableName())
            ->execute();
    }

    public function testTheFieldComesBackCarryingTheToken(): void
    {
        $this->login();
        $this->setUpUpload('notes.txt');

        $html = $this->post();

        self::assertStringContainsString('notes.txt', $html);
        self::assertMatchesRegularExpression('/value="[\w-]{16}_notes\.txt"/', $html);

        $files = glob($this->upload->tempPath . '*') ?: [];

        self::assertCount(1, $files);
        self::assertStringEndsWith('_notes.txt', $files[0]);
    }

    /**
     * The container id is what the response is selected by, so it has to be the same one the form rendered — a
     * generated id counts per request and would differ.
     */
    public function testTheContainerIdIsDerivedFromTheAttribute(): void
    {
        $this->login();
        $this->setUpUpload('notes.txt');

        self::assertStringContainsString('id="uploadrecord-attachment-upload"', $this->post());
    }

    public function testAnExtensionTheDefinitionDoesNotAllowIsRefused(): void
    {
        $this->login();
        $this->setUpUpload('notes.exe');

        $this->post();

        self::assertSame(400, $this->getWebResponse()->getStatusCode());
        self::assertSame([], glob($this->upload->tempPath . '*') ?: []);
    }

    public function testAFileOverTheMaximumSizeIsRefused(): void
    {
        $this->assignPermission($this->login(), User::AUTH_USER);
        $this->setUpUpload('notes.pdf', str_repeat('x', 32));

        $this->post(['type' => UploadRecord::TYPE_RESTRICTED]);

        self::assertSame(400, $this->getWebResponse()->getStatusCode());
        self::assertSame([], glob($this->upload->tempPath . '*') ?: []);
    }

    /**
     * The skeleton cannot know a model's permission, so the definition names it and the action asks — an account
     * that may open the form is otherwise all the endpoint checks.
     */
    public function testADefinitionsPermissionIsEnforced(): void
    {
        $this->login();
        $this->setUpUpload('notes.pdf', 'x');

        $this->expectException(ForbiddenHttpException::class);

        $this->post(['type' => UploadRecord::TYPE_RESTRICTED]);
    }

    public function testADefinitionsPermissionIsGranted(): void
    {
        $user = $this->login();
        $this->assignPermission($user, User::AUTH_USER);
        $this->setUpUpload('notes.pdf', 'x');

        $html = $this->post(['type' => UploadRecord::TYPE_RESTRICTED]);

        self::assertIsString($html);
        self::assertStringContainsString('notes.pdf', $html);
    }

    public function testAnAccountOverTheUploadLimitIsRefused(): void
    {
        $this->login();
        $this->upload->uploadLimit = 1;

        $this->setUpUpload('notes.txt');
        $this->post();

        self::assertSame(200, $this->getWebResponse()->getStatusCode());

        $this->setUpUpload('second.txt');
        $this->post();

        self::assertSame(429, $this->getWebResponse()->getStatusCode());
        self::assertCount(1, glob($this->upload->tempPath . '*') ?: []);
    }

    public function testASignatureOfAnotherAttributeIsRefused(): void
    {
        $this->login();
        $this->setUpUpload('notes.txt');

        $this->expectException(NotFoundHttpException::class);

        $this->post(['signature' => $this->upload->sign(UploadRecord::class, 'track')]);
    }

    /**
     * A signature is issued for the user the form was rendered for, so it cannot be handed on.
     */
    public function testASignatureOfAnotherUserIsRefused(): void
    {
        $this->login();
        $signature = $this->upload->sign(UploadRecord::class, 'attachment');

        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));
        $this->setUpUpload('notes.txt');

        $this->expectException(NotFoundHttpException::class);

        $this->post(['signature' => $signature]);
    }

    public function testAClassThatHasNoSuchAttributeIsRefused(): void
    {
        $this->login();
        $this->setUpUpload('notes.txt');

        $this->expectException(NotFoundHttpException::class);

        $this->post([
            'model' => User::class,
            'signature' => $this->upload->sign(User::class, 'attachment'),
        ]);
    }

    public function testAGuestUploadsNothing(): void
    {
        $this->setUpUpload('notes.txt');

        self::assertNull($this->post());
        self::assertTrue($this->getWebResponse()->getIsRedirection());
        self::assertSame([], glob($this->upload->tempPath . '*') ?: []);
    }

    public function testTheRemoveRequestAnswersTheEmptyField(): void
    {
        $this->login();

        $html = $this->post(['remove' => 1]);

        self::assertStringContainsString('file-upload', $html);
        self::assertStringNotContainsString('notes.txt', $html);

        // the empty field reads as the filled one: one button laid out as an input group, the icon appended
        self::assertMatchesRegularExpression('#<button[^>]+class="input-group upload-select"[^>]*type="button"|<button[^>]+type="button"[^>]*class="input-group upload-select"#', $html);
        self::assertStringContainsString(Yii::t('skeleton', 'UPLOAD_BUTTON_SELECT'), $html);
        self::assertStringContainsString('input upload-name text-muted', $html);
        self::assertStringContainsString('input-group-append', $html);
        self::assertStringNotContainsString('btn-secondary', $html);
    }

    /**
     * Nothing will ever ask for the file of a pending upload once the form has dropped its token, so it goes now
     * rather than waiting for the collector.
     */
    public function testRemovingAPendingUploadDeletesItsFile(): void
    {
        $this->login();
        $this->setUpUpload('notes.txt');
        $this->post();

        $files = glob($this->upload->tempPath . '*') ?: [];

        self::assertCount(1, $files);

        $this->post(['remove' => 1, 'token' => basename($files[0])]);

        self::assertSame([], glob($this->upload->tempPath . '*') ?: []);
    }

    public function testAbandonedTemporaryFilesAreCollected(): void
    {
        $this->login();
        $this->setUpUpload('notes.txt');
        $this->post();

        $files = glob($this->upload->tempPath . '*') ?: [];
        touch($files[0], time() - $this->upload->tempLifetime - 1);

        self::assertSame(1, $this->upload->collectGarbage());
        self::assertSame([], glob($this->upload->tempPath . '*') ?: []);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function post(array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        $params = [
            'model' => UploadRecord::class,
            'attribute' => 'attachment',
            ...$params,
        ];

        $params['signature'] ??= $this->upload->sign(
            $params['model'],
            $params['attribute'],
            $params['type'] ?? null,
        );

        return Yii::$app->runAction('admin/upload/create', $params);
    }

    private function setUpUpload(string $name, string $content = 'content'): void
    {
        $tempName = $this->path . 'source';
        file_put_contents($tempName, $content);

        $_FILES['upload'] = [
            'name' => $name,
            'full_path' => $name,
            'type' => 'text/plain',
            'tmp_name' => $tempName,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($content),
        ];
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->getWebUser()->setIdentity($user);

        return $user;
    }

    private function assignPermission(User $user, string $name): void
    {
        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission($name), $user->id);
    }

    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
