<?php
defined('_JEXEC') or  die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/*
*Cart Ajax Module
*
 * @version $Id: mod_virtuemart_cart.php 10804 2023-03-20 10:36:15Z Milbo $
 * @package VirtueMart
 * @subpackage modules
 *
 * @author Sören, Max Milbers, Spyros
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2005 - 2021 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
*/

if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
VmConfig::loadConfig();
vmLanguage::loadJLang('mod_virtuemart_cart', true);
vmLanguage::loadJLang('com_virtuemart', true);
vmJsApi::jQuery();

vmJsApi::addJScript("/modules/mod_virtuemart_cart/assets/js/update_cart.js",false,false);


$viewName = vRequest::getString('view',0);
if($viewName=='cart'){
	$checkAutomaticPS = true;
} else {
	$checkAutomaticPS = false;
}

$currencyDisplay = CurrencyDisplay::getInstance( );
vmJsApi::cssSite();
$moduleclass_sfx 	= $params->get('moduleclass_sfx', '');
$show_price 		= (bool)$params->get( 'show_price', 1 ); // Display the Product Price?
$show_product_list 	= (bool)$params->get( 'show_product_list', 1 ); // Display the Product Price?
$dropdown_icon = $params->get( 'dropdown_icon', '' ); // User selected cart icon
$dropdown_alignment = (bool)$params->get( 'dropdown_alignment', 1 ); // Dropdown alignment

$options = array();
$session = JFactory::getSession($options);
$multixcart = VmConfig::get('multixcart',0);

$carts = array();
if($multixcart!='byproduct'){
	$carts[1] = $session->get('vmcart', 0, 'vm');
} else {
	$carts = $session->get('vmcarts', 0, 'vm');
}

$cart = VirtueMartCart::getCart();
/*if (VmConfig::get('oncheckout_show_images')) {
    $data = $cart->prepareAjaxData(true);
} else {
    $data = $cart->prepareAjaxData();
}*/
$vendorId = $cart->vendorId;
//vmdebug('cart module '.$multixcart,$vendorId,$carts);
if(!empty($carts)){
    foreach($carts as $vId=>$cartses) {
        if(!empty($cartses)){
            //This is strange we have the whole thing again in controllers/cart.php public function viewJS()
            $cart = VirtueMartCart::getCart(false, array(), NULL, $vId);
	        /*if (VmConfig::get('oncheckout_show_images')) {
		        $data = $cart->prepareAjaxData(true);
	        } else {
		        $data = $cart->prepareAjaxData();
	        }*/
            $data = $cart->prepareAjaxData();
	        require JModuleHelper::getLayoutPath('mod_virtuemart_cart', $params->get('layout', 'default'));
        }

    }

    //Reset cart to the selected one
    $cart = VirtueMartCart::getCart(false, array(), NULL, $vendorId);
} else {
	$data = $cart->prepareAjaxData();
    require JModuleHelper::getLayoutPath('mod_virtuemart_cart', $params->get('layout', 'default'));
}

echo vmJsApi::writeJS();
 ?>
