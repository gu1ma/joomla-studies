<?php

/**
 *
 * Product table
 *
 * @package	VirtueMart
 * @subpackage Product
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2021 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: product_prices.php 10649 2022-05-05 14:29:44Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Product table class
 * The class is is used to manage the products in the shop.
 *
 * @package	VirtueMart
 * @author RolandD
 * @author Max Milbers
 */
class TableProduct_prices extends VmTable {

    /** @var int Primary key */
    var $virtuemart_product_price_id = 0;
    /** @var int Product id */
    var $virtuemart_product_id = 0;
    /** @var int Shopper group ID */
    var $virtuemart_shoppergroup_id = null;

    /** @var string Product price */
    var $product_price = null;
    var $override = null;
    var $product_override_price = null;
    var $product_tax_id = null;
    var $product_discount_id = null;

    /** @var string Product currency */
    var $product_currency = null;

    var $product_price_publish_up = 0;
    var $product_price_publish_down = 0;

    /** @var int Price quantity start */
    var $price_quantity_start = null;
    /** @var int Price quantity end */
    var $price_quantity_end = null;

    /**
     * @param JDataBase $db
     */
    function __construct(&$db) {
        parent::__construct('#__virtuemart_product_prices', 'virtuemart_product_price_id', $db);

		$this->setLoggable();
		$this->setTableShortCut('pp');
		$this->setConvertDecimal(array('product_price','product_override_price'));
		$this->setDateFields(array('product_price_publish_up','product_price_publish_down'));
		$this->_updateNulls = true;
    }

}

// pure php no closing tag
