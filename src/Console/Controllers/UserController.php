<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Models\Forms\ConsoleSignupForm;
use Hirtz\Skeleton\Models\User;
use Override;
use Seld\CliPrompt\CliPrompt;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manages user accounts from the console.
 */
class UserController extends Controller
{
    /**
     * The password a provisioning script sets, so it stays out of the shell history and the process list, which
     * `--password` reaches.
     */
    final public const string PASSWORD_ENV = 'YII_USER_PASSWORD';

    /**
     * @var string|null the account name, prompted for when it is not given
     */
    public ?string $name = null;

    /**
     * @var string|null the email address, prompted for when it is not given
     */
    public ?string $email = null;

    /**
     * @var string|null the password, prompted for when neither it nor the `YII_USER_PASSWORD` environment
     * variable is given
     */
    public ?string $password = null;

    /**
     * @param string $actionID
     * @return list<string>
     */
    #[Override]
    public function options($actionID): array
    {
        return array_values([
            ...parent::options($actionID),
            ...match ($actionID) {
                'create' => ['name', 'email', 'password'],
                'password' => ['password'],
                default => [],
            },
        ]);
    }

    /**
     * Sets a user's password from the console. The way back into an installation whose only administrator lost
     * their password and whose mailer is not an option.
     */
    public function actionPassword(string $email): int
    {
        $user = User::find()
            ->andWhereEmail($email)
            ->limit(1)
            ->one();

        if (!$user) {
            $this->stdout("No user found for $email." . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $password = $this->readPassword();
        $length = mb_strlen($password);

        if ($length < $user->passwordMinLength || $length > $user->passwordMaxLength) {
            $this->stdout("The password must be between $user->passwordMinLength and"
                . " $user->passwordMaxLength characters." . PHP_EOL, Console::FG_RED);

            return ExitCode::DATAERR;
        }

        $user->generatePasswordHash($password);
        $user->generateAuthKey();

        if ($user->update() === false) {
            $this->stdout(Console::errorSummary($user) . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->afterPasswordChange();

        $this->stdout("Password updated for $user->email." . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    protected function readPassword(): string
    {
        $password = $this->password ?? getenv(self::PASSWORD_ENV);

        if (is_string($password) && $password !== '') {
            return $password;
        }

        if (!$this->interactive) {
            return '';
        }

        $this->stdout('Enter password: ');
        return CliPrompt::hiddenPrompt();
    }

    public function actionCreate(): int
    {
        $form = ConsoleSignupForm::create();

        $form->name = $this->name ?? $this->prompt('Enter username:', ['required' => true]);
        $form->email = $this->email ?? $this->prompt('Enter email address:', ['required' => true]);
        $form->password = $this->readPassword();

        if ($form->insert()) {
            $this->stdout('User account created.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout(Console::errorSummary($form) . PHP_EOL, Console::FG_RED);

        // The options are what makes the command scriptable, so the retry must not read them again: it exists to
        // correct the value that failed and would otherwise repeat it forever.
        $this->name = null;
        $this->email = null;
        $this->password = null;

        // `confirm()` answers its default without a terminal, so the retry has to be asked for explicitly — a
        // scripted run would otherwise recurse forever on the value it was given.
        return $this->interactive && $this->confirm('Do you want to retry?', true)
            ? $this->actionCreate()
            : ExitCode::DATAERR;
    }
}
