<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Store/dataobjects/StoreRegionWrapper.php';
require_once 'Promo/dataobjects/PromoPromotionWrapper.php';

/**
 * Index page for Promotions
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class PromoPromotionIndex extends AdminIndex
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Promo/admin/components/Promotion/index.xml';
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Promotion/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$view = $this->ui->getWidget('index_view');

		if ($view->hasGroup('instance_group')) {
			$view->getGroup('instance_group')->visible =
				$this->app->isMultipleInstanceAdmin();
		}
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		// Need to coalesce here to handle promotions with no codes or no
		// orders that are not reflected in the PromotionROI view.
		$sql = 'select Promotion.*,
				coalesce(PromotionROIView.num_orders, 0) as num_orders,
				PromotionROIView.promotion_total, PromotionROIView.total,
				Instance.title as instance_title
			from Promotion
			left outer join Instance on Promotion.instance = Instance.id
			left outer join PromotionROIView on
				Promotion.id = PromotionROIView.promotion';

		$instance_id = $this->app->getInstanceId();
		if ($instance_id !== null) {
			$sql.= sprintf(
				' where Promotion.instance = %s',
				$this->app->db->quote($instance_id, 'integer')
			);
		}

		$sql.= sprintf(
			' order by instance_title nulls first,
				Promotion.instance nulls first, %s',
			$this->getOrderByClause($view, 'title')
		);

		$rs = SwatDB::query($this->app->db, $sql);

		$class_name = SwatDBClassMap::get('PromoPromotion');

		$store = new SwatTableStore();
		foreach ($rs as $row) {
			$promotion = new $class_name($row);
			$promotion->setDatabase($this->app->db);

			$ds = $this->getPromotionDetailsStore(
				$promotion,
				$row
			);

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getPromotionDetailsStore()

	protected function getPromotionDetailsStore(PromoPromotion $promotion,
		$row)
	{
		$ds = new SwatDetailsStore($promotion);
		$ds->show_discount_amount = $promotion->isFixedDiscount();
		$ds->valid_dates = $promotion->getValidDates(
			$this->app->default_time_zone,
			SwatDate::DF_DATE_TIME_SHORT
		);

		$ds->is_active = $promotion->isActive(false);

		$ds->num_orders = ($row->num_orders === null)
			? 0
			: $row->num_orders;

		$ds->promotion_total = $row->promotion_total;
		$ds->total = $row->total;

		if ($ds->promotion_total === null) {
			$ds->roi_infinite = false;
			$ds->roi = null;
		} elseif ($ds->promotion_total == 0) {
			$ds->roi_infinite = true;
			$ds->roi = 0;
		} else {
			$ds->roi_infinite = false;
			$ds->roi = ($ds->total - $ds->promotion_total) /
				$ds->promotion_total;
		}

		$ds->has_notes = ($promotion->notes != '');
		$ds->notes     = SwatString::minimizeEntities($ds->notes);
		$ds->notes     = '<span class="admin-notes">'.$ds->notes.'</span>';

		return $ds;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/promo/admin/styles/promo-promotion-index.css'
		);
	}

	// }}}
}

?>