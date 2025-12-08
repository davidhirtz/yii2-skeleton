<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Override;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

class AssetGridView extends GridView
{
    public string $layout = '{items}{footer}';

    #[Override]
    public function configure(): void
    {
        $this->provider ??= new ArrayDataProvider([
            'allModels' => $this->findAssets(),
            'pagination' => false,
            'sort' => false,
        ]);

        $this->columns ??= [
            Column::make()
                ->header(Yii::t('skeleton', 'Name'))
                ->content(function ($item): Stringable {
                    $ul = Ul::make()
                        ->class('small');

                    foreach ($item['files'] as $file => $link) {
                        $ul->addContent(A::make()
                            ->href($link . $file)
                            ->text($file)
                            ->target('_blank'));
                    }

                    return Div::make()
                        ->addContent(Div::make()
                            ->class('strong')
                            ->content($item['name']))
                        ->addContent($ul);
                }),
            RelativeTimeColumn::make()
                ->property('modified')
                ->header(Yii::t('skeleton', 'Updated')),
        ];

        /** @see SystemController::actionPublish() */
        $this->footer ??= [
            GridToolbarItem::make()
                ->class('ms-auto')
                ->content(Button::make()
                    ->primary()
                    ->text(Yii::t('skeleton', 'Refresh'))
                    ->icon('sync-alt')
                    ->post(['publish'])),
        ];

        parent::configure();
    }

    protected function findAssets(): array
    {
        $manager = Yii::$app->getAssetManager();
        $basePath = $manager->basePath;
        $baseUrl = $manager->baseUrl;

        $directories = FileHelper::findDirectories($basePath, ['recursive' => false]);
        $assets = [];

        foreach ($directories as $directory) {
            $handle = @opendir($directory);
            $basename = pathinfo((string)$directory, PATHINFO_BASENAME);
            $files = [];

            while (($file = readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..') {
                    $files[$file] = $baseUrl . '/' . $basename . '/';
                }
            }

            closedir($handle);

            $assets[] = [
                'name' => $basename,
                'files' => $files,
                'modified' => filemtime($directory),
            ];
        }

        return $assets;
    }
}
