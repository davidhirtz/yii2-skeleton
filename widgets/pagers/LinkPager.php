<?php
namespace davidhirtz\yii2\skeleton\widgets\pagers;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;

/**
 * Class LinkPager.
 * @package davidhirtz\yii2\skeleton\widgets\pagers
 */
class LinkPager extends \yii\widgets\LinkPager
{
	/**
	 * @var array
	 */
	public $pageOptions=[];

	/**
	 * @var int
	 */
	public $maxButtonCount=7;

	/**
	 * @var string
	 */
	public $pageCssClass='page-item';

	/**
	 * @var string
	 */
	public $linkCssClass='page-link';

	/**
	 * @var bool
	 */
	public $firstPageLabel=true;

	/**
	 * @var string|bool
	 */
	public $lastPageLabel=true;

	/**
	 * @var bool
	 */
	public $renderDisabledLink=true;

	/**
	 * Adds page css class to option array.
	 */
	public function init()
	{
		if($this->pageCssClass)
		{
			Html::addCssClass($this->pageOptions, $this->pageCssClass);
		}

		if($this->linkCssClass)
		{
			Html::addCssClass($this->linkOptions, $this->linkCssClass);
		}

		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	protected function renderPageButtons()
	{
		if($this->pagination->getPageCount()>1 || !$this->hideOnSinglePage)
		{
			$currentPage=$this->pagination->getPage();
			$lastPage=$this->pagination->getPageCount()-1;
			list($beginPage, $endPage)=$this->getPageRange();

			$buttons=[];
			$buttons[]=$this->renderPrevPageButton();

			if($beginPage>0)
			{
				$buttons[]=$this->renderFirstPageButton();
				$buttons[]=$this->renderRangeButton(0, $beginPage);
			}

			for($i=$beginPage; $i<=$endPage; ++$i)
			{
				$buttons[]=$this->renderPageButton($i+1, $i, null, false, $i==$currentPage);
			}

			if($endPage<$lastPage)
			{
				$buttons[]=$this->renderRangeButton($endPage, $lastPage);
				$buttons[]=$this->renderLastPageButton();
			}

			$buttons[]=$this->renderNextPageButton();

			return Html::tag('ul', implode('', $buttons), $this->options);
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	protected function renderPageButton($label, $page, $class, $disabled, $active)
	{
		$options=$this->pageOptions;
		$tag=ArrayHelper::remove($options, 'tag', 'li');

		if($class)
		{
			Html::addCssClass($options, $class);
		}

		if($active)
		{
			Html::addCssClass($options, $this->activePageCssClass);
		}

		if($disabled)
		{
			Html::addCssClass($options, $this->disabledPageCssClass);

			if(!$this->renderDisabledLink)
			{
				return Html::tag($tag, Html::tag('span', $label), $options);
			}
		}

		$linkOptions=$this->linkOptions;
		$linkOptions['data-page']=$page;

		return Html::tag($tag, Html::a($label, $this->pagination->createUrl($page), $linkOptions), $options);
	}

	/**
	 * @return string
	 */
	public function renderFirstPageButton()
	{
		$label=$this->firstPageLabel===true ? '1' : $this->firstPageLabel;

		return ($label!==false && $this->pagination->getPage()>0) ? $this->renderPageButton($label, 0, $this->firstPageCssClass, false, false) : null;
	}

	/**
	 * @return string
	 */
	public function renderLastPageButton()
	{
		$pageCount=$this->pagination->getPageCount();
		$label=$this->lastPageLabel===true ? $pageCount : $this->lastPageLabel;

		return $label!==false ? $this->renderPageButton($label, $pageCount-1, $this->lastPageCssClass, $this->pagination->getPage()>=$pageCount-1, false) : null;
	}

	/**
	 * @param string $label
	 * @param string $cssClass
	 * @return string
	 */
	public function renderPrevPageButton($label=null, $cssClass=null)
	{
		if(!$label)
		{
			$label=$this->prevPageLabel;
		}

		if($label)
		{
			$currentPage=$this->pagination->getPage();

			if(($page=$currentPage-1)<0)
			{
				$page=0;
			}

			return $this->renderPageButton($label, $page, $cssClass ?: $this->prevPageCssClass, $currentPage<=0, false);
		}

		return null;
	}

	/**
	 * @param string $label
	 * @param string $cssClass
	 * @return string
	 */
	public function renderNextPageButton($label=null, $cssClass=null)
	{
		if(!$label)
		{
			$label=$this->nextPageLabel;
		}

		if($label)
		{
			$currentPage=$this->pagination->getPage();
			$pageCount=$this->pagination->getPageCount();

			if(($page=$currentPage+1)>=$pageCount-1)
			{
				$page=$pageCount-1;
			}

			return $this->renderPageButton($label, $page, $cssClass ?: $this->nextPageCssClass, $currentPage>=$pageCount-1, false);
		}

		return null;
	}

	/**
	 * @param int $start
	 * @param int $stop
	 * @return string
	 */
	public function renderRangeButton($start, $stop)
	{
		if($start<$stop-1)
		{
			$ellipsis=$start<$stop-2;
			return $this->renderPageButton($ellipsis ? '...' : $start+2, $start+1, '', $ellipsis, false);
		}

		return null;
	}
}