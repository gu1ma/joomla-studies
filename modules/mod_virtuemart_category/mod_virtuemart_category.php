<?php
defined('_JEXEC') or  die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/*
* Best selling Products module for VirtueMart
* @version $Id: mod_virtuemart_category.php 1160 2014-05-06 20:35:19Z milbo $
* @package VirtueMart
* @subpackage modules
*
* @copyright (C) 2011-2021 The Virtuemart Team
*
*
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* VirtueMart is Free Software.
* VirtueMart comes with absolute no warranty.
*
* @link https://virtuemart.net
*----------------------------------------------------------------------
* This code creates a list of the bestselling products
* and displays it wherever you want
*----------------------------------------------------------------------
*/

if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');

if (!class_exists( 'mod_virtuemart_category' )) require(JPATH_ROOT .'/modules/mod_virtuemart_category/helper.php');

VmConfig::loadConfig();

vmJsApi::jQuery();
vmJsApi::cssSite();

/** @var \phpDocumentor\Reflection\Types\Array_ $params */

$layout = $params->get('layout','default');
vmdebug('Layout category module ',$layout);
$class_sfx = trim($params->get('class_sfx',''));
$className = 'VMmenu'.$class_sfx;
if( strpos($layout, 'default')!==FALSE or strpos($layout,'current')!==FALSE ){
	/* ID for jQuery dropdown */

	$js="jQuery(document).ready(function() {
	
		jQuery('.".$className." li.VmClose ul.menu').hide();
		jQuery('.".$className." li .VmArrowdown').click(
		function() {
			if (jQuery(this).parent().next('ul').is(':hidden')) {
				jQuery('.".$className." ul:visible').delay(200).slideUp(500,'linear').parents('li').addClass('VmClose').removeClass('VmOpen');
				jQuery(this).parent().next('ul').slideDown(500,'linear');
				jQuery(this).parents('li').addClass('VmOpen').removeClass('VmClose');
			} else {
				jQuery('.".$className." ul:visible').delay(200).slideUp(500,'linear').parents('li').addClass('VmOpen').removeClass('VmClose');
				jQuery(this).parents('li').addClass('VmClose').removeClass('VmOpen');	
			}
		});
	});
" ;
	vmJsApi::addJScript('catClose', $js);

}

$cache = $params->get( 'vmcache', true );
$cachetime = $params->get( 'vmcachetime', 300 );

$active_category_id = vRequest::getInt('virtuemart_category_id', '0');
$category_id = $params->get('Parent_Category_id', 0);
//vmdebug('Use cached mod category',$params, $active_category_id, $category_id, $layout);
if($cache){
	//$key = 'products'.$category_id.'.'.$max_items.'.'.$filter_category.'.'.$display_style.'.'.$products_per_row.'.'.$show_price.'.'.$show_addtocart.'.'.$Product_group.'.'.$virtuemart_currency_id.'.'.$category_id.'.'.$filter_manufacturer.'.'.$manufacturer_id;
	$cache	= VmConfig::getCache('mod_virtuemart_category');
	$cache->setCaching(1);
	$cache->setLifeTime($cachetime);
	$db = JFactory::getDbo();
	echo $cache->get( array( 'mod_virtuemart_category', 'displayCatsMod' ), array($module, $params, $active_category_id, $category_id, $layout));

} else {
	echo mod_virtuemart_category::displayCatsMod($module, $params, $active_category_id, $category_id, $layout);
}

echo vmJsApi::writeJS();
?>