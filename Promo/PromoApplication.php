<?php

require_once 'Store/StoreApplication.php';
require_once 'Promo/Promo.php';
require_once 'Promo/dataobjects/PromoPromotion.php';

/**
 * @package   Promo
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class PromoApplication extends StoreApplication
{
	// {{{ protected function initModules()

	protected function initModules()
	{
		$this->session->registerObject(
			'promotion',
			SwatDBClassMap::get('PromoPromotion')
		);

		parent::initModules();
	}

	// }}}
}

?>
