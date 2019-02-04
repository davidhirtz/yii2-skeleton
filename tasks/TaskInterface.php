<?php

namespace davidhirtz\yii2\skeleton\tasks;

/**
 * Interface TaskInterface.
 * @package davidhirtz\yii2\skeleton\tasks
 */
interface TaskInterface
{
    /**
     * @return bool
     */
    public function run();

    /**
     * @return string
     */
    public function getMessage();
}