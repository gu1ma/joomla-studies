<?php
/**
 * Shopper group data access object.
 *
 * @package	VirtueMart
 * @subpackage ShopperGroup
 * @author Max Milbers
 * @author Markus Öhler
 * @copyright Copyright (c) 2011 - 2018 VirtueMart Team. All rights reserved.
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

/**
 * Shopper group table.
 *
 * This class is a template.
 */
class TableShoppergroups extends VmTable
{

	var $virtuemart_shoppergroup_id	 = 0;
	var $virtuemart_vendor_id = 0;
	var $shopper_group_name  = '';
	var $shopper_group_desc  = '';
	var $sgrp_additional = 1;
	var $custom_price_display = 0;
	var $price_display		= '';
	var $default = 0;
	var $published = 0;


	function __construct(&$db)
	{
		parent::__construct('#__virtuemart_shoppergroups', 'virtuemart_shoppergroup_id', $db);

		$this->setUniqueName('shopper_group_name');

		$this->setLoggable();
		$this->setLockable();
		$this->setTableShortCut('sg');

		$varsToPushParam = array('show_prices' => array(0,'int'));
		foreach(CurrencyDisplay::$priceNames as $field){
			$varsToPushParam[$field] = array(1,'int');
			$varsToPushParam[$field.'Text'] = array(1,'int');
			$varsToPushParam[$field.'Rounding'] = array(-1,'int');
		}

		$this->setParameterable('price_display',$varsToPushParam);

	}

	function check(){

		if (empty($this->shopper_group_name) ){
			vmError('COM_VIRTUEMART_SHOPPERGROUP_RECORDS_MUST_HAVE_NAME');
			return false;
		} else {
			if(function_exists('mb_strlen') ){
				$length = mb_strlen($this->shopper_group_name);
			} else {
				$length = strlen($this->shopper_group_name);
			}
			if($length>128){
				vmError('COM_VIRTUEMART_SHOPPERGROUP_NAME_128');
			}
		}

		if($this->virtuemart_shoppergroup_id==1 OR $this->virtuemart_shoppergroup_id==2){
			if($this->virtuemart_shoppergroup_id==1) {
				$this->default=2;
			} else if($this->virtuemart_shoppergroup_id==2) {
				$this->default=1;
			}
			$this->sgrp_additional = 0;
			$this->shared = 1;
			$this->virtuemart_vendor_id = 1;
			//$this->published = 1;
		} else {
			$this->default=0;
		}

		return parent::check();

	}
}
// pure php no closing tag
