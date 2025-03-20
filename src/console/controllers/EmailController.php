<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ControllerTrait;
use Yii;
use yii\console\Controller;

/**
 * Tests the email functionality.
 */
class EmailController extends Controller
{
    use ControllerTrait;

    public $defaultAction = 'test';

    public function actionTest(string $email): void
    {
        $this->interactiveStartStdout('Testing email functionality ...');

        $success = Yii::$app->getMailer()
            ->compose()
            ->setSubject('Test email')
            ->setTextBody('This is a test email from ' . Yii::$app->name . '. If you received this email, the email functionality is working.')
            ->setFrom(Yii::$app->params['email'])
            ->setTo($email)
            ->send();

        $this->interactiveDoneStdout($success);
    }
}
