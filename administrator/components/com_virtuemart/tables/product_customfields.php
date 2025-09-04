<?php
/**
*
* @package	VirtueMart
* @subpackage product
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2011 - 2021 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: product_medias.php 3002 2011-04-08 12:35:45Z alatak $
*/

defined('_JEXEC') or die();

class TableProduct_customfields extends VmTable {

	/** @var int Primary key */
	var $virtuemart_customfield_id		= 0;

	/** @var int Product id */
	var $virtuemart_product_id		= 0;

	/** @var int group key */
	var $virtuemart_custom_id		= 0;

	var $override = 0;
	var $disabler = 0;
	var $noninheritable = 0;
    /** @var string custom value */
	var $customfield_value	= null;
    /** @var string price  */
	var $customfield_price	= null;

    var $customfield_params = '';
    var $product_sku = '';
	var $product_gtin = '';
	var $product_mpn = '';

	/** @var int custom published or not */
	var $published		= 0;

	/** @var int listed Order */
	var $ordering	= 0;

	/**
	 * @author Max Milbers
	 * @param JDataBase $db
	 */
	function __construct(&$db){
		parent::__construct('#__virtuemart_product_customfields', 'virtuemart_customfield_id', $db);


		$this->setOrderable();
		$this->setConvertDecimal(array('customfield_price'));
	}

	function check(){

		if(empty($this->virtuemart_product_id)){
			vmError('Could not store custom id '.$this->virtuemart_custom_id.' no product id given');
			return false;
		}

		if(empty($this->virtuemart_custom_id)){
			vmError('Could not store custom for product id '.$this->virtuemart_product_id.' no custom id given');
			return false;
		}
		return parent::check();
	}
}
