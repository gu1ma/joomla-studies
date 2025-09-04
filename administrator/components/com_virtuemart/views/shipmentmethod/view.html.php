<?php
/**
*
* Shipment  View
*
* @package	VirtueMart
* @subpackage Shipment
* @author RickG
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: view.html.php 10880 2023-07-04 17:22:38Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * HTML View class for maintaining the list of shipment
 *
 * @package	VirtueMart
 * @subpackage Shipment
 * @author RickG
 */
class VirtuemartViewShipmentmethod extends VmViewAdmin {

	function display($tpl = null) {

		$model = VmModel::getModel();

		$this->SetViewTitle();

		$layoutName = vRequest::getCmd('layout', 'default');
		if ($layoutName == 'edit') {
			vmLanguage::loadJLang('plg_vmpsplugin', false);

			JForm::addFieldPath(VMPATH_ADMIN .'/fields');

			$shipment = $model->getShipment();
			$this->setOrigLang($shipment);
			$this->checkConditionsCore = false;
			// Get the payment XML.
			$formFile	= vRequest::filterPath( VMPATH_ROOT .'/plugins/vmshipment/'. $shipment->shipment_element .'/'. $shipment->shipment_element . '.xml');
			if (file_exists($formFile)){

				$form = JForm::getInstance($shipment->shipment_element, $formFile, array(),false, '//vmconfig | //config[not(//vmconfig)]');
				$shipment->params = new stdClass();
				$varsToPush = vmPlugin::getVarsToPushFromForm($form);

				VmTable::bindParameterableToSubField($shipment,$varsToPush);
				$shipment->bind($shipment->params);

				$props = $shipment->getProperties();

				$form->bind($props);
				$fdata = $form->getData()->toArray();
				if(isset($fdata['checkConditionsCore']) or isset($fdata['params']['checkConditionsCore'])){
					$this->checkConditionsCore = true;
					vmPSPlugin::addVarsToPushCore($varsToPush);
					VmTable::bindParameterableToSubField($shipment,$varsToPush);

					$toRemove = array();
					vmPSPlugin::addVarsToPushCore($toRemove,1);
					foreach($toRemove as $name=>$v){
						$form->removeField($name,'params');
					}
					$props = $shipment->getProperties();
					$this->shipmentList = shopfunctions::renderShipmentDropdown($shipment->virtuemart_shipmentmethod_ids);
				}
				$shipment->form = $form;
				$shipment->form->bind($props);

			} else {
				$shipment->form = null;
			}

			if($this->showVendors()){
				$this->vendorList= ShopFunctions::renderVendorList($shipment->virtuemart_vendor_id);
			}

			$this->assignRef('shipment', $shipment);
			$this->pluginList = self::renderInstalledShipmentPlugins($shipment->shipment_jplugin_id);
			$this->shopperGroupList = ShopFunctions::renderShopperGroupList($shipment->virtuemart_shoppergroup_ids,true);

			$currency_model = VmModel::getModel ('currency');
			$currencies = $currency_model->getCurrencies ();

			$currency = VirtueMartModelVendor::getVendorCurrency ($shipment->virtuemart_vendor_id);
			$this->assignRef('vendor_currency', $currency->currency_symbol);

			if(empty($shipment->currency_id)) $shipment->currency_id = $currency->virtuemart_currency_id;
			$attrs['class'] = 'vm-chzn-select vm-drop';
			$this->currencyList = JHtml::_ ('select.genericlist', $currencies, 'currency_id', $attrs, 'virtuemart_currency_id', 'currency_name', $shipment->currency_id);

			$this->addStandardEditViewCommands($shipment->virtuemart_shipmentmethod_id);

		} else {
			JToolbarHelper::custom('cloneshipment', 'copy', 'copy', vmText::_('COM_VIRTUEMART_SHIPMENT_CLONE'), true);

			$this->addStandardDefaultViewCommands();
			$this->addStandardDefaultViewLists($model);

			$this->shipments = $model->getShipments();
			vmLanguage::loadJLang('com_virtuemart_shoppers',TRUE);

			foreach ($this->shipments as &$data){
				// Write the first 5 shoppergroups in the list
				$data->shipmentShoppersList = shopfunctions::renderGuiList($data->virtuemart_shoppergroup_ids,'shoppergroups','shopper_group_name','shoppergroup');
			}

			$this->pagination = $model->getPagination();

			if($this->showVendors()){
				$this->lists['vendors'] = Shopfunctions::renderVendorList($model->virtuemart_vendor_id, 'virtuemart_vendor_id', true);
			}
		}

		parent::display($tpl);
	}

	function renderInstalledShipmentPlugins($selected) {
		$db = JFactory::getDBO();

		$table = '#__extensions';
		$enable = 'enabled';
		$ext_id = 'extension_id';

		$q = 'SELECT * FROM `'.$table.'` WHERE `folder` = "vmshipment" AND `state`="0" ORDER BY `ordering`,`name` ASC';
		$db->setQuery($q);
		$result = $db->loadAssocList($ext_id);
		if(empty($result)){
			$app = JFactory::getApplication();
			$app -> enqueueMessage(vmText::_('COM_VIRTUEMART_NO_SHIPMENT_PLUGINS_INSTALLED'));
		}

		foreach ($result as &$sh) {
			$sh['name'] = vmText::_($sh['name']);
		}
		$attribs='style= "width: 300px;"';
		return JHtml::_('select.genericlist', $result, 'shipment_jplugin_id', $attribs, $ext_id, 'name', $selected);
	}

	public function ajaxCategoryDropDown($id){

		$param = '';
		if(!empty($this->categoryId)){
			$param = '&virtuemart_category_id='.$this->categoryId;
		} else if(!empty($this->product->virtuemart_product_id)){
			$param = '&virtuemart_product_id='.$this->product->virtuemart_product_id;
		}
		$eOpt = vmText::sprintf( 'COM_VIRTUEMART_SELECT' ,  vmText::_('COM_VIRTUEMART_CATEGORY'));

		$id = 'categories';

		vmJsApi::ajaxCategoryDropDown($id, $param, $eOpt);

	}

}
// pure php no closing tag
