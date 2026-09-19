<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class AdminGermanRbacTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testThePermissionsPageReadsGerman(): void
    {
        $this->login();

        self::switchToGerman();
        $this->open('admin/auth/index');

        self::assertResponseIsSuccessful();

        $html = self::$crawler->html();

        self::assertStringContainsString('Benutzer verwalten', $html);
        self::assertStringContainsString('Benutzerrecht', $html);
    }

    public function testTheTrailReadsGerman(): void
    {
        $user = $this->login();

        $this->createTrail($user, Trail::TYPE_ORDER, Message::make('skeleton', 'TRAIL_PASSWORD_CHANGED')->toJson());
        $this->createTrail($user, Trail::TYPE_DELETE);
        $this->createTrail($user, Trail::TYPE_CHILD_DELETE);

        // An assign trail written before 3.0 carries its rendered description instead of the item name
        $this->createTrail($user, Trail::TYPE_ASSIGN, 'Update entries');

        Yii::$app->getAuthManager()->assign(
            Yii::$app->getAuthManager()->getPermission(User::AUTH_USER),
            $user->id
        );

        self::switchToGerman();
        $this->open('admin/trail/index');

        self::assertResponseIsSuccessful();

        $html = self::$crawler->html();

        self::assertStringContainsString('Passwort geändert', $html);
        self::assertStringContainsString('wurde gelöscht', $html);
        self::assertStringContainsString('Gelöscht</div> gelöscht', $html);
        self::assertStringContainsString('Update entries', $html);
        self::assertStringContainsString('Benutzer verwalten', $html);
    }

    private static function switchToGerman(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->setSessionLanguage('de');
    }

    private function createTrail(User $user, int $type, ?string $message = null): void
    {
        $trail = Trail::create();
        $trail->type = $type;
        $trail->model_class = User::class;
        $trail->model_id = (string)$user->id;
        $trail->message = $message;
        $trail->insert();
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('owner');

        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $user->email,
            'password' => 'password',
        ]));

        return $user;
    }
}
