<?php
defined ('_JEXEC') or die();
/**
 * @author Max Milbers
 * @copyright Copyright (C) VirtueMart Team - All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL 2, see COPYRIGHT.php
 */


/**
 * Supports a modal product picker.
 *
 *
 */
class JFormFieldProduct extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @author      Valerie Cartan Isaksen
	 * @var		string
	 *
	 */
	var $type = 'product';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */


	function getInput() {
		if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
		$key = ($this->element['key_field'] ? $this->element['key_field'] : 'value');
		$val = ($this->element['value_field'] ? $this->element['value_field'] : $this->name);
		VmConfig::loadConfig();
		return JHtml::_('select.genericlist',  $this->_getProducts(), $this->name, 'class="inputbox"   ', 'value', 'text', $this->value, $this->id);
	}

	static public function _getProducts($virtuemart_category_id=0) {

		static $hash = array();
		if(is_array($virtuemart_category_id)){
			$catHash = implode('.',$virtuemart_category_id);
		} else {
			$catHash = $virtuemart_category_id;
		}
		if(isset($hash[$catHash])){
			return $hash[$catHash];
		} else {
			$productModel = VmModel::getModel('Product');
			$productModel->_noLimit = true;
			if(vmAccess::manager('managevendors')){
				$productModel->virtuemart_vendor_id = 0;
			}

			$onlyPublished = true;
			$params = array('searchcustoms'=>false,'virtuemart_custom_id'=>false, 'keyword' =>false, 'published' =>1,'virtuemart_manufacturer_id' => 0, 'product_parent_id' => 0);
			$ids = $productModel->sortSearchListQuery ($onlyPublished, $virtuemart_category_id, FALSE, FALSE, $params );
			$productModel->listing = TRUE;
			$products = $productModel->getProducts ($ids, false, false, $onlyPublished, true);
			$productModel->listing = FALSE;

			//$products = $productModel->getProductListing(false, false, false, false, true, $virtuemart_category_id, $virtuemart_category_id);
			$productModel->_noLimit = false;
			$i = 0;
			$hash[$catHash] = array();
			$hash[$catHash][$i]['value'] = 0;
			$hash[$catHash][$i]['text'] = vmText::_("COM_VIRTUEMART_LIST_EMPTY_OPTION");

			foreach ($products as $product) {
				$hash[$catHash][$i]['value'] = $product->virtuemart_product_id;
				$hash[$catHash][$i]['text'] = $product->product_name. " (". $product->product_sku.")";
				$i++;
			}
		}

		return $hash[$catHash];
	}

}