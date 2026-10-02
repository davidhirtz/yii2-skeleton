<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\ControllerTrait;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\validators\EmailValidator;

/**
 * Tests the email functionality.
 */
class EmailController extends Controller
{
    use ControllerTrait;

    public $defaultAction = 'test';

    /**
     * Sends a test email to the given address.
     */
    public function actionTest(string $email): int
    {
        if (empty(Yii::$app->params['email'])) {
            $this->stderr('No email address configured. Please set the "email" parameter in your config file.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        if (!(new EmailValidator())->validate($email)) {
            $this->stderr("\"$email\" is not a valid email address." . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $mailer = Yii::$app->getMailer();

        if ($mailer->useFileTransport) {
            $this->stdout('The mailer writes files instead of sending (`useFileTransport`).' . PHP_EOL, Console::FG_YELLOW);
        } elseif ($error = $mailer->getTransportError()) {
            $this->stderr($error . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $this->interactiveStartStdout('Testing email functionality ...');

        $success = $mailer
            ->compose()
            ->setSubject('Test email')
            ->setTextBody('This is a test email from ' . Yii::$app->name . '. If you received this email, the email functionality is working.')
            ->setFrom(Yii::$app->params['email'])
            ->setTo($email)
            ->send();

        $this->interactiveDoneStdout($success);

        if ($exception = $mailer->getLastTransportException()) {
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);
        }

        if (!$success) {
            $this->stderr('The test email could not be sent.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }

        return ExitCode::OK;
    }
}
