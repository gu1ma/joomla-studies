<?php
defined ('_JEXEC') or  die('Direct Access to ' . basename (__FILE__) . ' is not allowed.');
/*
 * Module Helper
 * @package VirtueMart
 * @copyright (C) 2011 - 2021 The VirtueMart Team
 * @Email: max@virtuemart.net
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 * @link https://virtuemart.net
 */

class mod_virtuemart_product {

	/*
	 * @deprecated
	 */
	static function addtocart ($product) {

		echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$product));
	}

	static function displayProductsMod($module, $params, $Product_group){

		vmLanguage::loadJLang('mod_virtuemart_product', true);

// Setting
		$max_items = 		$params->get( 'max_items', 2 ); //maximum number of items to display
		$layout = $params->get('layout','default');
		$category_id = 		$params->get( 'virtuemart_category_id', null ); // Display products from this category only
		$filter_category = 	(bool)$params->get( 'filter_category', 0 ); // Filter the category
		$manufacturer_id = 	$params->get( 'virtuemart_manufacturer_id', null ); // Display products from this manufacturer only
		$filter_manufacturer = 	(bool)$params->get( 'filter_manufacturer', 0 ); // Filter the manufacturer
		$display_style = 	$params->get( 'display_style', "div" ); // Display Style
		$products_per_row = $params->get( 'products_per_row', 1 ); // Display X products per Row
		$show_price = 		(bool)$params->get( 'show_price', 1 ); // Display the Product Price?
		$show_addtocart = 	(bool)$params->get( 'show_addtocart', 1 ); // Display the "Add-to-Cart" Link?
		$headerText = 		$params->get( 'headerText', '' ); // Display a Header Text
		$footerText = 		$params->get( 'footerText', ''); // Display a footerText


		$productModel = VmModel::getModel('Product');
		//if(!$products){
		$vendorId = vRequest::getInt('vendorid', 1);

		if ($filter_category ) $filter_category = TRUE;
		VirtueMartModelProduct::$omitLoaded = $params->get( 'omitLoaded', 0);
		$products = $productModel->getProductListing($Product_group, $max_items, $show_price, true, false,$filter_category, $category_id, $filter_manufacturer, $manufacturer_id, $params->get( 'omitLoaded', 0));
		//}

		if(empty($products)) return false;

		$productModel->addImages($products);

		shopFunctionsF::sortLoadProductCustomsStockInd($products,$productModel);
		if(empty($products)) return false;

		$totalProd = 		count( $products);

		$currency = CurrencyDisplay::getInstance( );

		ob_start();

		/* Load tmpl default */
		require(JModuleHelper::getLayoutPath('mod_virtuemart_product',$layout));
		$output = ob_get_clean();
		echo $output;

	}
}

?>
