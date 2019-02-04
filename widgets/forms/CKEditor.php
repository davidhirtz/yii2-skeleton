<?php
namespace davidhirtz\yii2\skeleton\widgets\forms;
use davidhirtz\yii2\skeleton\assets\CKEditorBootstrapAsset;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use Yii;

/**
 * Class CKEditor.
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
class CKEditor extends \dosamigos\ckeditor\CKEditor
{
	/**
	 * @var array
	 */
	public $toolbar=[
		['Bold', 'Italic', 'Underline', 'Strike'],
		['NumberedList', 'BulletedList', 'Table', 'Blockquote'],
		['RemoveFormat'],
		['Link', 'Unlink'],
		['Source'],
	];

	/**
	 * @inherit
	 */
	public $clientOptions=[
		'height'=>300,
		'removeDialogTabs'=>'link:advanced',
	];

	/**
	 * @var array
	 */
	public $extraPlugins=[];

	/**
	 * @var array
	 */
	public $removePlugins=[];

	/**
	 * @var string
	 */
	public $preset='custom';

	/**
	 * @var string
	 */
	public $validator='davidhirtz\yii2\skeleton\validators\HtmlValidator';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		// Plugins.
		$removePlugins=array_merge($this->removePlugins, [
			'elementspath',
			'magicline',
			'resize',
			'contextmenu',
			'liststyle',
			'tabletools',
			'tableselection',
		]);

		if($this->extraPlugins)
		{
			$removePlugins=array_diff($removePlugins, $this->extraPlugins);
		}

		$this->clientOptions['removePlugins']=implode(',', array_unique(array_filter($removePlugins)));
		$this->clientOptions['toolbar']=$this->toolbar;

		/** @var HtmlValidator $validator */
		$validator=Yii::createObject($this->validator);
		$this->clientOptions['allowedContent']=str_replace('|', ',', implode(';', $validator->allowedHtmlTags));

		// Editor skin path.
		$bundle=CKEditorBootstrapAsset::register($view=$this->getView());
		$this->clientOptions['skin']='bootstrap,'.$bundle->baseUrl.'/';

		// Contents CSS file.
		$bundle=$view->registerAssetBundle($bundle->editorAssetBundle ?: 'davidhirtz\yii2\skeleton\assets\AppAsset');
		$this->clientOptions['contentsCss']=$bundle->baseUrl.'/'.$bundle->css[0];

		// Language.
		if(Yii::$app->language!=Yii::$app->sourceLanguage)
		{
			$this->clientOptions['language']=Yii::$app->language;
		}

		parent::init();
	}
}