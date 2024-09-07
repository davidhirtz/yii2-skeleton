<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\models\forms\ConsoleSignupForm;
use Seld\CliPrompt\CliPrompt;
use yii\console\Controller;
use yii\helpers\Console;

class UserController extends Controller
{
    private ?string $name = null;
    private ?string $email = null;


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

        $this->stdout('Enter password: ');
        $form->password = CliPrompt::hiddenPrompt();

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
