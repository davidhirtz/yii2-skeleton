<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\GridToolbarItem;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

class AssetGridView extends GridView
{
    public string $layout = '{items}{footer}';

    public function init(): void
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
            TimeagoColumn::make()
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

        parent::init();
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
