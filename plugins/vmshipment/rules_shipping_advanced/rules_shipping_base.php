<?php

defined ('_JEXEC') or die('Restricted access');

/**
 * Shipment plugin for general, rules-based shipments, like regular postal services with complex shipping cost structures
 *
 * @package VirtueMart
 * @subpackage Plugins - shipment
 * @copyright Copyright (C) 2004-2012 VirtueMart Team - All rights reserved.
 * @copyright Copyright (C) 2013 Reinhold Kainhofer, reinhold@kainhofer.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 *
 * @author Reinhold Kainhofer, based on the weight_countries shipping plugin by Valerie Isaksen
 *
 */
if (!class_exists( 'VmConfig' )) {
	require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/helpers/config.php');
	VmConfig::loadConfig();
}

// Only declare the class once...
// if (class_exists ('plgVmShipmentRules_Shipping_Base')) {
// 	return;
// }
if (!class_exists('RulesShippingFrameworkJoomla'))
	require_once (dirname(__FILE__) . DS . 'rules_shipping_framework_joomla.php');


/** Shipping costs according to general rules.
 *  Supported Variables: Weight, ZIP, Amount, Products (1 for each product, even if multiple ordered), Articles
 *  Assignable variables: Shipping, Name
 */
class plgVmShipmentRules_Shipping_Base extends vmPSPlugin {
	protected $helper = null;

	/**
	 * @param object $subject
	 * @param array  $config
	 */
	function __construct (& $subject, $config) {
		parent::__construct ($subject, $config);

		$this->_loggable = TRUE;
		$this->_tablepkey = 'id';
		$this->_tableId = 'id';
		$this->tableFields = array_keys ($this->getTableSQLFields ());
		$varsToPush = $this->getVarsToPush ();
		$this->addVarsToPushCore($varsToPush,0);
		$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);

		$this->helper = new RulesShippingFrameworkJoomla();
		$this->helper->setup();
	}

	/**
	 * Create the table for this plugin if it does not yet exist.
	 *
	 * @author Valérie Isaksen
	 */
	public function getVmPluginCreateTableSQL () {
		return $this->createTableSQL ('Shipment Rules Table');
	}
	
	/**
	 * @return array
	 */
	function getTableSQLFields () {
		$SQLfields = array(
			'id'                           => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id'          => 'int(11) UNSIGNED',
			'order_number'                 => 'char(32)',
			'virtuemart_shipmentmethod_id' => 'mediumint(1) UNSIGNED',
			'shipment_name'                => 'varchar(5000)',
			'rule_name'                    => 'varchar(500)',
			'order_weight'                 => 'decimal(10,4)',
			'order_articles'               => 'int(1)',
			'order_products'               => 'int(1)',
			'shipment_weight_unit'         => 'char(3) DEFAULT \'KG\'',
			'shipment_cost'                => 'decimal(10,2)',
			'tax_id'                       => 'smallint(1)'
		);
		return $SQLfields;
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the shipment-specific data.
	 *
	 * @param integer $virtuemart_order_id The order ID
	 * @param integer $virtuemart_shipmentmethod_id The selected shipment method id
	 * @param string  $shipment_name Shipment Name
	 * @return mixed Null for shipments that aren't active, text (HTML) otherwise
	 * @author Valérie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmOnShowOrderFEShipment ($virtuemart_order_id, $virtuemart_shipmentmethod_id, &$shipment_name) {
		$this->onShowOrderFE ($virtuemart_order_id, $virtuemart_shipmentmethod_id, $shipment_name);
	}

	/**
	 * This event is fired after the order has been stored; it gets the shipment method-
	 * specific data.
	 *
	 * @param int    $order_id The order_id being processed
	 * @param object $cart  the cart
	 * @param array  $order The actual order saved in the DB
	 * @return mixed Null when this method was not selected, otherwise true
	 * @author Valerie Isaksen
	 */
	function plgVmConfirmedOrder (VirtueMartCart $cart, $order) {

		if (!($method = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_shipmentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->shipment_element)) {
			return FALSE;
		}
		// We need to call getCosts, because in J3 $method->rule_name and $method->cost as set in getCosts is no longer preserved.
		// Instead, we simply call getCosts again, which as a side-effect sets all those members of $method.
		$costs = $this->helper->getCosts($cart,$method);
		if (empty($costs)) 
			return FALSE;
		$rulename = $costs[0]['rulename'];
// 		$rulename = $this->helper->getRuleName($method->virtuemart_shipmentmethod_id);
		$variables = $this->helper->getRuleVariables($method->virtuemart_shipmentmethod_id);
		$values['virtuemart_order_id'] = $order['details']['BT']->virtuemart_order_id;
		$values['order_number'] = $order['details']['BT']->order_number;
		$values['virtuemart_shipmentmethod_id'] = $order['details']['BT']->virtuemart_shipmentmethod_id;
		$values['shipment_name'] = $this->renderPluginName ($method);
		$values['rule_name'] = $rulename; 
		$values['order_weight'] = $variables['weight'];
		$values['order_articles'] = $variables['articles'];
		$values['order_products'] = $variables['products'];
		$values['shipment_weight_unit'] = $method->weight_unit;
		$values['shipment_cost'] = $method->cost;
		$values['tax_id'] = $method->tax_id;
		$this->storePSPluginInternalData ($values);

		return TRUE;
	}
	function getCosts (VirtueMartCart $cart, $method, $cart_prices) {
		$costs = $this->helper->getCosts($cart, $method, $cart_prices);
		if (empty($costs)) {
			return false;
		} else {
			return $costs[0]['cost'];
		}
	}
	protected function checkConditions ($cart, $method, $cart_prices) {
		if(parent::checkConditions($cart, $method, $cart_prices)){
			return $this->helper->checkConditions($cart, $method, $cart_prices);
		} else {
			return false;
		}

	}
	/**
	 * This method is fired when showing the order details in the backend.
	 * It displays the shipment-specific data.
	 * NOTE, this plugin should NOT be used to display form fields, since it's called outside
	 * a form! Use plgVmOnUpdateOrderBE() instead!
	 *
	 * @param integer $virtuemart_order_id The order ID
	 * @param integer $virtuemart_shipmentmethod_id The order shipment method ID
	 * @param object  $_shipInfo Object with the properties 'shipment' and 'name'
	 * @return mixed Null for shipments that aren't active, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderBEShipment ($virtuemart_order_id, $virtuemart_shipmentmethod_id) {
		if (!($this->selectedThisByMethodId ($virtuemart_shipmentmethod_id))) {
			return NULL;
		}
		$html = $this->getOrderShipmentHtml ($virtuemart_order_id);
		return $html;
	}

	/**
	 * @param $virtuemart_order_id
	 * @return string
	 */
	function getOrderShipmentHtml ($virtuemart_order_id) {

		$db = JFactory::getDBO ();
		$q = 'SELECT * FROM `' . $this->_tablename . '` '
			. 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
		$db->setQuery ($q);
		if (!($shipinfo = $db->loadObject ())) {
			vmWarn (500, $q . " " . $db->getErrorMsg ());
			return '';
		}

		if (!class_exists ('CurrencyDisplay')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
		}

		$currency = CurrencyDisplay::getInstance ();
		$tax = ShopFunctions::getTaxByID ($shipinfo->tax_id);
		$taxDisplay = is_array ($tax) ? $tax['calc_value'] . ' ' . $tax['calc_value_mathop'] : $shipinfo->tax_id;
		$taxDisplay = ($taxDisplay == -1) ? JText::_ ('COM_VIRTUEMART_PRODUCT_TAX_NONE') : $taxDisplay;

		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE ();
		$html .= $this->getHtmlRowBE ('OTSHIPMENT_RULES_SHIPPING_NAME', $shipinfo->shipment_name);
		$html .= $this->getHtmlRowBE ('OTSHIPMENT_RULES_WEIGHT', $shipinfo->order_weight . ' ' . ShopFunctions::renderWeightUnit ($shipinfo->shipment_weight_unit));
		$html .= $this->getHtmlRowBE ('OTSHIPMENT_RULES_ARTICLES', $shipinfo->order_articles . '/' . $shipinfo->order_products);
		$html .= $this->getHtmlRowBE ('OTSHIPMENT_RULES_COST', $currency->priceDisplay ($shipinfo->shipment_cost));
		$html .= $this->getHtmlRowBE ('OTSHIPMENT_RULES_TAX', $taxDisplay);
		$html .= '</table>' . "\n";

		return $html;
	}
	
	/** Include the rule name in the shipment name */
	protected function renderPluginName ($plugin) {

		$return = '';
		$plugin_name = $this->_psType . '_name';
		$plugin_desc = $this->_psType . '_desc';
		$description = '';
		// 		$params = new JParameter($plugin->$plugin_params);
		// 		$logo = $params->get($this->_psType . '_logos');
		$logosFieldName = $this->_psType . '_logos';
		$logos = $plugin->$logosFieldName;
		if (!empty($logos)) {
			$return = $this->displayLogos ($logos) . ' ';
		}
		if (!empty($plugin->$plugin_desc)) {
			$description = '<span class="' . $this->_type . '_description">' . $plugin->$plugin_desc . '</span>';
		}
		$rulename='';
		if (!empty($plugin->rule_name)) {
			$rulename=" (".htmlspecialchars($plugin->rule_name).")";
		}
		$pluginName = $return . '<span class="' . $this->_type . '_name">' . $plugin->$plugin_name . $rulename.'</span>' . $description;
		return $pluginName;
	}

	/**
	 * update the plugin cart_prices
	 *
	 * Override the plugin's setCartPrices to allow reverse tax calculation (i.e. shipping costs are 
	 * given with taxes, the net price and the tax is calculated from the gross shipping costs)-
	 *  We need separate versions for VM2 and VM3.
	 *
	 * @author Valérie Isaksen (original), Reinhold Kainhofer (tax calculations from shippingWithTax)
	 *
	 * @param $cart_prices: $cart_prices['salesPricePayment'] and $cart_prices['paymentTax'] updated. Displayed in the cart.
	 * @param $value :   fee
	 * @param $tax_id :  tax id
	 */
	function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method, $progressive = true) {
		// BEGIN_RK_CHANGES
		$includes_tax = isset($method->includes_tax) && $method->includes_tax;
		if (!$includes_tax) {
			// Use the default from the parent class, so any changes done in future versions apply directly
			return parent::setCartPrices($cart, $cart_prices, $method, $progressive);
		}
		
		// if ($includes_tax==true) => We need to modify the code to remove all taxes from the calculations
		// CAUTION: Need to keep this code in sync with the VM core code, in particular the 
		// setCartPrices in Lines 984ff, File administrator/components/com_virtuemart/plugins/vmpsplugin.php
		// END_RK_CHANGES

		$idN = 'virtuemart_'.$this->_psType.'method_id';

		$_psType = ucfirst ($this->_psType);

		if (!class_exists ('calculationHelper')) {
			// BEGIN_RK_CHANGES
			if(!defined('VM_VERSION') or VM_VERSION < 3){ // VM2:
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
			} else { // VM 3:
				require(VMPATH_ADMIN . DS . 'helpers' . DS . 'calculationh.php');
			}
			// END_RK_CHANGES
		}

		$calculator = calculationHelper::getInstance ();

		if($this->_toConvert){
			$calculator = calculationHelper::getInstance ();
			foreach($this->_toConvert as $c){
				if(isset($method->$c)){
					$method->$c = $calculator->_currencyDisplay->convertCurrencyTo($method->currency_id,$method->$c,true);
				} else {
					$method->$c = 0.0;
				}

			}
		}

		$cart_prices[$this->_psType . 'Value'] = $calculator->roundInternal ($this->getCosts ($cart, $method, $cart_prices), 'salesPrice');
		if(!isset($cart_prices[$this->_psType . 'Value'])) $cart_prices[$this->_psType . 'Value'] = 0.0;
		if(!isset($cart_prices[$this->_psType . 'Tax'])) $cart_prices[$this->_psType . 'Tax'] = 0.0;

		if($this->_psType=='payment'){
			$cartTotalAmountOrig = $this->getCartAmount($cart_prices);

			if(!isset($method->cost_percent_total)) $method->cost_percent_total = 0.0;
			if(!isset($method->cost_per_transaction)) $method->cost_per_transaction = 0.0;

			if(!$progressive){
				//Simple
				$cartTotalAmount=($cartTotalAmountOrig + $method->cost_per_transaction) * (1 +($method->cost_percent_total * 0.01));
				//vmdebug('Simple $cartTotalAmount = ('.$cartTotalAmountOrig.' + '.$method->cost_per_transaction.') * (1 + ('.$method->cost_percent_total.' * 0.01)) = '.$cartTotalAmount );
				//vmdebug('Simple $cartTotalAmount = '.($cartTotalAmountOrig + $method->cost_per_transaction).' * '. (1 + $method->cost_percent_total * 0.01) .' = '.$cartTotalAmount );
			} else {
				//progressive
				$cartTotalAmount = ($cartTotalAmountOrig + $method->cost_per_transaction) / (1 -($method->cost_percent_total * 0.01));
				//vmdebug('Progressive $cartTotalAmount = ('.$cartTotalAmountOrig.' + '.$method->cost_per_transaction.') / (1 - ('.$method->cost_percent_total.' * 0.01)) = '.$cartTotalAmount );
				//vmdebug('Progressive $cartTotalAmount = '.($cartTotalAmountOrig + $method->cost_per_transaction) .' / '. (1 - $method->cost_percent_total * 0.01) .' = '.$cartTotalAmount );
			}

			$cart_prices[$this->_psType . 'Value'] = $cartTotalAmount - $cartTotalAmountOrig;
			if(!empty($method->cost_min_transaction) and $method->cost_min_transaction!='' and $cart_prices[$this->_psType . 'Value'] < $method->cost_min_transaction){
				$cart_prices[$this->_psType . 'Value'] = $method->cost_min_transaction;

			}
		}

		if(!isset($cart_prices['salesPrice' . $_psType])) $cart_prices['salesPrice' . $_psType] = $cart_prices[$this->_psType . 'Value'];

		// BEGIN_RK_CHANGES
		// If the given shipping cost includes the tax, set the final sales price to that value beforehand!
		if ($includes_tax) {
			$cart_prices['salesPrice' . $_psType] = $cart_prices[$this->_psType . 'Value'];
		}
		// END_RK_CHANGES

		$taxrules = array();
		if(isset($method->tax_id) and (int)$method->tax_id === -1){

		} else if (!empty($method->tax_id)) {
			$cart_prices[$this->_psType . '_calc_id'] = $method->tax_id;

			$db = JFactory::getDBO ();
			$q = 'SELECT * FROM #__virtuemart_calcs WHERE `virtuemart_calc_id`="' . $method->tax_id . '" ';
			$db->setQuery ($q);
			$taxrules = $db->loadAssocList ();

			if(!empty($taxrules) ){
				foreach($taxrules as &$rule){
					if(!isset($rule['subTotal'])) $rule['subTotal'] = 0;
					if(!isset($rule['taxAmount'])) $rule['taxAmount'] = 0;
					$rule['subTotalOld'] = $rule['subTotal'];
					$rule['taxAmountOld'] = $rule['taxAmount'];
					$rule['taxAmount'] = 0;
					$rule['subTotal'] = $cart_prices[$this->_psType . 'Value'];
					// BEGIN_RK_CHANGES
					if ($includes_tax) {
						$calculator->setRevert (true);
						$valueWithoutTax = $calculator->roundInternal ($calculator->interpreteMathOp($rule, $rule['subTotal']));
						$cart_prices[$this->_psType . 'TaxPerID'][$rule['virtuemart_calc_id']] = $calculator->roundInternal($rule['subTotal'] - $valueWithoutTax, 'salesPrice');
						$calculator->setRevert (false);
//  						$rule['subTotal'] = $valueWithoutTax;
					} else {
						$cart_prices[$this->_psType . 'TaxPerID'][$rule['virtuemart_calc_id']] = $calculator->roundInternal($calculator->roundInternal($calculator->interpreteMathOp($rule, $rule['subTotal'])) - $rule['subTotal'], 'salesPrice');
					}
					// END_RK_CHANGES
					$cart_prices[$this->_psType . 'Tax'] += $cart_prices[$this->_psType . 'TaxPerID'][$rule['virtuemart_calc_id']];
				}
			}
		} else {
			// BEGIN_RK_CHANGES: VM change in VM3!
			if (isset($calculator->_cartData) && is_array($calculator->_cartData)) { // VM2:
				$taxrules = array_merge($calculator->_cartData['VatTax'],$calculator->_cartData['taxRulesBill']);
			} else { // VM3:
				$taxrules = array_merge($cart->cartData['VatTax'],$cart->cartData['taxRulesBill']);
			}
			// END_RK_CHANGES
			$cartdiscountBeforeTax = $calculator->roundInternal($calculator->cartRuleCalculation($cart->cartData['DBTaxRulesBill'], $cart->cartPrices['salesPrice']));

			if(!empty($taxrules) ){

				foreach($taxrules as &$rule){
					//Quickn dirty
					if(!isset($rule['calc_kind'])) $rule = (array)VmModel::getModel('calc')->getCalc($rule['virtuemart_calc_id']);

					if(!isset($rule['subTotal'])) $rule['subTotal'] = 0;
					if(!isset($rule['taxAmount'])) $rule['taxAmount'] = 0;
					if(!isset($rule['DBTax'])) $rule['DBTax'] = 0;
					// BEGIN_RK_CHANGES: If shipping includes tax, the distribution of the shipping cost to the tax rules needs to be determined after taxes. Otherwise the distribution should be calculated before taxes
					if(!isset($rule['percentage'])/* && $rule['subTotal'] < $cart->cartPrices['salesPrice']*/) {
					// END_RK_CHANGES
						$rule['percentage'] = ($rule['subTotal'] + $rule['DBTax']) / ($cart->cartPrices['salesPrice'] + $cartdiscountBeforeTax);
					} else if(!isset($rule['percentage'])) {
						$rule['percentage'] = 1;
					}
					$rule['subTotalOld'] = $rule['subTotal'];
					$rule['subTotal'] = 0;
					$rule['taxAmountOld'] = $rule['taxAmount'];
					$rule['taxAmount'] = 0;
				}

				foreach($taxrules as &$rule){
					$rule['subTotal'] = $cart_prices[$this->_psType . 'Value'] * $rule['percentage'];
					if(!isset($cart_prices[$this->_psType . 'Tax'])) $cart_prices[$this->_psType . 'Tax'] = 0.0;
					// BEGIN_RK_CHANGES
					if ($includes_tax) {
						$calculator->setRevert (true);
						$valueWithoutTax = $calculator->roundInternal ($calculator->interpreteMathOp($rule, $rule['subTotal']));
						$cart_prices[$this->_psType . 'TaxPerID'][$rule['virtuemart_calc_id']] = $calculator->roundInternal($rule['subTotal'] - $valueWithoutTax, 'salesPrice');
						$calculator->setRevert (false);
// 						$rule['subTotal'] = $valueWithoutTax;
					} else {
						$cart_prices[$this->_psType . 'TaxPerID'][$rule['virtuemart_calc_id']] = $calculator->roundInternal($calculator->roundInternal($calculator->interpreteMathOp($rule, $rule['subTotal'])) - $rule['subTotal'], 'salesPrice');
					}
					// END_RK_CHANGES
					$cart_prices[$this->_psType . 'Tax'] += $cart_prices[$this->_psType . 'TaxPerID'][$rule['virtuemart_calc_id']];

				}
			}
		}

		if(empty($method->cost_per_transaction)) $method->cost_per_transaction = 0.0;
		if(empty($method->cost_min_transaction)) $method->cost_min_transaction = 0.0;
		if(empty($method->cost_percent_total)) $method->cost_percent_total = 0.0;

		if (count ($taxrules) > 0 ) {

			// BEGIN_RK_CHANGES
			if ($includes_tax) {
				// Calculate the net shipping cost by removing all taxes:
				$calculator->setRevert (true);
				$cart_prices[$this->_psType . 'Value'] = $calculator->roundInternal ($calculator->executeCalculation($taxrules, $cart_prices[$this->_psType . 'Value'], true), 'salesPrice');
				$calculator->setRevert (false);
			} else {
				$cart_prices['salesPrice' . $_psType] = $calculator->roundInternal ($calculator->executeCalculation ($taxrules, $cart_prices[$this->_psType . 'Value'],true,false), 'salesPrice');
//				$cart_prices[$this->_psType . 'Tax'] = $calculator->roundInternal (($cart_prices['salesPrice' . $_psType] -  $cart_prices[$this->_psType . 'Value']), 'salesPrice');
			}
			// END_RK_CHANGES
			reset($taxrules);

			foreach($taxrules as &$rule){
				if(!isset($cart_prices[$this->_psType . '_calc_id']) or !is_array($cart_prices[$this->_psType . '_calc_id'])) $cart_prices[$this->_psType . '_calc_id'] = array();
				$cart_prices[$this->_psType . '_calc_id'][] = $rule['virtuemart_calc_id'];

				if(isset($rule['subTotalOld'])) $rule['subTotal'] += $rule['subTotalOld'];
				if(isset($rule['taxAmountOld'])) $rule['taxAmount'] += $rule['taxAmountOld'];
			}

		} else {

			$cart_prices['salesPrice' . $_psType] = $cart_prices[$this->_psType . 'Value'];
			$cart_prices[$this->_psType . 'Tax'] = 0;
			$cart_prices[$this->_psType . '_calc_id'] = 0;
		}
		//$c[$this->_psType][$method->$idN] =& $cart_prices;
		//if($_psType='Shipment')vmTrace('setCartPrices '.$cart_prices['salesPrice' . $_psType]);
		return $cart_prices['salesPrice' . $_psType];

	}

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 *
	 */
	function plgVmOnStoreInstallShipmentPluginTable ($jplugin_id) {
		return $this->onStoreInstallPluginTable ($jplugin_id);
	}

	/**
	 * @param VirtueMartCart $cart
	 * @return null
	 */
	public function plgVmOnSelectCheckShipment (VirtueMartCart &$cart) {
		return $this->OnSelectCheck ($cart);
	}

	/**
	 * plgVmDisplayListFE
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for example
	 *
	 * @param object  $cart Cart object
	 * @param integer $selected ID of the method selected
	 * @return boolean True on success, false on failures, null when this plugin was not selected.
	 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEShipment (VirtueMartCart $cart, $selected, &$htmlIn) {
		return $this->displayListFE ($cart, $selected, $htmlIn);
	}

	/**
	 * @param VirtueMartCart $cart
	 * @param array          $cart_prices
	 * @param                $cart_prices_name
	 * @return bool|null
	 */
	public function plgVmOnSelectedCalculatePriceShipment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
	}

	/**
	 * plgVmOnCheckAutomaticSelected
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedShipment (VirtueMartCart $cart, array $cart_prices, &$shipCounter) {
		if ($shipCounter > 1) {
			return 0;
		}
		return $this->onCheckAutomaticSelected ($cart, $cart_prices, $shipCounter);
	}

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrint ($order_number, $method_id) {
		return $this->onShowOrderPrint ($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsShipment ($name, $id, &$data) {
		return $this->declarePluginParams ('shipment', $name, $id, $data);
	}

	/* This function is needed in VM 2.0.14 etc. because otherwise the params are not saved */
	function plgVmSetOnTablePluginParamsShipment ($name, $id, &$table) {

		return $this->setOnTablePluginParams ($name, $id, $table);
	}

	function plgVmDeclarePluginParamsShipmentVM3 (&$data) {
		return $this->declarePluginParams ('shipment', $data);
	}

	function plgVmSetOnTablePluginShipment(&$data,&$table){

		$name = $data['shipment_element'];
		$id = $data['shipment_jplugin_id'];

		if (!empty($this->_psType) and !$this->selectedThis ($this->_psType, $name, $id)) {
			return FALSE;
		}
		if (isset($data['rules1'])) {
			// Try to parse all rules (and spit out error) to inform the user. There is no other 
			// reason to parse the rules here, it's really only to trigger warnings/errors in case of a syntax error.
			$this->helper->parseRuleSyntax ($data['rules1'], isset($data['countries1'])?$data['countries1']:array(), $data['tax_id1']);
			$this->helper->parseRuleSyntax ($data['rules2'], isset($data['countries2'])?$data['countries2']:array(), $data['tax_id2']);
			$this->helper->parseRuleSyntax ($data['rules3'], isset($data['countries3'])?$data['countries3']:array(), $data['tax_id3']);
			$this->helper->parseRuleSyntax ($data['rules4'], isset($data['countries4'])?$data['countries4']:array(), $data['tax_id4']);
			$this->helper->parseRuleSyntax ($data['rules5'], isset($data['countries5'])?$data['countries5']:array(), $data['tax_id5']);
			$this->helper->parseRuleSyntax ($data['rules6'], isset($data['countries6'])?$data['countries6']:array(), $data['tax_id6']);
			$this->helper->parseRuleSyntax ($data['rules7'], isset($data['countries7'])?$data['countries7']:array(), $data['tax_id7']);
			$this->helper->parseRuleSyntax ($data['rules8'], isset($data['countries8'])?$data['countries8']:array(), $data['tax_id8']);
		}
		$ret=$this->setOnTablePluginParams ($name, $id, $table);
		return $ret;
	}

	/* Display product shipping costs on product details page (required in some jurisdictions)
	 *
	 * Copied from the weight_countries shipment plugin
	 * @copyright Copyright (C) 2004-2012 VirtueMart Team - All rights reserved.
	 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
	 * @author Valerie Isaksen
	 */
/*	function plgVmOnProductDisplayShipment($product, &$productDisplayShipments) {

		if ($this->getPluginMethods($product->virtuemart_vendor_id) === 0) {

			return FALSE;
		}
		if (!class_exists('VirtueMartCart'))
			require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');

		$html = '';
		if (!class_exists('CurrencyDisplay'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
		$currency = CurrencyDisplay::getInstance();

		foreach ($this->methods as $this->_currentMethod) {

			if($this->_currentMethod->show_on_pdetails){
				if(!isset($cart)) {
					$cart = VirtueMartCart::getCart();
					$cart->prepareCartData();
				}
				$prices = array('salesPrice'=>0.0);
				if(isset($cart->cartPrices)){
					$prices['salesPrice'] = $cart->cartPrices['salesPrice'];
				}
				if(isset($product->prices)){
					$prices['salesPrice'] += $product->prices['salesPrice'];
				}

				if($this->checkConditions($cart, $this->_currentMethod, $prices, $product)){

					$product->prices['shipmentPrice'] = $this->getCosts($cart, $this->_currentMethod, $cart->cartPrices);
					// TODO: Implement shipping costs with tax included...
					if(isset($product->prices['VatTax']) and count($product->prices['VatTax'])>0){
						reset($product->prices['VatTax']);
						$rule = current($product->prices['VatTax']);
						if(isset($rule[1])) {
							$product->prices['shipmentTax'] = $product->prices['shipmentPrice'] * $rule[1]/100.0;
							$product->prices['shipmentPrice'] = $product->prices['shipmentPrice'] * (1 + $rule[1]/100.0);
						}
					}

					// TODO: Implement custom display template
					$html = $this->renderByLayout( 'default', array("method" => $this->_currentMethod, "cart" => $cart,"product" => $product,"currency" => $currency) );
				}
			}

		}

		$productDisplayShipments[] = $html;

	}
*/
}

// No closing tag
