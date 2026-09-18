<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base;

use Override;
use ReflectionClass;

class Module extends \yii\base\Module
{
    public const string EVENT_INIT = 'init';

    /**
     * Yii keeps its view path private, so whether one was assigned through configuration — which must survive
     * {@see static::init()} — is tracked here.
     */
    private bool $hasViewPath = false;

    #[Override]
    public function init(): void
    {
        if (!$this->hasViewPath) {
            $this->setViewPath($this->getViewPathFromBasePath());
        }

        $this->trigger(self::EVENT_INIT);

        if ($this->controllerNamespace === null) {
            $this->controllerNamespace = (new ReflectionClass(static::class))->getNamespaceName() . '\\Controllers';

            // Yii would derive the directory back from that namespace through an alias of the same name, which a
            // project's own module never has: only an installed extension is written into `extensions.php`.
            $this->setControllerPath($this->getBasePath() . '/Controllers');
        }

        parent::init();
    }

    /**
     * @param string $path
     */
    #[Override]
    public function setViewPath($path): void
    {
        $this->hasViewPath = true;
        parent::setViewPath($path);
    }

    /**
     * A bundle keeps its views beside `src/` rather than inside it. A module class that lives in no `src/` tree —
     * a project's own, under `app/Modules/Admin` — has nothing to walk up to, so it takes the application's view
     * directory.
     */
    protected function getViewPathFromBasePath(): string
    {
        $path = $this->getBasePath();
        $parts = explode('/', $path);
        $name = strtolower(array_pop($parts));

        if ($name === 'src') {
            return "$path/../resources/views/";
        }

        while ($parts) {
            $path .= '/..';

            if (array_pop($parts) === 'src') {
                return "$path/../resources/views/$name/";
            }
        }

        return '@views/' . $this->id;
    }
}
