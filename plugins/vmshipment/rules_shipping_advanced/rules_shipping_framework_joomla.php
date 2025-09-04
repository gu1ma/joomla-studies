<?php
/**
 * Shipping by Rules generic helper class (Joomla/VM-specific)
 * Reinhold Kainhofer, Open Tools, office@open-tools.net
 * @copyright (C) 2012-2015 - Reinhold Kainhofer
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

// defined('_JEXEC') or	 die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;

// if (!class_exists( 'VmConfig' )) 
//     require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
// VmConfig::loadConfig();

if (!class_exists( 'RulesShippingFramework' )) 
	require_once (dirname(__FILE__) . DS . 'library' . DS . 'rules_shipping_framework.php');

// $test=new asdfasdsf();
class RulesShippingFrameworkJoomla extends RulesShippingFramework {
	/* Constructor: Register the available scopings */
	function __construct() {
		parent::__construct();
		$this->registerScopings(array(
			"categories"    => 'categories',
			"subcategories" => 'subcategories',
			"products"      => 'products',
			"skus"          => 'products',
			"vendors"       => 'vendors',
			"manufacturers" => 'manufacturers',
		));
	}
	function getCustomFunctions() {
		// Let other plugins add custom functions! 
		// The onVmShippingRulesRegisterCustomFunctions() trigger is expected to return an array of the form:
		//   array ('functionname1' => 'function-to-be-called',
		//          'functionname2' => array($classobject, 'memberfunc')),
		//          ...);
		JPluginHelper::importPlugin('vmshipmentrules');
		$defs = vDispatcher::trigger('onVmShippingRulesRegisterCustomFunctions',array());
		$custfuncdefs = array();
		if (!empty($defs)) {
			$custfuncdefs = call_user_func_array('array_merge', $defs);
		}
		$custfuncdefs['convertfromcurrency'] = array($this, 'convert_from_currency');
		$custfuncdefs['converttocurrency'] = array($this, 'convert_to_currency');
		
		return $custfuncdefs;
	}
	
	protected function printMessage($message, $type) {
		// Keep track of messages, so we don't print them twice:
		global $printed_messages;
		if (!isset($printed_messages))
			$printed_messages = array();
		if (!in_array($message, $printed_messages)) {
			if ($type=='debug') {
				vmDebug($message);
			} else {
				JFactory::getApplication()->enqueueMessage($message, $type);
			}
			$printed_messages[] = $message;
		}
	}

	public function __($string) {
		$args = func_get_args();
		if (count($args)>1) {
			return call_user_func_array(array("JText", "sprintf"), $args);
		} else {
			return call_user_func(array("JText", "_"), $string);
		}
	}

	protected function setMethodCosts($method, $match, $costs) {
		$r = $match["rule"];
		// Allow some system-specific code, e.g. setting some members of $method, etc.
		$method->tax_id = $r->ruleinfo['tax_id'];
		// TODO: Shall we include the name of the modifiers, too?
		$method->rule_name = $match["rule_name"];
		$method->cost = $costs;
		$method->includes_tax = $r->includes_tax;
	}
	
	protected function getCartProducts($cart, $method) {
		return $cart->products;
	}
	
	protected function getMethodId($method) {
		return $method->virtuemart_shipmentmethod_id;
	}

	protected function getMethodName($method) {
		return $method->shipment_name;
	}

	protected function parseMethodRules (&$method) {
		$this->parseMethodRule ($method->rules1, $method->countries1, array('tax_id'=>$method->tax_id1), $method);
		$this->parseMethodRule ($method->rules2, $method->countries2, array('tax_id'=>$method->tax_id2), $method);
		$this->parseMethodRule ($method->rules3, $method->countries3, array('tax_id'=>$method->tax_id3), $method);
		$this->parseMethodRule ($method->rules4, $method->countries4, array('tax_id'=>$method->tax_id4), $method);
		$this->parseMethodRule ($method->rules5, $method->countries5, array('tax_id'=>$method->tax_id5), $method);
		$this->parseMethodRule ($method->rules6, $method->countries6, array('tax_id'=>$method->tax_id6), $method);
		$this->parseMethodRule ($method->rules7, $method->countries7, array('tax_id'=>$method->tax_id7), $method);
		$this->parseMethodRule ($method->rules8, $method->countries8, array('tax_id'=>$method->tax_id8), $method);
	}

	/**
	 * Functions to calculate the cart variables:
	 *   - getOrderArticles($cart, $products)
	 *   - getOrderProducts
	 *   - getOrderDimensions
	 */
	/** Functions to calculate all the different variables for the given cart and given (sub)set of products in the cart */
	protected function getOrderCounts ($cart, $products, $method) {
		$counts = array(
			'articles' => 0,
			'products' => count($products),
			'quantity' => 0,
			'minquantity' => 9999999999,
			'maxquantity' => 0,
		);

		foreach ($products as $product) {
			$counts['articles']   += $product->quantity;
			$counts['maxquantity'] = max ($counts['maxquantity'], $product->quantity);
			$counts['minquantity'] = min ($counts['minquantity'], $product->quantity);
		}
		$counts['quantity'] = $counts['articles'];
		
		return $counts;
	}

	protected function getOrderDimensions ($cart, $products, $method) {
		/* Cache the value in a static variable and calculate it only once! */
		$dimensions=array(
			'volume' => 0,
			'maxvolume' => 0, 'minvolume' => 9999999999,
			'maxlength' => 0, 'minlength' => 9999999999, 'totallength' => 0,
			'maxwidth'  => 0, 'minwidth' => 9999999999,  'totalwidth'  => 0,
			'maxheight' => 0, 'minheight' => 9999999999, 'totalheight' => 0,
			'maxpackaging' => 0, 'minpackaging' => 9999999999, 'totalpackaging' => 0,
		);
		$length_dimension = $method->length_unit;
		foreach ($products as $product) {
	
			$l = ShopFunctions::convertDimensionUnit ($product->product_length, $product->product_lwh_uom, $length_dimension);
			$w = ShopFunctions::convertDimensionUnit ($product->product_width, $product->product_lwh_uom, $length_dimension);
			$h = ShopFunctions::convertDimensionUnit ($product->product_height, $product->product_lwh_uom, $length_dimension);

			$volume = $l * $w * $h;
			$dimensions['volume'] += $volume * $product->quantity;
			$dimensions['maxvolume'] = max ($dimensions['maxvolume'], $volume);
			$dimensions['minvolume'] = min ($dimensions['minvolume'], $volume);
				
			$dimensions['totallength'] += $l * $product->quantity;
			$dimensions['maxlength'] = max ($dimensions['maxlength'], $l);
			$dimensions['minlength'] = min ($dimensions['minlength'], $l);
			$dimensions['totalwidth'] += $w * $product->quantity;
			$dimensions['maxwidth'] = max ($dimensions['maxwidth'], $w);
			$dimensions['minwidth'] = min ($dimensions['minwidth'], $w);
			$dimensions['totalheight'] += $h * $product->quantity;
			$dimensions['maxheight'] = max ($dimensions['maxheight'], $h);
			$dimensions['minheight'] = min ($dimensions['minheight'], $h);
			$dimensions['totalpackaging'] += $product->product_packaging * $product->quantity;
			$dimensions['maxpackaging'] = max ($dimensions['maxpackaging'], $product->product_packaging);
			$dimensions['minpackaging'] = min ($dimensions['minpackaging'], $product->product_packaging);
		}

		return $dimensions;
	}
	
	protected function getOrderWeights ($cart, $products, $method) {
		$weight_unit = $method->weight_unit;
		$dimensions=array(
			'weight' => 0,
			'maxweight' => 0, 'minweight' => 9999999999,
		);
		foreach ($products as $product) {
			$w = ShopFunctions::convertWeightUnit ($product->product_weight, $product->product_weight_uom, $weight_unit);
			$dimensions['maxweight'] = max ($dimensions['maxweight'], $w);
			$dimensions['minweight'] = min ($dimensions['minweight'], $w);
			$dimensions['weight'] += $w * $product->quantity;
		}
		return $dimensions;
	}
	
	protected function getOrderListProperties ($cart, $products, $method) {
		$categories = array();
		$vendors = array();
		$skus = array();
		$manufacturers = array();
		foreach ($products as $product) {
			$skus[] = $product->product_sku;
			$categories = array_merge ($categories, $product->categories);
			$vendors[] = $product->virtuemart_vendor_id;
			if (is_array($product->virtuemart_manufacturer_id)) {
				$manufacturers = array_merge($manufacturers, $product->virtuemart_manufacturer_id);
			} elseif ($product->virtuemart_manufacturer_id) {
				$manufacturers[] = $product->virtuemart_manufacturer_id;
			}
		}
		$skus = array_unique($skus);
		$vendors = array_unique($vendors);
		$categories = array_unique($categories);
		$manufacturers = array_unique($manufacturers);
		return array ('skus'=>$skus, 
			      'categories'=>$categories,
			      'vendors'=>$vendors,
			      'manufacturers'=>$manufacturers,
		);
	}
	
	protected function getOrderUser ($cart, $method) {
		$user = $cart->user;
		$uinfo = array(
			'shoppergroups' => $user->shopper_groups,
			'groups' => $user->JUser->groups,
			'userid' => $user->virtuemart_user_id,
			'username' => $user->JUser->name,
			'isguest' => $user->JUser->guest,
			'isvendor' => $user->user_is_vendor,
// 			'customernumber' => $user->customer_number,
		);

		return $uinfo;
	}
	
	protected function getOrderAddress ($cart, $method) {
		$address = (($cart->ST == 0 || $cart->STsameAsBT == 1) ? $cart->BT : $cart->ST);
		$zip = isset($address['zip'])?trim($address['zip']):'';
		$data = array('zip'=>$zip,
			'zip1'=>substr($zip,0,1),
			'zip2'=>substr($zip,0,2),
			'zip3'=>substr($zip,0,3),
			'zip4'=>substr($zip,0,4),
			'zip5'=>substr($zip,0,5),
			'zip6'=>substr($zip,0,6),
			'zipnumeric'  => preg_replace('/[^0-9]/', '', $zip),
			'zipalphanum' => preg_replace('/[^a-zA-Z0-9]/', '', $zip),
			'city'=>isset($address['city'])?trim($address['city']):'',
			'countryid' => 0, 'country' => '', 'country2' => '', 'country3' => '',
			'stateid'   => 0, 'state'   => '', 'state2'   => '', 'state3'   => '',
		);
		$data['company'] = isset($address['company'])?$address['company']:'';
		$data['title'] = isset($address['title'])?$address['title']:'';
		$data['first_name'] = isset($address['title'])?$address['title']:'';
		$data['middle_name'] = isset($address['middle_name'])?$address['middle_name']:'';
		$data['last_name'] = isset($address['last_name'])?$address['last_name']:'';
		$data['address1'] = isset($address['address_1'])?$address['address_1']:'';
		$data['address2'] = isset($address['address_2'])?$address['address_2']:'';
		$data['city'] = isset($address['city'])?$address['city']:'';
		$data['phone1'] = isset($address['phone_1'])?$address['phone_1']:'';
		$data['phone2'] = isset($address['phone_2'])?$address['phone_2']:'';
		$data['fax'] = isset($address['fax'])?$address['fax']:'';
		$data['email'] = isset($address['email'])?$address['email']:'';
		
		// Country and State variables:
		$countriesModel = VmModel::getModel('country');
		if (isset($address['virtuemart_country_id'])) {
			$data['countryid'] = $address['virtuemart_country_id'];
			// The following is a workaround to make sure the cache is invalidated
			// because if some other extension meanwhile called $countriesModel->getCountries,
			// the cache will be modified, but the model's id will not be changes, so the
			// getData call will return the wrong cache.
			$countriesModel->setId(0); 
			$countriesModel->setId($address['virtuemart_country_id']);
			$country = $countriesModel->getData($address['virtuemart_country_id']);
			if (!empty($country)) {
				$data['country'] = $country->country_name;
				$data['country2'] = $country->country_2_code;
				$data['country3'] = $country->country_3_code;
			}
		}
		
		$statesModel = VmModel::getModel('state');
		if (isset($address['virtuemart_state_id'])) {
			$data['stateid'] = $address['virtuemart_state_id'];
			// The following is a workaround to make sure the cache is invalidated
			// because if some other extension meanwhile called $countriesModel->getCountries,
			// the cache will be modified, but the model's id will not be changes, so the
			// getData call will return the wrong cache.
			$statesModel->setId(0); 
			$statesModel->setId($address['virtuemart_state_id']);
			$state = $statesModel->getData($address['virtuemart_state_id']);
			if (!empty($state)) {
				$data['state'] = $state->state_name;
				$data['state2'] = $state->state_2_code;
				$data['state3'] = $state->state_3_code;
			}
		}
		
		return $data;
	}
	
	protected function getOrderPrices ($cart, $products, /*$cart_prices, */$method) {
		$cart_prices = $cart->cartPrices;
		$data = array(
			'amount' => 0, 
			'amountwithtax' => 0, 
			'amountwithouttax' => 0, 
			'baseprice' => 0, 
			'basepricewithtax' => 0, 
			'discountedpricewithouttax' => 0, 
			'salesprice' => 0, 
			'taxamount' => 0, 
			'salespricewithdiscount' => 0, 
			'discountamount' => 0, 
			'pricewithouttax' => 0,
			
			// Bill-related prices: Will not be affected by scoping! Totals before and after cart-wide discounts.
// 			'billtotal' => $cart_prices['billTotal'],
// 			'billdiscountamount' => $cart_prices['billDiscountAmount'],
// 			'billtaxamount' => $cart_prices['billTaxAmount'],
// 			'billsubtotal' => $cart_prices['billSub'],
		);
// JFactory::getApplication()->enqueueMessage("<pre>getOrderPrices, cart=".print_r($cart,1)."</pre>", 'error');


		// Calculate the prices from the individual products!
		// Possible problems are discounts on the order total
		foreach ($products as $product) {
// 			$prices = isset($product->allPrices)?($product->allPrices[$product->selectedPrice]):($product->prices);
			$prices = $product->prices;
			$quant = $product->quantity;
			$data['amount']                    += $quant * $prices['salesPrice'];
			$data['amountwithtax']             += $quant * $prices['salesPrice'];
			$data['amountwithouttax']          += $quant * $prices['priceWithoutTax'];
			$data['baseprice']                 += $quant * $prices['basePrice'];
			$data['basepricewithtax']          += $quant * $prices['basePriceWithTax'];
			$data['discountedpricewithouttax'] += $quant * $prices['discountedPriceWithoutTax'];
			$data['salesprice']                += $quant * $prices['salesPrice'];
			$data['taxamount']                 += $quant * $prices['taxAmount'];
			$data['salespricewithdiscount']    += $quant * $prices['salesPriceWithDiscount'];
			$data['discountamount']            += $quant * $prices['discountAmount'];
			$data['pricewithouttax']           += $quant * $prices['priceWithoutTax'];
// JFactory::getApplication()->enqueueMessage("<pre>prices: ".print_r($prices,1)."</pre>", 'error');
// JFactory::getApplication()->enqueueMessage("<pre>product: ".print_r($product,1)."</pre>", 'error');
		}
		return $data;
	}

	/** Allow child classes to add additional variables for the rules or modify existing one
	 */
	protected function addPluginCartValues($cart, $products, $method, &$values) {
		// Finally, call the triger of vmshipmentrules plugins to let them add/modify variables
		JPluginHelper::importPlugin('vmshipmentrules');
		vDispatcher::trigger('onVmShippingRulesGetCartValues',array(&$values, $cart, $products, $method));
	}
	
	
	protected function getCategoryTreeRecursive($catmodel, $parent_category_id, &$categories) {
		if ($catmodel->hasChildren($parent_category_id)){
			// We need to call getCategories with vendorID=1, because the 
			// default "" will cause VM to check the current user for superuser 
			// access, which he usually does not have, and thus no child 
			// categories would be displayed.
			$childCats = $catmodel->getCategories(/*$onlyPublished*/false, 
				$parent_category_id, /*child id*/false, /*$keyword*/"", 
				/*VendorID*/1);
			foreach ($childCats as $key => $category) {
				$categories[] = $category->virtuemart_category_id;
				$this->getCategoryTreeRecursive($catmodel, $category->virtuemart_category_id, $categories);
			}
		}
	}

	/** Filter the given array of products and return only those that belong to the categories, manufacturers, 
	*  vendors or products given in the $filter_conditions. The $filter_conditions is an array of the form:
	*     array( 'products'=>array(....), 'categories'=>array(1,2,3,42), 'manufacturers'=>array(77,78,83), 'vendors'=>array(1,2))
	*  Notice that giving an empty array for any of the keys means "no restriction" and is exactly the same 
	*  as leaving out the entry altogether
	*/
	public function filterProducts($products, $filter_conditions) {
		$result = array();
		
		// For the subcategories scoping we need all subcategories of the conditions:
		$subcategories = array();
		if (isset($filter_conditions['subcategories']) && !empty($filter_conditions['subcategories'])) {
			$catmodel = VmModel::getModel('category');
			foreach ($filter_conditions['subcategories'] as $catid) {
				$subcategories[] = $catid;
				$categories = $this->getCategoryTreeRecursive($catmodel, $catid, $subcategories);
			}
			$subcategories = array_unique($subcategories);
		}
		

		foreach ($products as $p) {
			if (!empty($filter_conditions['products']) && !in_array($p->product_sku, $filter_conditions['products']))
				continue;
			if (!empty($filter_conditions['categories']) && count(array_intersect($filter_conditions['categories'], $p->categories))==0)
				continue;
			if (!empty($filter_conditions['subcategories']) && count(array_intersect($subcategories, $p->categories))==0)
				continue;
			if (!empty($filter_conditions['manufacturers']) && count(array_intersect($filter_conditions['manufacturers'], $p->virtuemart_manufacturer_id))==0)
				continue;
			if (!empty($filter_conditions['vendors']) && !in_array($p->virtuemart_vendor_id, $filter_conditions['vendors']))
				continue;
			$result[] = $p;
		}
		return $result;
	}
	
	
	/** Currency conversion function
	 */
	 
	protected function getCurrencyDisplay($curr) {
		if (!class_exists('ShopFunctions'))
			require(VMPATH_ADMIN . '/' . 'helpers' . '/' . 'shopfunctions.php');
		if (is_string($curr)) {
			$currID = ShopFunctions::getCurrencyIDByName($curr);
		} elseif (is_numeric($curr)) {
			$currID = $curr;
		} else {
			$this->printWarning(JText::sprintf('OTSHIPMENT_RULES_CURRENCY_CONVERSION_FAILED', $curr, $rule->rulestring));
			$currID = 0;
		}
		
		return CurrencyDisplay::getInstance($currID);
	}
	
	public function convert_from_currency($args, $rule) {
		$value = $args[0];
		$curr = $args[1];
		$currency = $this->getCurrencyDisplay($curr);
		if ($currency) {
			return $currency->convertCurrencyTo($curr, $value, TRUE);
		} else {
			$this->printWarning(JText::sprintf('OTSHIPMENT_RULES_CURRENCY_CONVERSION_FAILED', $curr, $rule->rulestring));
			return 0;
		}
	}
	
	public function convert_to_currency($args, $rule) {
		$value = $args[0];
		$curr = $args[1];
		$currency = $this->getCurrencyDisplay($curr);
		if ($currency) {
			return $currency->convertCurrencyTo($curr, $value, FALSE);
		} else {
			$this->printWarning(JText::sprintf('OTSHIPMENT_RULES_CURRENCY_CONVERSION_FAILED', $curr, $rule->rulestring));
			return 0;
		}
	}
}
