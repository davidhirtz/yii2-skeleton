<?php
namespace davidhirtz\yii2\skeleton\grid;
use yii\base\Widget;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class SummaryWidget.
 * @package davidhirtz\yii2\skeleton\grid
 * @todo remove
 */
class SummaryWidget extends Widget
{
	/**
	 * @var \yii\data\DataProviderInterface the data provider for the view. This property is required.
	 */
	public $dataProvider;

	/**
	 * @var string the param name of the search query.
	 */
	public $searchParamName='q';

	/**
	 * @var string the search query string.
	 */
	public $search;

	/**
	 * @var string the current list route used for search
	 * Leave empty to use current route and parameters.
	 */
	public $route;

	/**
	 * @var array the input field html options.
	 */
	public $options;

	/**
	 * @var string
	 */
	public $summary;

	/**
	 *
	 */
	public function init()
	{
		if(!$this->route)
		{
			$this->route=Url::current([$this->searchParamName=>null]);
		}

		/**
		 * Try to find search string from request.
		 */
		if(!$this->search)
		{
			$this->search=trim(Yii::$app->request->get($this->searchParamName));
		}

		if($this->search)
		{
			Html::addCssClass($this->options, 'alert-dismissible');
		}

		Html::addCssClass($this->options, $this->dataProvider->getTotalCount() ? ['alert', 'alert-info'] : ['alert', 'alert-warning']);
		parent::init();
	}

	/**
	 * Renders summary.
	 */
	public function run()
	{
		echo Html::beginTag('div', $this->options);

		if($this->search)
		{
			echo Html::a(Html::tag('span', '&times;', ['aria-hidden'=>true]), $this->route, ['class'=>'close','aria-label'=>Yii::t('app', 'Close')]);
		}

		echo $this->summary;
		echo Html::endTag('div');
	}
}