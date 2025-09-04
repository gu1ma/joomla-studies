<?php
defined('_JEXEC') or die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/*
* featured/Latest/Topten/Random Products Module
*
* @version $Id: mod_virtuemart_product.php 2789 2011-02-28 12:41:01Z oscar $
* @package VirtueMart
* @subpackage modules
*
* @copyright (C) 2010 - Patrick Kohl
* @copyright (C) 2011 - 2021 The VirtueMart Team
* @author Max Milbers, Valerie Isaksen, Alexander Steiner
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* VirtueMart is Free Software.
* VirtueMart comes with absolute no warranty.
*
* @link https://virtuemart.net
*/

if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');

VmConfig::loadConfig();

$mainframe = Jfactory::getApplication();
$virtuemart_currency_id = $mainframe->getUserStateFromRequest( "virtuemart_currency_id", 'virtuemart_currency_id',vRequest::getInt('virtuemart_currency_id',0) );

vmJsApi::jPrice();
vmJsApi::cssSite();

$Product_group = $params->get( 'product_group', 'featured'); // Display a footerText
$cache = $params->get( 'vmcache', true );
$cachetime = $params->get( 'vmcachetime', 60 );
$products = false;
//vmdebug('$params for mod products',$params);

if (!class_exists( 'mod_virtuemart_product' )) require(JPATH_ROOT .'/modules/mod_virtuemart_product/helper.php');

if($cache and $Product_group!='recent'){
	//$key = 'products'.$category_id.'.'.$max_items.'.'.$filter_category.'.'.$display_style.'.'.$products_per_row.'.'.$show_price.'.'.$show_addtocart.'.'.$Product_group.'.'.$virtuemart_currency_id.'.'.$category_id.'.'.$filter_manufacturer.'.'.$manufacturer_id;

	$cache	= VmConfig::getCache('mod_virtuemart_product');
	$cache->setCaching(1);
	$cache->setLifeTime($cachetime);
	$db = JFactory::getDbo();
	echo $cache->get( array( 'mod_virtuemart_product', 'displayProductsMod' ), array($module, $params, $Product_group));
	vmdebug('Use cached mod products');
} else {
	echo mod_virtuemart_product::displayProductsMod($module, $params, $Product_group);
}

echo vmJsApi::writeJS();
?>
