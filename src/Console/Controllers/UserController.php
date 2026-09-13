<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Models\Forms\ConsoleSignupForm;
use Hirtz\Skeleton\Models\User;
use Seld\CliPrompt\CliPrompt;
use yii\console\Controller;
use yii\helpers\Console;

class UserController extends Controller
{
    private ?string $name = null;
    private ?string $email = null;

    /**
     * Sets a user's password from the console. The way back into an installation whose only administrator lost
     * their password and whose mailer is not an option.
     */
    public function actionPassword(string $email): void
    {
        $user = User::find()
            ->andWhereEmail($email)
            ->limit(1)
            ->one();

        if (!$user) {
            $this->stdout("No user found for $email." . PHP_EOL, Console::FG_RED);
            return;
        }

        $password = $this->readPassword();
        $length = mb_strlen($password);

        if ($length < $user->passwordMinLength || $length > $user->passwordMaxLength) {
            $this->stdout("The password must be between $user->passwordMinLength and"
                . " $user->passwordMaxLength characters." . PHP_EOL, Console::FG_RED);

            return;
        }

        $user->generatePasswordHash($password);
        $user->generateAuthKey();

        if ($user->update() === false) {
            $this->stdout(Console::errorSummary($user) . PHP_EOL, Console::FG_RED);
            return;
        }

        $user->afterPasswordChange();

        $this->stdout("Password updated for $user->email." . PHP_EOL, Console::FG_GREEN);
    }

    protected function readPassword(): string
    {
        $this->stdout('Enter password: ');
        return CliPrompt::hiddenPrompt();
    }

    public function actionCreate(): void
    {
        $form = ConsoleSignupForm::create();

        $form->name = $this->prompt('Enter username:', [
            'default' => $this->name,
            'required' => true,
        ]);

        $form->email = $this->prompt('Enter email address:', [
            'default' => $this->email,
            'required' => true,
        ]);

        $form->password = $this->readPassword();

        if ($form->insert()) {
            $this->stdout('User account created.' . PHP_EOL, Console::FG_GREEN);
            return;
        }

        $this->name = $form->name;
        $this->email = $form->email;

        $this->stdout(Console::errorSummary($form) . PHP_EOL, Console::FG_RED);

        if ($this->confirm('Do you want to retry?', true)) {
            $this->actionCreate();
        }
    }
}
