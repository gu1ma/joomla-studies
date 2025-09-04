<?php

/**
 *
 * Category model for the cart
 *
 * @package	VirtueMart
 * @subpackage Cart
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2023 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 11045 2024-08-14 20:04:08Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');


/**
 * Model class for the cart
 * Very important, use ALWAYS the getCart function, to get the cart from the session
 * @package	VirtueMart
 * @subpackage Cart
 * @author RolandD
 * @author Max Milbers
 */
class VirtueMartCart {

	var $products = array();
	var $_productAdded = false;
	var $_calculated = false;
	var $_inCheckOut = false;
	var $_inConfirm = false;
	var $_fromCart = false;
	var $_dataValidated = false;
	var $_blockConfirm = false;
	var $_blockConfirmedCheckout = false;
	var $_confirmDone = false;
	var $_redirect = false;
	var $_redirected = false;
	var $_redirect_disabled = false;
	var $_lastError = null; // Used to pass errmsg to the cart using addJS()

	//todo multivendor stuff must be set in the add function, first product determines ownership of cart, or a fixed vendor is used
	var $vendorId = 0;
	var $vendor = null;
	var $lastVisitedCategoryId = 0;
	var $lastAddedProduct = 0;
	var $virtuemart_shipmentmethod_id = 0;
	var $virtuemart_paymentmethod_id = 0;
	var $automaticSelectedShipment = false;
	var $automaticSelectedPayment  = false;
	var $BT = array();
	var $ST = array();
	var $BTaddress = array();
	var $STaddress = array();
	var $cartfields = array();

	/* Internal use only, 3rd party developer should use $cart->cartData['couponCode'] */
	var $couponCode = '';
	var $order_language = '';
	var $orderDetails = 0;
	var $totalProduct = 0;
	var $productsQuantity = array();
	var $lists = null;
	var $order_number=null; // added to solve emptying cart for payment notification
	var $order_pass=null;
	var $virtuemart_order_id = false;
	var $OrderIdOrderDone = false;
	var $customer_number=null;
	var $cartAdv = false;

	var $pricesCurrency = null;
	var $paymentCurrency = null;
	var $STsameAsBT = 1;
	var $selected_shipto = 0;

	var $_triesValidateCoupon = array();

	var $cartProductsData = array();
	var $cartData = array();
	var $cartPrices = array();
	var $layout ;
	var $layoutPath='';
	var $orderdoneHtml = false;
	var $virtuemart_cart_id = 0;
	var $customer_notified = false;
	/* @deprecated */
	var $pricesUnformatted = array();

	private static $_cart = null;
	static $_carts = null;

	var $tempCart = false;
	var $useXHTML = false;
	var $useSSL = 1;
	var $storeCartSession = true;
	var $productCartLoaded = array();
	var $loadedCart = false;
	var $user = null;
	var $_guest = true;

	public function __construct() {
		$this->useSSL = vmURI::useSSL();
		$this->useXHTML = false;
		$this->cartProductsData = array();
		$this->layout = self::getCartLayoutByVmConfig();
		$this->_confirmDone = false;

		if(empty($this->layout)){
			$this->layout = 'default';
		}
	}

	static public function getCartLayoutByVmConfig(){
		$v = VmConfig::get('cartlayout','default');
		if(empty($v)){
			$v = 'default';
		}
		return $v;
	}

	private static $lastVendorId = 1;

	public static $_session = null;
	/**
	 * Get the cart from the session
	 *
	 * @author Max Milbers
	 * @access public
	 * @param array $cart the cart to store in the session
	 */
	public static function &getCart($forceNew=false, $options = array(), $cartData=NULL, $vendorId = NULL) {

		$multixcart = VmConfig::get('multixcart',0);

		self::$_session = JFactory::getSession($options);
		if(empty($multixcart)){
			$vendorId = 1;
			//vmdebug('No Multicart vendorId = 1');
		} else {
			if($vendorId === NULL){

				$lastVendorId = self::$_session->get('vmcartlastVendorId', 0, 'vm');
				if($lastVendorId!=0){
					$vendorId = $lastVendorId;
					//vmdebug('getCart use lastVendorId',$lastVendorId);
				}
				if($multixcart=='byvendor' /*or self::$_cart->vendorId==1*/){

					$vId = vmAccess::isSuperVendor();
					if(!empty($vId) and $vId!=1){
						$vendorId = $vId;
					}

				}
				if($multixcart=='byselection' or $multixcart=='byproduct'){
					$tmpVendId = vRequest::getInt('virtuemart_vendor_id',false);
					if($tmpVendId!=false){
						$vendorId = $tmpVendId;
					}
				}

				if(empty($vendorId)) $vendorId = 1;

				//vmdebug('getCart Multicart vendorId '.$multixcart,$vendorId);
			}
		}


		if(empty(self::$_carts[$vendorId]) or $forceNew){

			self::$_cart = new VirtueMartCart;

			self::$_cart->vendorId	 					= $vendorId;
			if (empty($cartData)) {

				if($multixcart!='byproduct'){
					$sessionCart = self::$_session->get('vmcart', 0, 'vm');
				}
				else {
					$sessionCart = self::$_session->get('vmcarts.'.$vendorId, 0, 'vm');

				}

				if (!empty($sessionCart)) {
					$sessionCart = (object)vmJsApi::safe_json_decode( $sessionCart ,true);
				}
			} else {
				$sessionCart=$cartData;
			}

			$userModel = VmModel::getModel('user');
			self::$_cart->user = $userModel->getCurrentUser();

			$lang = vmLanguage::getLanguage();
			self::$_cart->order_language = $lang->getTag();

			self::$_cart->_guest = true;
			self::$_cart->loadedCart = false;
			$setInSession = false;
			if (!empty($sessionCart)) {

				if(isset($sessionCart->cartProductsData)){
					self::$_cart->cartProductsData 				= $sessionCart->cartProductsData;

					self::$_cart->lastVisitedCategoryId	 		= $sessionCart->lastVisitedCategoryId;
					self::$_cart->virtuemart_shipmentmethod_id	= $sessionCart->virtuemart_shipmentmethod_id;
					self::$_cart->virtuemart_paymentmethod_id 	= $sessionCart->virtuemart_paymentmethod_id;
					self::$_cart->automaticSelectedShipment 	= $sessionCart->automaticSelectedShipment;
					self::$_cart->automaticSelectedPayment 		= $sessionCart->automaticSelectedPayment;
					self::$_cart->BT 							= $sessionCart->BT;
					self::$_cart->ST 							= $sessionCart->ST;
					self::$_cart->cartfields					= $sessionCart->cartfields;

					self::$_cart->couponCode 					= $sessionCart->couponCode;
					self::$_cart->_triesValidateCoupon			= $sessionCart->_triesValidateCoupon;
					self::$_cart->order_number					= $sessionCart->order_number;
					self::$_cart->pricesCurrency				= $sessionCart->pricesCurrency;
					self::$_cart->paymentCurrency				= $sessionCart->paymentCurrency;

					self::$_cart->_guest						= $sessionCart->_guest;
					self::$_cart->_inCheckOut 					= $sessionCart->_inCheckOut;
					self::$_cart->_inConfirm					= $sessionCart->_inConfirm;
					self::$_cart->_redirected					= $sessionCart->_redirected;
					self::$_cart->_dataValidated				= $sessionCart->_dataValidated;
					//self::$_cart->_confirmDone					= $sessionCart->_confirmDone;
					self::$_cart->STsameAsBT					= $sessionCart->STsameAsBT;
					self::$_cart->selected_shipto 				= $sessionCart->selected_shipto;
					self::$_cart->_fromCart						= $sessionCart->_fromCart;
					self::$_cart->layout						= $sessionCart->layout;
					self::$_cart->layoutPath					= $sessionCart->layoutPath;
					self::$_cart->virtuemart_cart_id			= $sessionCart->virtuemart_cart_id;
					self::$_cart->OrderIdOrderDone              = isset($sessionCart->OrderIdOrderDone)? $sessionCart->OrderIdOrderDone:false;
					self::$_cart->orderdoneHtml					= $sessionCart->orderdoneHtml;
					self::$_cart->virtuemart_order_id			= $sessionCart->virtuemart_order_id;
					self::$_cart->byDefaultBT					= isset($sessionCart->byDefaultBT)? $sessionCart->byDefaultBT: array();
					self::$_cart->byDefaultST					= isset($sessionCart->byDefaultST)? $sessionCart->byDefaultST: array();
					self::$_cart->lastAddedProduct				= isset($sessionCart->lastAddedProduct)? $sessionCart->lastAddedProduct: 0;
				}
				self::$_cart->_guest						= isset($sessionCart->_guest)? $sessionCart->_guest: true;
				self::$_cart->productCartLoaded				= isset($sessionCart->productCartLoaded)? $sessionCart->productCartLoaded: array();
				self::$_cart->loadedCart					= isset($sessionCart->loadedCart)? $sessionCart->loadedCart: false;

			} else {
				$setInSession = true;
			}

			if((int)self::$_cart->_guest!=(int)self::$_cart->user->JUser->guest){
				self::$_cart->loadedCart = false;
			}
			if(VmConfig::isSite() and empty(self::$_cart->loadedCart) ){
				self::$_cart->loadCart(self::$_cart);
			}

			//self::$_cart->selected_shipto = vRequest::getVar('shipto', self::$_cart->selected_shipto);
			if(empty(self::$_cart->selected_shipto)){
				//self::$_cart->STsameAsBT = 1;
			}

			self::$_cart->setupAddressFieldsForCart(true, true);

			if (empty(self::$_cart->virtuemart_shipmentmethod_id) && !empty(self::$_cart->user->virtuemart_shipmentmethod_id)) {
				self::$_cart->virtuemart_shipmentmethod_id = self::$_cart->user->virtuemart_shipmentmethod_id;
			}

			if (empty(self::$_cart->virtuemart_paymentmethod_id) && !empty(self::$_cart->user->virtuemart_paymentmethod_id)) {
				self::$_cart->virtuemart_paymentmethod_id = self::$_cart->user->virtuemart_paymentmethod_id;
			}

			if((!empty(self::$_cart->user->tos) || !empty(self::$_cart->BT['tos'])) && !VmConfig::get('agree_to_tos_onorder',0) ){
				self::$_cart->BT['tos'] = 1;
			}

			if(!empty(self::$_cart->user->customer_number)){
				self::$_cart->customer_number = self::$_cart->user->customer_number;
			}

			if(empty(self::$_cart->customer_number) or strpos(self::$_cart->customer_number,'nonreg_')!==FALSE){
				$firstName = empty(self::$_cart->BT['first_name'])? '':self::$_cart->BT['first_name'];
				$lastName = empty(self::$_cart->BT['last_name'])? '':self::$_cart->BT['last_name'];
				$email = empty(self::$_cart->BT['email'])? '':self::$_cart->BT['email'];
				$qdate = date("Ymd_His_");
				self::$_cart->customer_number = 'nonreg_'.shopFunctionsF::vmSubstr($firstName,0,2).shopFunctionsF::vmSubstr($lastName,0,2).shopFunctionsF::vmSubstr($email,0,2).$qdate;
			}

			//We need to check for the amount of products. A cart in Multix mode using the first product
			// to determine the vendorId is a valid if there is no product in the cart
			$cp = count(self::$_cart->cartProductsData);
			if( $cp >0 and empty(self::$_cart->vendorId)){
				self::$_cart->vendorId = 1;
			}

			self::$_session->set('vmcartlastVendorId', $vendorId, 'vm');
			self::$lastVendorId = $vendorId;

			if($setInSession){
				self::$_cart->setCartIntoSession();
			}
			self::$_carts[self::$_cart->vendorId] = &self::$_cart;
		} else {

			self::$_session->set('vmcartlastVendorId', $vendorId, 'vm');
			self::$_cart = self::$_carts[$vendorId];
		}

		return self::$_cart;
	}

	function setupAddressFieldsForCart($update = false, $onlyDefaults = false){

		if($this->BT==0) $this->BT = array();
		if($this->ST==0) $this->ST = array();
		if($this->cartfields==0) $this->cartfields = array();

		//vmdebug('setupAddressFieldsForCart the begin ',$this->STsameAsBT,$this->BT,$this->ST,$this->cartfields);
		$bt = $this->BT;
		$st = $this->ST;

		$default['BT']=true;
		$default['ST']=true;

		$btloaded = false;
		$stloaded = false;
		//If the user is logged in and exists, we check if he has already addresses stored
		if(!empty($this->user->virtuemart_user_id)){
			//quorvia load stored shopper addresses regardless what is in cart
			if(VmConfig::get( 'alwaysLoadStoredShopperAddress', 0 )) {
				foreach( $this->user->userInfo as $address ) {
					if($address->address_type == 'BT') {
						$this->saveAddressInCart( (array)$address, $address->address_type, FALSE );
					} else {
						if(!empty( $this->selected_shipto ) and $address->virtuemart_userinfo_id == $this->selected_shipto) {
							$this->saveAddressInCart( (array)$address, $address->address_type, FALSE, '' );
						}
					}
				}
			}
			$loadBT = true;
			$countBT = count($this->BT) - count($this->cartfields);
			//vmdebug('my setupAddressFieldsForCart $countBT $this->byDefaultBT',(int)$countBT, (int) count($this->byDefaultBT));
			//Check if the address is already loaded
			if( $countBT > count($this->byDefaultBT) ){
				$loadBT = false;
			}

			$loadST = true;
			$selected_shipto = vRequest::getVar('shipto', $this->selected_shipto);
			if( $selected_shipto==$this->selected_shipto and count($this->ST) > count($this->byDefaultST) ){
				$loadST = false;
			}
			$this->selected_shipto = $selected_shipto;

			//vmdebug('my $loadBT $loadST',(int)$loadBT, (int) $loadST);
			foreach ($this->user->userInfo as $address) {
				if ($loadBT and $address->address_type == 'BT') {
					$bt = $address->loadFieldValues();
					$btloaded = true;
				} else if($loadST and !empty($this->selected_shipto) and $address->virtuemart_userinfo_id==$this->selected_shipto){
					//$this->saveAddressInCart((array) $address, $address->address_type,false,'');
					$st = $address->loadFieldValues();
					$stloaded = true;
					$this->STsameAsBT = 0;
					$this->ST = array();
					//	vmdebug('setupAddressFieldsForCart found stored ST ',$st);
				}
			}

			if(empty($this->selected_shipto)){
				$this->STsameAsBT = 1;
				$this->ST = array();
			} else {
				$this->STsameAsBT = 0;
			}
		}

		$userFieldsModel = VmModel::getModel('Userfields');

		$types = array('BT','ST');
		foreach($types as $type){

			if($type=='ST'){
				$preFix = 'shipto_';
				if($stloaded){
					$defaults = $this->byDefaultST;
					if(!empty($defaults)){
						foreach($defaults as $name=>$v){
							if(isset($this->{$type}[$name])){
								$this->{$type}[$name] = '';
							}
						}
					}
					$data = $this->ST = self::mergeArraysOverrideEmpty($st, $this->ST);
				} else {
					$data = $this->ST;
				}

			} else {
				$preFix = '';
				if($btloaded){
					$defaults = $this->byDefaultBT;
					if(!empty($defaults)){
						foreach($defaults as $name=>$v){
							if(isset($this->{$type}[$name])){
								$this->{$type}[$name] = '';
							}
						}
					}
					$data = $this->BT = self::mergeArraysOverrideEmpty($bt, $this->BT);
				} else {
					$data = $this->BT;
				}
			}

			$addresstype = $type.'address'; //for example BTaddress
			if($update or empty($this->{$addresstype})){

				$userFields = $userFieldsModel->getUserFieldsFor('cart',$type);

				$this->{$addresstype} = $userFieldsModel->getUserFieldsFilled(
					$userFields
					,$data
					,$preFix
					,$onlyDefaults
				);

				if(!empty($this->{$addresstype}['byDefault'])){
					if($type=='BT'){
						$this->byDefaultBT = $this->{$addresstype}['byDefault'];
					} else {
						$this->byDefaultST = $this->{$addresstype}['byDefault'];
					}
				}

				if(!empty($this->{$addresstype}['fields'])){
					$this->bindUserfieldToCart($type, $this->{$addresstype}['fields']);
				} else {
					vmdebug('cart helper found no userfields to bind');
				}
			}
		}

	}

	public function unsetDefaults($type, $ar){

		if($type == 'BT'){
			$defaults = $this->byDefaultBT;
		} else {
			$defaults = $this->byDefaultST;
		}
		vmdebug('unsetDefaults $defaults',$defaults);
		if(!empty($defaults)){
			foreach($defaults as $name=>$v){
				if(isset($ar[$name])){
					unset($ar[$name]);
				}
			}
		}
		if($type == 'BT'){
			$this->byDefaultBT = array();
		} else {
			$this->byDefaultST = array();
		}
		vmdebug('Defaults UNSET '.$type,$ar);
		return $ar;

	}

	static public function mergeArraysOverrideEmpty($ar1, $ar2){
		//vmdebug('mergeArraysOverrideEmpty Going to merge,',$ar1, $ar2);
		$arr = array_merge($ar1, $ar2);
		foreach($arr as $n =>$v){
			if(empty($v) and !empty($ar1[$n])) $arr[$n] = $ar1[$n];
		}
		//vmdebug('mergeArraysOverrideEmpty result',$arr);
		return $arr;
	}

	public $byDefaultBT = array();
	public $byDefaultST = array();

	private function bindUserfieldToCart($type, $userFields){

		//vmdebug('bindUserfieldToCart type before bind '.$type,$this->{$type});
		foreach ($userFields as $name=>$fld) {

			if(!empty($fld['name'])){
				if(empty($this->{$type}[$name]) or !empty($userFields['byDefault'][$name])){

					if($name=='virtuemart_country_id' and !empty( $fld['virtuemart_country_id'])){
						$this->{$type}[$name] = $fld['virtuemart_country_id'];
					} else if ($name=='virtuemart_state_id' and !empty( $fld['virtuemart_state_id'])){
						$this->{$type}[$name] = $fld['virtuemart_state_id'];
					} else if(!empty( $fld['value'])) {
						$this->{$type}[$name] = $fld['value'];
					}
				}
			}
		}
		//vmdebug('bindUserfieldToCart type AFTER bind '.$type,$this->{$type});
	}

	/**
	 * @deprecated use setupAddressFieldsForCart instead
	 */
	function loadSetRenderBTSTAddress(){

		//If the user is logged in and exists, we check if he has already addresses stored
		if(!empty($this->user->virtuemart_user_id)){

			foreach ($this->user->userInfo as $address) {
				if ($address->address_type == 'BT') {
					$this->saveAddressInCart((array) $address, $address->address_type,false);
				} else {
					if(!empty($this->selected_shipto) and $address->virtuemart_userinfo_id==$this->selected_shipto){
						$this->saveAddressInCart((array) $address, $address->address_type,false,'');
					}
				}
			}
			if(empty($this->selected_shipto)){
				$this->STsameAsBT = 1;
				$this->ST = 0;
			}
		}
	}

	/**
	 * @deprecated use setupAddressFieldsForCart instead
	 */
	//function prepareAddressDataInCart($type='BT',$new = false,$virtuemart_user_id = null){
	function prepareAddressFieldsInCart($update=false){

		$userFieldsModel = VmModel::getModel('Userfields');

		$types = array('BT','ST');
		foreach($types as $type){
			if($type=='ST'){
				$preFix = 'shipto_';
			} else {
				$preFix = '';
			}

			$addresstype = $type.'address'; //for example BTaddress
			if($update or empty($this->{$addresstype})){

				$userFields = $userFieldsModel->getUserFieldsFor('cart',$type);
				$this->{$addresstype} = $userFieldsModel->getUserFieldsFilled(
					$userFields
					,$this->{$type}
					,$preFix
				);

				$this->bindUserfieldToCart($type, $this->{$addresstype}['fields']);

				vmdebug('prepareAddressFieldsInCart '.$addresstype,$this->{$type});
			}
		}

	}


	var $storeToDB = false;

	/**
	 * Loads cart from the cart table
	 * @author Max Milbers
	 */
	public function loadCart(&$existingSession){

		/*stAn - this is a global disabler - either per plugins setting this temporary during the process or generally - disables both cookie and DB cart load */
		$cartLoad = VmConfig::get('enableCartLoad', true);
		if (empty($cartLoad)) {
			return;
		}

		/*stAn - set this to true to not merge cart if current cart is not empty - useful if your login page is after your cart content page*/
		$enableCartMerging = VmConfig::get('enableCartMerging', true);
		if (empty($enableCartMerging) && (!empty($existingSession->cartProductsData))) {
			return;
		}

		$currentUser = JFactory::getUser();

		if(!empty($existingSession->loadedCart)){
			return false;
		} else {
			if($existingSession){
				//vmdebug('Executing loadCart set $existingSession->loadedCart = true');
				$this->loadedCart = true;
				$this->storeToDB = true;
			}
		}
		//vmdebug('Executing loadCart ');

		if( $currentUser->guest ){
			if(empty(VmConfig::get('cartCookieExpire',0))){
				return false;
			} else {
				$cookie  = JFactory::getApplication()->input->cookie;
				$cartData = $cookie->get('myCart', null, $filter = 'string');

				if(empty($cartData)){
					vmdebug('loadCart, there is no CookieData ', $_COOKIE);
					return;
				} else {
					vmdebug('loadCart, CookieData loaded ');
					$cartData = vmJsApi::safe_json_decode(urldecode($cartData),true);
				}
			}


		} else {
			$model = new VmModel();
			$carts = $model->getTable('carts');
			if(!empty($existingSession->virtuemart_cart_id)){
				$carts->load($existingSession->virtuemart_cart_id,'virtuemart_cart_id');
			} else {
				$carts->load($currentUser->id,0,' ORDER BY `modified_on` DESC');
			}
			$cartData = $carts->loadFieldValues();	//returned as array

			//We dont need this cookie, if the user logged in.
			if(!empty(VmConfig::get('cartCookieExpire',0))){
				$this->setCookie('', time() - 10);
			}

		}

		if(isset($cartData['_inCheckOut'])) unset($cartData['_inCheckOut']);
		if(isset($cartData['_dataValidated'])) unset($cartData['_dataValidated']);
		if(isset($cartData['_confirmDone'])) unset($cartData['_confirmDone']);
		if(isset($cartData['_fromCart'])) unset($cartData['_fromCart']);
		if(isset($cartData['virtuemart_cart_id'])) $this->virtuemart_cart_id = $cartData['virtuemart_cart_id'];
		if($cartData and !empty($cartData['cartData'])){

			$cartData['cartData'] = (object)vmJsApi::safe_json_decode($cartData['cartData'],true);
			$add = false;
			if($existingSession and !empty($cartData['cartData'])){
				if(!empty($cartData['cartData']->cartProductsData) and is_array($cartData['cartData']->cartProductsData)){
					foreach($cartData['cartData']->cartProductsData as $k => $product){
						foreach($existingSession->cartProductsData as $kses => $productses){
							//vmdebug('my stored products ',$productses);
							if($product['virtuemart_product_id']==$productses['virtuemart_product_id']){

								//Okey, the id is already the same, so lets check the customProductData
								$diff = !$this->deepCompare($product['customProductData'],$productses['customProductData']);

								if(!$diff){
									unset($cartData['cartData']->cartProductsData[$k]);
									//break;
								} else {
									$this->storeToDB = true;
									vmdebug('product variant is different, I add to cart');
								}
								//unset($cartData['cartData']->cartProductsData[$k]);
							} else {
								$this->storeToDB = true;
							}

							//vmdebug('my stored products ',$product);
						}
						$this->productCartLoaded[$k] = true;
					}

				}

				foreach($cartData['cartData'] as $key=>$value){

					if ($key == 'byDefaultBT' or $key =='byDefaultST'){
						//continue;
					}
					else if($key == '_triesValidateCoupon'){	//We need this special handling for fallback reasons, we could also just delete stored carts when updating
						foreach($value as $k=>$v){
							if($k<100) continue;
							if(!in_array($v,$existingSession->_triesValidateCoupon)){
								$existingSession->_triesValidateCoupon[$k] = $v;
							}
						}
					} else {
						if(is_array($value)){
							if($key=='cartProductsData' and count($value)>0 and VmConfig::get('showCartLoadedMsg',1)){
								VmInfo('COM_VM_LOADED_STORED_CART');
							} else if ($key=='BT' or $key=='ST'){
								$existingSession->{$key} = $this->unsetDefaults($key, $existingSession->{$key});
							}
							$existingSession->{$key} = array_merge( $value,(array)$existingSession->{$key});
						} else if(empty($existingSession->{$key})){
							$existingSession->{$key} = $cartData['cartData']->{$key};
						}
					}
				}
				if(count($cartData['cartData']->cartProductsData)!=$existingSession->cartProductsData){
					$this->storeToDB = true;
				}
			}
		}

	}

	public function storeCart($cartDataToStore = false){

		if($this->tempCart) return;
		//quorvia dont store non completed carts for logged in users
		$Cartsdontsave = VmConfig::get('CartsDontSave', 0 );
		if ($Cartsdontsave){
			return;
		}

		//quorvia dont store non completed cart data for shoppergroup
		$CartsdontsaveShopperGroup = VmConfig::get('CartsDontSaveByshoppergroup', 0 );
		if ($CartsdontsaveShopperGroup > 0 ){
			if(!empty($this->user->shopper_groups) AND in_array($CartsdontsaveShopperGroup, $this->user->shopper_groups)) {
				return;
			}
		}

		$adminID = vmAccess::getBgManagerId();
		$currentUser = JFactory::getUser();
		//Better to replace the cookie technic against JWT
		$cartCookieExpire = VmConfig::get('cartCookieExpire',0);

		if( (!$currentUser->guest or !empty($cartCookieExpire) ) && (!$adminID || $adminID == $currentUser->id)){

			if(!$cartDataToStore){
				$data = $this->getCartDataToStore();
				unset($data->productCartLoaded);
				unset($data->OrderIdOrderDone);
				unset($data->orderdoneHtml);
				if(!$currentUser->guest){
					unset($data->BT);
					unset($data->ST);
				}

				//quorvia dont store cartfields e.g. TOS, Customer_note
				$CartsdontsaveCartfields = VmConfig::get('CartsDontSaveCartFields', 0 );
				if($CartsdontsaveCartfields) {
//				this could just be more focussed if necessary
//					$data->cartfields['customer_note'] = '';
//					unset($data->cartfields['tos']);
					$data->cartfields = '';
				}
				$cartDataToStore = json_encode($data);
			}

			$cObj = new StdClass();
			if(!empty($this->virtuemart_cart_id)) $cObj->virtuemart_cart_id = (int) $this->virtuemart_cart_id;
			$cObj->virtuemart_user_id = (int) $currentUser->id;
			$cObj->virtuemart_vendor_id = (int) $this->vendorId;
			$cObj->cartData = $cartDataToStore;



			if(!empty($cartCookieExpire) and $currentUser->guest and !headers_sent()){
				$this->setCookie($cObj);
			} else if (!$currentUser->guest){
				$model = new VmModel();
				$carts = $model->getTable('carts');
				vmdebug('<span style="background-color: red;">storeCart by Table</span>',$cObj);
				$carts->bindChecknStore($cObj);

				if(!empty($cObj->virtuemart_cart_id)){
					$this->virtuemart_cart_id = $cObj->virtuemart_cart_id;
				}
				if(!empty($cartCookieExpire)){
					$this->setCookie('', time() - 10);
				}
			}

		}

	}

	public function setCookie($cObj, $timeout = false){
		
		if (headers_sent()) return; 
		
		if (empty($cObj)) {
			$contEncode = null;
			$strlenCE = 0;
		}
		else {
			if (is_object($cObj)) {
				$cObj->modified_on = date('c'); 
			}
			$contEncode = json_encode($cObj);
			$strlenCE = strlen($contEncode);
		}

		if( $strlenCE == 0 or (50 < $strlenCE and $strlenCE < 3600)) {
			$ver = (float)phpversion();
			if($timeout===false) $timeout = time() + 86400 * VmConfig::get('cartCookieExpire',0);
			$domain = JUri::getInstance()->getHost();
			$path = JUri::root(true);
			if ($ver > 7.2) {
				$arr_cookie_options = array (
					'expires' => $timeout,
					'secure' => true,     // or false //*/
					'httponly' => true,    // or false
					'samesite' => 'Strict', // None || Lax  || Strict  //*/
					'domain'    => $domain,
					'path'=> $path
				);
				@setcookie('myCart', $contEncode, $arr_cookie_options);
				//vmdebug('<span style="background-color: red;">storeCart by Cookie</span>',time(),$arr_cookie_options);
			} else {
				@setcookie('myCart', $contEncode, $timeout, $path, $domain, 1, 1);
				//vmdebug('<span style="background-color: red;">storeCart by Cookie</span>');
			}

		}

		if (empty($contEncode)) {
			$cookie  = JFactory::getApplication()->input->cookie;
			if(method_exists($cookie, 'clear')){
				$cookie->clear('myCart');
			}
		}

	}

	public function deleteCart(){

		$model = new VmModel();
		$carts = $model->getTable('carts');

		if(!empty($this->virtuemart_cart_id)){
			$carts->delete($this->virtuemart_cart_id,'virtuemart_cart_id');
		} else {
			$currentUser = JFactory::getUser();
			if(!empty($currentUser->id)) {
				$carts->delete($currentUser->id);
			}
		}
		if(!empty(VmConfig::get('cartCookieExpire',0))){
			$this->setCookie('', time() - 10);
		}

	}



	/**
	 * Set the cart in the session
	 *
	 * @access public
	 * @param array $cart the cart to store in the session
	 */
	public function setCartIntoSession($storeDb = false, $forceWrite = false) {

		if($this->tempCart) return;

		//if($this->storeCartSession or $forceWrite){
			if(!isset(self::$_session)) self::$_session = JFactory::getSession();
			$sessionCart = $this->getCartDataToStore();
			$sessionCart = json_encode($sessionCart);
			$multixcart = VmConfig::get('multixcart',0);
			//vmTrace('setCartIntoSession set in session');
			if($multixcart!='byproduct'){
				self::$_session->set('vmcart', $sessionCart,'vm');
			} else {
				self::$_session->set('vmcart', $sessionCart,'vm');
				self::$_session->set('vmcarts.'.$this->vendorId, $sessionCart,'vm');
			}
		//}

		if($this->storeCartSession and $storeDb){
			$this->storeCart();
		}

		if($forceWrite){
			if (!headers_sent()) {
				vmdebug('setCartIntoSession restart session');
				session_write_close();
				session_start();

				//This just creates unneeded extra sql requests
				//self::$_session->close();
				//self::$_session->start();
			}
		}

	}

	public function getCartDataToStore(){
		$sessionCart = new stdClass();

		$sessionCart->cartProductsData = $this->cartProductsData;
		$sessionCart->vendorId	 					= $this->vendorId;
		$sessionCart->lastVisitedCategoryId	 		= $this->lastVisitedCategoryId;
		$sessionCart->virtuemart_shipmentmethod_id	= $this->virtuemart_shipmentmethod_id;
		$sessionCart->virtuemart_paymentmethod_id 	= $this->virtuemart_paymentmethod_id;
		$sessionCart->automaticSelectedShipment 	= $this->automaticSelectedShipment;
		$sessionCart->automaticSelectedPayment 		= $this->automaticSelectedPayment;
		$sessionCart->order_number 		            = $this->order_number;

		$sessionCart->BT 							= $this->BT;
		$sessionCart->ST 							= $this->ST;
		$sessionCart->cartfields					= $this->cartfields;

		$sessionCart->couponCode 					= $this->couponCode;
		$sessionCart->_triesValidateCoupon			= $this->_triesValidateCoupon;
		$sessionCart->order_language 				= $this->order_language;

		$sessionCart->pricesCurrency				= $this->pricesCurrency;
		$sessionCart->paymentCurrency				= $this->paymentCurrency;

		//private variables
		//We nee to store this, so that we now if a user logged in before
		$sessionCart->_guest						= JFactory::getUser()->guest;
		$sessionCart->_inCheckOut 					= $this->_inCheckOut;
		$sessionCart->_inConfirm					= $this->_inConfirm;
		$sessionCart->_redirected 					= $this->_redirected;
		$sessionCart->_dataValidated				= $this->_dataValidated;
		$sessionCart->_confirmDone					= $this->_confirmDone;
		$sessionCart->STsameAsBT					= $this->STsameAsBT;
		$sessionCart->selected_shipto 				= $this->selected_shipto;
		$sessionCart->_fromCart						= $this->_fromCart;
		$sessionCart->layout						= $this->layout;
		$sessionCart->layoutPath					= $this->layoutPath;
		$sessionCart->virtuemart_cart_id			= $this->virtuemart_cart_id;
		$sessionCart->OrderIdOrderDone              = $this->OrderIdOrderDone;
		$sessionCart->orderdoneHtml					= $this->orderdoneHtml;
		$sessionCart->virtuemart_order_id			= $this->virtuemart_order_id;
		$sessionCart->byDefaultBT					= $this->byDefaultBT;
		$sessionCart->byDefaultST					= $this->byDefaultST;
		$sessionCart->productCartLoaded				= $this->productCartLoaded;
		$sessionCart->loadedCart                    = $this->loadedCart;
		$sessionCart->lastAddedProduct              = $this->lastAddedProduct;
		return $sessionCart;
	}



	/**
	 * Remove the cart from the session
	 *
	 * @author Max Milbers
	 * @access public
	 */
	public function removeCartFromSession() {

		$carts = self::$_session->get('vmcarts', 0,'vm');
		if($carts != 0){
			unset($carts->{$this->vendorId});
			self::$_session->set('vmcarts', $carts, 'vm');
		}

		self::$_session->set('vmcart', 0, 'vm');
		if(!empty(VmConfig::get('cartCookieExpire',0))){
			$this->setCookie('', time() - 10);
		}


	}

	public function setDataValidation($valid=false) {
		$this->_dataValidated = $valid;
	}

	public function getDataValidated() {
		return $this->_dataValidated;
	}

	public function getInCheckOut() {
		return $this->_inCheckOut;
	}

	public function setOutOfCheckout(){
		$this->_inCheckOut = false;
		$this->_dataValidated = false;
		$this->_blockConfirm = true;
		$this->_redirected = true;
		$this->_redirect = false;
		$this->storeCartSession = true;
		$this->setCartIntoSession(false,true);
	}

	public function blockConfirm(){
		$this->_blockConfirm = true;
	}

	/**
	 * For one page checkouts, disable with this the redirects
	 * @param bool $bool
	 */
	public function setRedirectDisabled($bool = TRUE){
		$this->_redirect_disabled = $bool;
	}

	/**
	 * Add a product to the cart
	 *
	 * @author Max Milbers
	 * @access public
	 */
	public function add($virtuemart_product_ids=null, $post = null) {

		$updateSession = true;
		if($post===null) $post = vRequest::getRequest();

		if(empty($virtuemart_product_ids)){
			$virtuemart_product_ids = vRequest::getInt('virtuemart_product_id'); //is sanitized
		}

		if (empty($virtuemart_product_ids)) {
			vmWarn('COM_VIRTUEMART_CART_ERROR_NO_PRODUCT_IDS');
			return false;
		} else {
			if(is_array($virtuemart_product_ids)){
				//2024 For legacy layouts with the doubled product ids in the layout 
				if(!empty($post['quantity']) and is_array($post['quantity']) and count($post['quantity'])!=count($virtuemart_product_ids)){
					$virtuemart_product_ids = array_unique($virtuemart_product_ids);
				}

			} else {
				$virtuemart_product_ids = array($virtuemart_product_ids);
			}
		}

		$products = array();

		$customFieldsModel = VmModel::getModel('customfields');

		vDispatcher::importVMPlugins('vmcustom');

		//We may have to create a new cart for multicart, so we add them now
		$multixcart = VmConfig::get('multixcart',0);
		if($multixcart=='byproduct' ){
			foreach ($virtuemart_product_ids as $p_key => $virtuemart_product_id) {
				if(empty($virtuemart_product_id)){
					vmWarn('Product could not be added with virtuemart_product_id = 0');
					unset($virtuemart_product_ids[$virtuemart_product_id]);
					continue;
				} /*else {
                    $productData['virtuemart_product_id'] = (int)$virtuemart_product_id;
                }*/

				if(!empty( $post['quantity'][$p_key])){
					$productData['quantity'] = (int) $post['quantity'][$p_key];
				} else if (count($virtuemart_product_ids) == 1) {
					$productData['quantity'] = vRequest::getInt('quantity',1);
					if(is_array($productData['quantity'])){
						$productData['quantity'] = reset($productData['quantity']);
					}
				} else {
					continue;
				}

				$product = VirtueMartCart::getProduct($virtuemart_product_id, $productData['quantity']);
				/*$productTemp = $productModel->getProduct($virtuemart_product_id, true, false,true,$productData['quantity']);
				$productTemp->modificatorSum = null;
				$product = clone($productTemp);*/

				if(!isset(self::$_carts[$product->virtuemart_vendor_id])){
					VirtuemartCart::getCart(false, array(), NULL,$product->virtuemart_vendor_id);
				}

			}
		}

		//Iterate through the prod_id's and perform an add to cart for each one
		foreach ($virtuemart_product_ids as $p_key => $virtuemart_product_id) {

			$product = false;
			$updateSession = true;
			$productData = array();

			if(empty($virtuemart_product_id)){
				vmWarn('Product could not be added with virtuemart_product_id = 0');
				return false;
			} else {
				$productData['virtuemart_product_id'] = (int)$virtuemart_product_id;
			}

			if(!empty( $post['quantity'][$p_key])){
				$productData['quantity'] = (int) $post['quantity'][$p_key];
			} else if (count($virtuemart_product_ids) == 1) {
				$productData['quantity'] = vRequest::getInt('quantity',1);
				if(is_array($productData['quantity'])){
					$productData['quantity'] = reset($productData['quantity']);
				}
			} else {
				continue;
			}

			/*if(!empty( $post['customProductData'][$p_key])) {
				$customProductData = $post['customProductData'][$p_key];
			} else */
			if(!empty( $post['customProductData'][$virtuemart_product_id])) {

				$customProductData = $post['customProductData'][$virtuemart_product_id];
			} else {
				$customProductData = array();
			}

			//Now we check if the delivered customProductData is correct and add missing
			$product = VirtueMartCart::getProduct($virtuemart_product_id, $productData['quantity']);
			if(!$product) {
				vmdebug('cart add, no product found with id '.$virtuemart_product_id.' and quantity',$productData['quantity'] );
				return false;
			}

			if($product->product_discontinued and !VmConfig::get('discontinuedPrdsBrowseable',1)){
				vmError('COM_VM_CART_TRIED_ADDING_PRODUCT_DISCONTINUED');
				continue;
			}

			$productData['virtuemart_vendor_id'] = $product->virtuemart_vendor_id;

			$product->customfields = $customFieldsModel->getCustomEmbeddedProductCustomFields($product->allIds,0,1);
			$customProductDataTmp=array();

			foreach($product->customfields as $customfield){

				// Some customfields may prevent the product being added to the cart
				$customFiltered = false;

				$addToCartReturnValues = vDispatcher::trigger('plgVmOnAddToCartFilter',array(&$product, &$customfield, &$customProductData, &$customFiltered));
				if(!empty($product->remove)){
					vmdebug('Remove product');
					break;
				}
				if($customFiltered){
					$customProductDataTmp=$customProductData;
				} else if(!$customFiltered && $customfield->is_input==1){
					if(isset($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id])){

						if(is_array($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id])){

							foreach($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] as $i=>$customData){

								//$value = vmFilter::hl( $customData,array('deny_attribute'=>'*'));
								//to strong
								/* $value = preg_replace('@<[\/\!]*?[^<>]*?>@si','',$value);//remove all html tags  */
								//lets use instead
								$value = JComponentHelper::filterText($customData);
								$value = (string)preg_replace('#on[a-z](.+?)\)#si','',$value);//replace start of script onclick() onload()...
								$value = trim(str_replace('"', ' ', $value),"'") ;
								$customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id][$i] = (string)preg_replace('#^\'#si','',$value);
							}
						}
						if(!isset($customProductDataTmp[$customfield->virtuemart_custom_id])) $customProductDataTmp[$customfield->virtuemart_custom_id] = array();
						$customProductDataTmp[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] = $customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id];
					}
					else if(isset($customProductData[$customfield->virtuemart_custom_id])) {
						$customProductDataTmp[$customfield->virtuemart_custom_id] = $customProductData[$customfield->virtuemart_custom_id];
						//vmdebug('my customp product data ',$customProductData[$customfield->virtuemart_custom_id]);
					}
				} else {
					if(!isset($customProductDataTmp[$customfield->virtuemart_custom_id])){
						$customProductDataTmp[$customfield->virtuemart_custom_id] = array();
					} else if(!is_array($customProductDataTmp[$customfield->virtuemart_custom_id])){
						$customProductDataTmp[$customfield->virtuemart_custom_id] = array($customProductDataTmp[$customfield->virtuemart_custom_id]);
					}
					if(!isset($customProductDataTmp[$customfield->virtuemart_custom_id])){
						$customProductDataTmp[$customfield->virtuemart_custom_id][(int)$customfield->virtuemart_customfield_id] = false;
					}
				}

			}

			$productData['customProductData'] = $customProductDataTmp;

			if(!empty($product->remove)){
				continue;
			}

			$found = false;
			$unsetA = array();
			foreach(self::$_carts as $vendorId => $cart) {

				if($multixcart=='byproduct' /*or $multixcart =='multicart'*/){
					if($cart->vendorId != $productData['virtuemart_vendor_id']) continue;
				}
				//$cart->cartProductsData
				//Now lets check if there is already a product stored with the same id, if yes, increase quantity and recalculate
				foreach( $cart->cartProductsData as $k => $cartProductData ) {
					$cartProductData = (array)$cartProductData;


					if(empty( $cartProductData['virtuemart_product_id'] )) {
						$unsetA[] = $k;
						$errorMsg = true;
					} else {
						if($cartProductData['virtuemart_product_id'] == $productData['virtuemart_product_id']) {

							//Okey, the id is already the same, so lets check the customProductData
							$diff = !$cart->deepCompare( $cartProductData['customProductData'], $productData['customProductData'] );

							if(!$diff) {
								vmdebug( 'my productCartLoaded ', $k, $cart->productCartLoaded );
								if(!empty( $cart->productCartLoaded[$k] )) {
									$newTotal = (float)$productData['quantity'];    //We assume the customer entered a correct new quantity.
									unset( $cart->productCartLoaded[$k] );
								} else {
									$newTotal = (float)$cartProductData['quantity'] + (float)$productData['quantity'];
								}


								if(!$product) $product = VirtueMartCart::getProduct( (int)$productData['virtuemart_product_id'], $cartProductData['quantity'] );
								if(empty( $product->virtuemart_product_id )) {
									vmWarn( 'COM_VIRTUEMART_PRODUCT_NOT_FOUND' );
									$unsetA[] = $k;

								} else {
									$cart->checkForQuantities( $product, $newTotal );
									vmdebug( "add to cart did checkForQuantities", $newTotal, $productData['quantity'] );
									$product->quantityAdded = $newTotal - $cartProductData['quantity'];
									$product->quantity = $newTotal;
									$cartProductData['quantity'] = $newTotal;
									$cart->cartProductsData[$k] = $cartProductData;
									$cart->_productAdded = true;
									$cart->lastAddedProduct = $product->virtuemart_product_id;
									vmdebug( 'add to cart did $product->quantityAdded  ', $cartProductData['quantity'] );
								}
								$found = TRUE;
								break;
							} else {
								vmdebug( 'product variant is different, I add to cart' );
								$cart->_productAdded = true;
								$cart->lastAddedProduct = $product->virtuemart_product_id;
							}
						}
					}

					//add products to remove to array
					if($cartProductData['quantity'] == 0) {
						$unsetA[] = $k;
						$this->_productAdded = true;
					}

				}

				if(!$found) {
					if(!$product) $product = VirtueMartCart::getProduct( (int)$productData['virtuemart_product_id'], $productData['quantity'] );
					if(!empty( $product->virtuemart_product_id )) {

						$cart->checkForQuantities( $product, $product->quantity );
						//vmdebug( 'my $productData $productData ', $productData );
						if(!empty( $product->quantity )) {
							$productData['quantity'] = $product->quantity;
							$cart->cartProductsData[] = $productData;
						} else {
							$errorMsg = true;
						}
					} else {
						$errorMsg = true;
					}

				}

				if($product) {
					$products[] = $product;
					$cart->_productAdded = true;
					$cart->lastAddedProduct = $product->virtuemart_product_id;
				}

				//Remove the products which have quantity=0
				foreach( $unsetA as $v ) {
					unset( $cart->cartProductsData[$v] );
				}

				vDispatcher::trigger('plgVmOnAddToCart',array(&$cart));

				//if ($updateSession== false) return false ;
				$cart->_dataValidated = false;
				// End Iteration through Prod id's
				$this->storeCartSession = true;
				$cart->setCartIntoSession(true, true);
				self::$_carts[$vendorId] = &$cart;
			}
		}


		return $products;
	}

	static public function deepCompare($a,$b) {
		if(is_object($a) && is_object($b)) {
			if(get_class($a)!=get_class($b))
				return false;
			foreach($a as $key => $val) {
				if(!self::deepCompare($val,$b->{$key}))
					return false;
			}
			return true;
		}
		else if(is_array($a) && is_array($b)) {
			while(!is_null(key($a)) && !is_null(key($b))) {
				if (key($a)!==key($b) || !self::deepCompare(current($a),current($b)))
					return false;
				next($a); next($b);
			}
			return is_null(key($a)) && is_null(key($b));
		}
		else
			return $a===$b;
	}

	/**
	 * Remove a product from the cart
	 *
	 * @author RolandD
	 * @param array $cart_id the cart IDs to remove from the cart
	 * @access public
	 */
	public function removeProductCart($prod_id=0) {
		// Check for cart IDs
		if (empty($prod_id))
			$prod_id = vRequest::getInt('cart_virtuemart_product_id');
		unset($this->products[$prod_id]);
		if(isset($this->cartProductsData[$prod_id])){
			// hook for plugin action "remove from cart"

			vDispatcher::importVMPlugins('vmcustom');
			$addToCartReturnValues = vDispatcher::trigger('plgVmOnRemoveFromCart',array($this,$prod_id));
			unset($this->cartProductsData[$prod_id]);
			if(!empty($cart->couponCode)){
				$cart->setCouponCode($cart->couponCode);
			}
			$this->setCartIntoSession(true);
			return true;
		} else {
			vmdebug('removeProductCart $prod_id '.$prod_id,$this->cartProductsData);
			return false;
		}
	}

	/**
	 * Update a product in the cart
	 *
	 * @author Max Milbers
	 * @param array $cart_id the cart IDs to remove from the cart
	 * @access public
	 */
	public function updateProductCart() {

		$quantities = vRequest::getInt('quantity');
		if(empty($quantities)) return false;
		$updated = false;

		foreach($quantities as $key=>$quantity){
			if (isset($this->cartProductsData[$key]) and !empty($quantity) and !isset($_POST['delete_'.$key])) {
				if($quantity!=$this->cartProductsData[$key]['quantity']){
					$this->cartProductsData[$key]['quantity'] = $quantity;
					$updated = true;
					$this->_productAdded = true;
					$this->storeCartSession = true;
					vmInfo('COM_VIRTUEMART_PRODUCT_UPDATED_SUCCESSFULLY');
				}

			} else if(empty($quantity) or isset($_POST['delete_'.$key])){

				unset($this->cartProductsData[$key]);
				$updated = true;
				$this->_productAdded = true;
				$this->storeCartSession = true;
				vmInfo('COM_VIRTUEMART_PRODUCT_REMOVED_SUCCESSFULLY');
			}
		}

		$this->setCartIntoSession($updated,true);
		return $updated;
	}

	/**
	 * Validate the coupon code. If ok,. set it in the cart
	 * @param string $coupon_code Coupon code as entered by the user
	 * TODO Change the coupon total/used in DB ?
	 * @access public
	 * @return string On error the message text, otherwise an empty string
	 */

	public function setCouponCode($coupon_code) {

		$db = JFactory::getDbo();

		$currentUser = JFactory::getUser();
		$allow_coupon = 0;
		$coupon_details = CouponHelper::getCouponDetails( $coupon_code );

		$userAttempts = 0;

		if($currentUser->id) {
			$query = $db->getQuery( true );

			$query->select( 'count(vo.virtuemart_order_id)' );
			$query->from( $db->quoteName( '#__virtuemart_orders', 'vo' ) );
			$query->join( 'INNER', $db->quoteName( '#__virtuemart_vmusers', 'vv' ).' ON ('.$db->quoteName( 'vo.virtuemart_user_id' ).' = '.$db->quoteName( 'vv.virtuemart_user_id' ).')' );
			$query->where( 'vo.virtuemart_user_id = '.$currentUser->id.'' );

			$db->setQuery( $query );

			$userAttempts = $db->loadResult();
		}

		if($userAttempts>0 and ( !empty($coupon_details->virtuemart_coupon_max_attempt_per_user) and $coupon_details->virtuemart_coupon_max_attempt_per_user>0 )) {
			if($userAttempts>=$coupon_details->virtuemart_coupon_max_attempt_per_user) {
				$this->clearCoupon();
				return 'Maximum coupon usage limit reached, please try different code.';
			}
		}

		if(!empty( $coupon_details->virtuemart_shoppergroup_ids ) || !empty( $coupon_details->virtuemart_shopper_ids )) {
			if(!empty( $coupon_details->virtuemart_shoppergroup_ids )) {
				$query = $db->getQuery( true );

				$query->select( $db->quoteName( array('virtuemart_user_id', 'virtuemart_shoppergroup_id') ) );
				$query->from( $db->quoteName( '#__virtuemart_vmuser_shoppergroups' ) );
				$query->where( 'virtuemart_user_id = '.$currentUser->id.' AND virtuemart_shoppergroup_id IN ('.$coupon_details->virtuemart_shoppergroup_ids.')' );

				$db->setQuery( $query );

				$isNotAllowed = $db->loadObjectList();

				$allow_coupon = ($isNotAllowed) ? 0 : 1;
			}

			if(!empty( $coupon_details->virtuemart_shopper_ids ) && $allow_coupon == 0) {
				$query = $db->getQuery( true );

				$query->select( $db->quoteName( array('virtuemart_user_id') ) );
				$query->from( $db->quoteName( '#__virtuemart_vmusers' ) );
				$query->where( 'virtuemart_user_id IN ('.$coupon_details->virtuemart_shopper_ids.')' );

				$db->setQuery( $query );

				$allowedUsersList = $db->loadColumn();

				$allow_coupon = (in_array( $currentUser->id, $allowedUsersList )) ? 1 : 0;
			}

			if(empty( $coupon_details->virtuemart_shoppergroup_ids ) && $allow_coupon == 0) {
				$virtuemart_shoppergroup_ids_arr = explode( ',', $coupon_details->virtuemart_shoppergroup_ids );

				if(($currentUser->id && in_array( 2, $virtuemart_shoppergroup_ids_arr )) || (!$currentUser->id && in_array( 1, $virtuemart_shoppergroup_ids_arr ))) {
					$allow_coupon = 1;
				}
			}

		} else if(empty( $coupon_details->virtuemart_shoppergroup_ids ) && empty( $coupon_details->virtuemart_shopper_ids )) {
			$allow_coupon = 1;
		}


		if(!empty( $coupon_details->virtuemart_product_ids ) || !empty( $coupon_details->virtuemart_category_ids )) {

			$allowed_product_ids = array();
			if(!empty($coupon_details->virtuemart_product_ids) and !is_array($coupon_details->virtuemart_product_ids)){
				$allowed_product_ids = explode( ',', $coupon_details->virtuemart_product_ids );
			}

			$allowed_productcat_ids = array();
			if(!empty($coupon_details->virtuemart_category_ids)){
				$allowed_productcat_ids = explode( ',', $coupon_details->virtuemart_category_ids );
			}
			$sizeof_cartitems_by_product = count( $this->productsQuantity );
			$allow_coupon_byproduct = 0;

			for( $i = 0; $i<$sizeof_cartitems_by_product; $i++ ) {
				if( (!empty($allowed_product_ids) and in_array( $this->cartPrices[$i]['virtuemart_product_id'], $allowed_product_ids )) || (!empty($allowed_productcat_ids) and array_intersect( $this->products[$i]->categories, $allowed_productcat_ids )) ) {
					$allow_coupon_byproduct = 1;
					break;
				}
			}

			$allow_coupon = ($allow_coupon_byproduct == 1) ? 1 : 0;
		}

		if($allow_coupon == 0) {
			$this->clearCoupon();
			return 'Coupon code not valid, please try different code.';
		}

		if(empty( $coupon_code ) or $coupon_code == vmText::_( 'COM_VIRTUEMART_COUPON_CODE_ENTER' )) {
			$this->clearCoupon();
			return false;
		} /*else if($this->couponCode == $coupon_code) {
			return;
		}*/

		$this->prepareCartData();

		$msg = $this->validateCoupon( $coupon_code );
		//$this->getCartPrices();


		if(empty( $msg )) {
			$this->couponCode = $coupon_code;
			$this->cartData['couponCode'] = $coupon_code;

			$this->prepareCartData( true );
			$this->setCartIntoSession( true, true );
			return vmText::_( 'COM_VIRTUEMART_CART_COUPON_VALID' );
		} else {
			$this->clearCoupon();

			$this->prepareCartData( true );
			$this->setCartIntoSession( true, true );
			return $msg;
		}
	}

	public function validateCoupon ($coupon_code) {

		$timeDeleteTries = time() - (24*60*60);
		foreach( $this->_triesValidateCoupon as $k => $v ) {
			if($k<8 or $k<$timeDeleteTries) {
				unset( $this->_triesValidateCoupon[$k] );
			}
		}

		$couponTryTime = (string)time();
		if(!in_array($coupon_code,$this->_triesValidateCoupon)){
			$this->_triesValidateCoupon[$couponTryTime] = $coupon_code;
		}

		if(count($this->_triesValidateCoupon)<8){
			$msg = CouponHelper::ValidateCouponCode($coupon_code, $this->cartPrices['salesPrice']);
			if(empty($msg)){
				$this->_triesValidateCoupon = array();	//A valid couponcode means we had a normal customer
			}
		} else{
			$msg = vmText::_('COM_VIRTUEMART_CART_COUPON_TOO_MANY_TRIES');
		}

		if (!empty($msg)) {
			$this->_dataValidated = false;
			$this->_blockConfirm = true;
			return $msg;
		}

		return '';
	}

	public function clearCoupon(){
		$this->couponCode = '';
		$this->cartData['couponCode'] = '';
	}

	public function setMethod($type,$force=false, $redirect=true, $id = null) {

		if($type){
			$idN = 'virtuemart_paymentmethod_id';
			$task = 'editpayment';
			$vmplugin = 'vmpayment';
		} else {
			$idN = 'virtuemart_shipmentmethod_id';
			$task = 'editshipment';
			$vmplugin = 'vmshipment';
		}
		if(!isset($id)) $id = vRequest::getInt($idN, $this->{$idN});
		if($this->{$idN} != $id or (!empty($id) and $force)){

			$this->{$idN} = $id;

			JPluginHelper::importPlugin($vmplugin);

			//Add a hook here for other methods, checking the data of the choosed plugin
			$msg = '';

			//@Todo we need actually &this,$msg for both triggers.
			if($type){
				$_retValues = vDispatcher::trigger('plgVmOnSelectCheckPayment', array( $this, &$msg));
			} else {
				$cart = &$this;
				$_retValues = vDispatcher::trigger('plgVmOnSelectCheckShipment', array( &$cart ));
			}

			$dataValid = true;
			foreach ($_retValues as $_retVal) {
				if ($_retVal === true ) {
					$this->setCartIntoSession();
					// Plugin completed succesfull; nothing else to do
					return true;
					break;
				} else if ($_retVal === false ) {
					if ($redirect) {
						$app = JFactory::getApplication();
						$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&task='.$task,$this->useXHTML,$this->useSSL), $msg);
						break;
					} else {
						return false;
					}
				}
			}
			$this->setCartIntoSession();
		}
	}

	/**
	 * Check the selected shipment data and store the info in the cart
	 * @param integer $shipment_id Shipment ID taken from the form data
	 * @author Max Milbers
	 */
	public function setShipmentMethod($force=false, $redirect=true, $virtuemart_shipmentmethod_id = null) {

		$this->setMethod(false, $force, $redirect, $virtuemart_shipmentmethod_id);
	}

	public function setPaymentMethod($force=false, $redirect=true, $virtuemart_paymentmethod_id = null) {

		$this->setMethod(true, $force, $redirect, $virtuemart_paymentmethod_id);

	}

	function confirmDone() {

		//Check if data has changed meanwhile
		$cHash = $this->getCartHash();
		if($cHash != $this->_dataValidated){
			$this->_dataValidated = false;
			vmInfo('COM_VIRTUEMART_CART_CHECKOUT_DATA_CHANGED');
			$app = JFactory::getApplication();
			$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'.$this->getLayoutUrlString(), FALSE) );
		}

		//Final check
		$this->checkoutData(false, true);
		$app = JFactory::getApplication();
		if ($this->_dataValidated == $cHash) {
			$this->_confirmDone = true;
			$this->orderDetails = 0;
			if($this->confirmedOrder()){
				$this->layout = 'orderdone';
				$this->setCartIntoSession();
				$currentUser = JFactory::getUser();
				if(!$currentUser->guest) {
					$um = VmModel::getModel('user');
					$this->BT['address_type'] = 'BT';
					$um->storeAddress($this->BT);
				}
				return true;
			} else {
				vmdebug('Confirmed order returned false');
				$this->_dataValidated = false;
				$this->_confirmDone = false;
				$this->setCartIntoSession();
				vmWarn('COM_VIRTUEMART_CART_CHECKOUT_DATA_NOT_VALID');
				$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE) );

			}

		}

		$this->_dataValidated = false;
		$this->_confirmDone = false;
		$this->setCartIntoSession();
		vmWarn('COM_VIRTUEMART_CART_CHECKOUT_DATA_NOT_VALID');
		$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'.$this->getLayoutUrlString(), FALSE) );

	}

	private function redirecter($relUrl,$redirectMsg){

		$this->_dataValidated = false;
		$app = JFactory::getApplication();
		if($this->_redirect and !$this->_redirected and !$this->_redirect_disabled){
			$this->_redirected = true;
			$this->setCartIntoSession( false, false);
			if(isset($redirectMsg))vmWarn($redirectMsg);
			$app->redirect(JRoute::_($relUrl,$this->useXHTML,$this->useSSL) );
			return true;
		} else {
			$this->_redirected = false;
			$this->_inCheckOut = false;
			$this->setCartIntoSession(false, false);
			return false;
		}
	}

	public function getLayoutUrlString($request=false){
		if($request){
			$layoutName = vRequest::getCmd('layout', 'default');
		} else {
			$layoutName = $this->layout;
		}
		if(!empty($layoutName) and $layoutName!='default'){
			return '&layout='.$layoutName;
		} else {
			return '';
		}
	}

	public function checkoutData($redirect = true, $forceStoreSession = false) {

		if($this->_redirected){
			$this->_redirect = false;
		} else {
			$this->_redirect = $redirect;
		}

		$layoutName = '';
		if(!empty($this->layout) and $this->layout!='default' and $this->layout!='orderdone') {
			$layoutName =  '&layout=' . $this->layout;
		}
vmdebug('checkoutData my layoutname ',$layoutName);
		$this->_inCheckOut = true;

		//This prevents that people checkout twice
		$this->setCartIntoSession(false,$forceStoreSession);

		// Check if a minimun purchase value is set
		if (($redirectMsg = $this->checkPurchaseValue()) != null) {
			$this->_inCheckOut = false;
			return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName , $redirectMsg);
		}

		$this->checkForCartQuantities();

		$currentUser = JFactory::getUser();

		$validUserDataBT = self::validateUserData();
		if ($validUserDataBT!==true) {	//Important, we can have as result -1,false and true.
			return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT' , '');
		}


		if(!empty($this->STsameAsBT) or (!$currentUser->guest and empty($this->selected_shipto))){	//Guest
//			quorvia consider whether the store wants to populate empty ST address with the BT address
			$populate_empty_ST = VmConfig::get('populateEmptyST', 1 );
			if($this->_confirmDone or $populate_empty_ST ){ //quorvia added to prevent population of ST address with BT address before the cart is confirmed
				$this->ST = $this->BT;
			} else {
				$this->ST = 0;
			}

		} else {
			if ($this->selected_shipto >0 ) {
				$userModel = VmModel::getModel('user');
				$stData = $userModel->getUserAddressList($currentUser->id, 'ST', $this->selected_shipto);

				if(isset($stData[0]) and is_object($stData[0])){
					$stData = get_object_vars($stData[0]);
					if($this->validateUserData('ST', $stData)>0){
						$this->ST = $stData;
					}
				} else {
					$this->selected_shipto = 0;
					$this->ST = $this->BT;
				}
			}

			//Only when there is an ST data, test if all necessary fields are filled
			$validUserDataST = self::validateUserData('ST');
			if ($validUserDataST!==true) {
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=ST' , '');
			}
		}

		$usersConfig = JComponentHelper::getParams( 'com_users' );
		$useractivation = $usersConfig->get( 'useractivation' );
		if ($currentUser ->block) {
			if($useractivation!=1){
				$redirectMsg = vmText::_('JERROR_NOLOGIN_BLOCKED');
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT' , $redirectMsg);
			}
		}

		if(VmConfig::get('oncheckout_only_registered',0)) {
			if(empty($currentUser->id)) {
				$redirectMsg = vmText::_('COM_VIRTUEMART_CART_ONLY_REGISTERED');
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT' , $redirectMsg);
			}
		}

		// Test Coupon
		if (!empty($this->couponCode)) {
			$redirectMsg = $this->validateCoupon($this->couponCode);


			if (!empty($redirectMsg)) {
				$this->clearCoupon();
				$this->_inCheckOut = false;
				$this->setCartIntoSession();
				return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName , $redirectMsg);
			}
		}
		$redirectMsg = '';

		//Test Shipment and show shipment plugin
		if (empty($this->virtuemart_shipmentmethod_id)) {
			return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_shipment' , $redirectMsg);
		} else {
			vDispatcher::importVMPlugins('vmshipment');
			//Add a hook here for other shipment methods, checking the data of the choosed plugin

			$retValues = vDispatcher::trigger('plgVmOnCheckoutCheckDataShipment', array(  $this));

			foreach ($retValues as $retVal) {
				if ($retVal === true) {
					break; // Plugin completed succesfull; nothing else to do
				} elseif ($retVal === false) {
					// Missing data, ask for it (again)
					return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_shipment' , $redirectMsg);
					// 	NOTE: inactive plugins will always return null, so that value cannot be used for anything else!
				}
			}
		}

		//Test Payment and show payment plugin
		if(!empty($this->cartPrices['salesPrice']) and $this->cartPrices['salesPrice']>0.0){
			if (empty($this->virtuemart_paymentmethod_id)) {
				return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=editpayment' , $redirectMsg);
			} else /*if ($redirect)*/ {
				vDispatcher::importVMPlugins('vmpayment');
				//Add a hook here for other payment methods, checking the data of the choosed plugin
				$retValues = vDispatcher::trigger('plgVmOnCheckoutCheckDataPayment', array( $this));

				foreach ($retValues as $retVal) {
					if ($retVal === true) {
						break; // Plugin completed succesful; nothing else to do
					} elseif ($retVal === false) {
						// Missing data, ask for it (again)
						return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=editpayment' , $redirectMsg);
						// 	NOTE: inactive plugins will always return null, so that value cannot be used for anything else!
					}
				}
			} /*else {

			}*/
		}

		$validUserDataCart = self::validateUserData('cartfields',$this->cartfields,$this->_redirect);

		if($validUserDataCart!==true){
			if($this->_redirect){
				$this->_inCheckOut = false;
				$redirectMsg = null;
				vmdebug('_redirect due missing cartfields '.$layoutName,$validUserDataCart);
				return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName , $redirectMsg);
			}
			$this->_blockConfirm = true;
		} else {
			//Atm a bit dirty. We store this information in the BT order_userinfo, so we merge it here, it gives also
			//the advantage, that plugins can easily deal with it.
			//$this->BT = array_merge((array)$this->BT,(array)$this->cartfields);
		}

		//Either we use here $this->_redirect, or we redirect always directly, atm we check the boolean _redirect
		if (count($this->cartProductsData) == 0) {
			$this->_inCheckOut = false;
			return $this->redirecter('index.php?option=com_virtuemart&view=cart', vmText::_('COM_VIRTUEMART_CART_NO_PRODUCT'));
		}

		//Show cart and checkout data overview
		if($this->_redirected){
			//$this->_redirected = false;
		} else {
			$this->_inCheckOut = false;
		}

		if($this->_blockConfirm){
			$this->_dataValidated = false;
			$this->_inCheckOut = false;
			return $this->redirecter('index.php?option=com_virtuemart&view=cart'.$layoutName,'');
		} else {
			$this->_dataValidated = $this->getCartHash();
			$this->_inCheckOut = false;
			$this->setCartIntoSession(false, false);
			if ($this->_redirect) {
				$app = JFactory::getApplication();
				vmInfo('COM_VIRTUEMART_CART_CHECKOUT_DONE_CONFIRM_ORDER');
				$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'.$layoutName, FALSE));
			} else {
				return true;
			}
		}
	}

	/**
	 * Check if a minimum purchase value for this order has been set, and if so, if the current
	 * value is equal or hight than that value.
	 * @author Oscar van Eijk
	 * @return An error message when a minimum value was set that was not eached, null otherwise
	 */
	private function checkPurchaseValue() {

		$this->prepareVendor();
		if ($this->vendor->vendor_min_pov > 0) {
			$this->getCartPrices();
			if ($this->cartPrices['salesPrice'] < $this->vendor->vendor_min_pov) {
				$currency = CurrencyDisplay::getInstance();
				return vmText::sprintf('COM_VIRTUEMART_CART_MIN_PURCHASE', $currency->priceDisplay($this->vendor->vendor_min_pov));
			}
		}
		return null;
	}

	/**
	 * Test userdata if valid
	 *
	 * @author Max Milbers
	 * @param String if BT or ST
	 * @param Object If given, an object with data address data that must be formatted to an array
	 * @return redirectMsg, if there is a redirectMsg, the redirect should be executed after
	 */
	public function validateUserData($type='BT', $obj = null,$redirect = false) {

		$usersModel = VmModel::getModel('user');
		if($obj==null){
			return $usersModel->validateUserData($this->{$type},$type,$redirect);
		} else {
			return $usersModel->validateUserData($obj,$type,$redirect);
		}
	}

	/**
	 * This function is called, when the order is confirmed by the shopper.
	 *
	 * Here are the last checks done by payment plugins.
	 * The mails are created and send to vendor and shopper
	 * will show the orderdone page (thank you page)
	 *
	 */
	function confirmedOrder() {

		//Just to prevent direct call
		if ($this->_dataValidated and $this->_confirmDone and !$this->_inCheckOut) {

			if($this->_inConfirm) return false;

			//We set this in the trigger of the plugin. so old plugins keep the old behaviour
			$orderModel = VmModel::getModel('orders');

			$this->orderdoneHtml = false;
			$reUseTimeSql = VmConfig::get('reuseorders','30');
			if(empty($reUseTimeSql)){
				$this->order_number = null;
			}
			$this->virtuemart_order_id = $orderModel->createOrderFromCart($this);

			if (!$this->virtuemart_order_id) {
				$mainframe = JFactory::getApplication();
				//vmError('No order created '.$orderModel->getError());
				$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart', FALSE) );
			}

			$orderId = $this->virtuemart_order_id;
			//$orderDetails = $orderModel->getMyOrderDetails($this->virtuemart_order_id,$this->order_number,$this->order_pass);
			$orderDetails = $orderModel->getOrder($this->virtuemart_order_id);

			if(!$orderDetails or empty($orderDetails['details'])){
				echo vmText::_('COM_VIRTUEMART_CART_ORDER_NOTFOUND');
				return false;
			}

			vDispatcher::importVMPlugins('vmpayment');

			$this->orderDetails = $orderDetails;

			$returnValues = vDispatcher::trigger('plgVmConfirmedOrder', array(&$this, $orderDetails));


			if($this->_blockConfirmedCheckout){
				VmInfo('Checkout blocked by Payment plugin');
				return false;
			}

			if($this->orderdoneHtml===false){
				$orderDoneHtml = vRequest::get('html', false);
				if($orderDoneHtml){
					$this->orderdoneHtml = $orderDoneHtml;
				} else {
					$this->orderdoneHtml = vmText::_('COM_VIRTUEMART_ORDER_PROCESSED');
				}
			}

			// may be redirect is done by the payment plugin (eg: paypal)
			// if payment plugin echos a form, false = nothing happen, true= echo form ,
			// 1 = cart should be emptied, 0 cart should not be emptied
			$this->setCartIntoSession();

			return $orderId;
		} else {
			vmdebug('one condition not meet for confirmedOrder',$this->_dataValidated, (int)$this->_confirmDone, (int)$this->_inCheckOut);
		}
		return false;
	}

	public function getCartHash(){

		return md5(serialize($this->cartProductsData) .
		serialize($this->BT).
		($this->STsameAsBT) ? '': serialize($this->ST) .
			$this->STsameAsBT .
			$this->order_language.
			$this->selected_shipto.
			serialize($this->cartPrices).
			$this->virtuemart_shipmentmethod_id .
			(!empty($this->cartData['shipmentName']) ? $this->cartData['shipmentName'] : '') .
			$this->virtuemart_paymentmethod_id .
			(!empty($this->cartData['paymentName']) ? $this->cartData['paymentName'] : '') .
			$this->customer_number );
	}
	/**
	 * emptyCart: Used for payment handling.
	 *
	 * @author Valerie Cartan Isaksen
	 *
	 */
	public function emptyCart(){
		self::emptyCartValues($this);
	}

	/**
	 * emptyCart: Used for payment handling.
	 *
	 * @author Valerie Cartan Isaksen
	 *
	 */
	static public function emptyCartValues( &$cart, $session = true){

		//We dont need to do this, if the cart was just emptied
		/*if(empty($cart->products) and empty($cart->cartProductsData) and empty($cart->cartData) ){
			return;
		}*/

		vmTrace('emptyCartValues');
		//if we used a coupon, we must set it in final use now
		$couponCode = '';
		if(!empty($cart->couponCode)){
			$couponCode = $cart->couponCode;
		} else if(!empty($cart->cartData['couponCode'])){
			$couponCode = $cart->cartData['couponCode'];
		}
		if(!empty($couponCode)){
			CouponHelper::setInUseCoupon($couponCode, true, 1);
		}

		//We delete the old stuff
		$cart->products = array();
		$cart->cartProductsData = array();
		$cart->cartData = array();
		$cart->cartPrices = array();
		$cart->cartfields = array();
		$cart->_inCheckOut = false;
		$cart->_dataValidated = false;
		$cart->_confirmDone = false;
		$cart->couponCode = '';
		$cart->order_language = '';
		$cart->virtuemart_shipmentmethod_id = 0; //OSP 2012-03-14
		$cart->virtuemart_paymentmethod_id = 0;
		$cart->order_number=null;
		$cart->_fromCart = false;
		$cart->_inConfirm = false;
		$cart->totalProduct=false;
		$cart->productsQuantity=array();

		$cart->OrderIdOrderDone = $cart->virtuemart_order_id;  //We delete this one later in order done, it should not hurt,...
		$cart->virtuemart_order_id = false;
		$cart->layout = self::getCartLayoutByVmConfig();

		if($session){
			$cart->deleteCart();
			$cart->setCartIntoSession();
		}
		//It looks like we need this to prevent, that the cart gets the old layout back (orderdone)
		$cart = VirtueMartCart::getCart(true);
	}

	function resetEntireCart(){

		self::emptyCartValues($this, false);
		$this->user = false;
		$this->customer_number = '';
		$this->BT = array();
		$this->ST = array();
		$this->BTaddress = array();
		$this->STaddress = array();
		$this->deleteCart();
		$this->virtuemart_cart_id = 0;

		$this->setCartIntoSession(false,true);
		self::$_carts[$this->vendorId] = $this;

	}

	function saveCartFieldsInCart(){

		$userFieldsModel = VmModel::getModel('userfields');

		$cartFields = $userFieldsModel->getUserFields(
			'cart'
			, array('delimiters' => true, 'captcha' => true, 'system' => false)
			, array('delimiter_userinfo', 'name','username', 'password', 'password2', 'address_type_name', 'address_type', 'user_is_vendor', 'agreed'));

		foreach ($cartFields as $fld) {
			if(!empty($fld->name)){
				$name = $fld->name;

				if($fld->type=='checkbox'){
					$tmp = vRequest::getInt($name,0);
					$this->cartfields[$name] = $tmp;
				} else {
					$tmp = vRequest::getString($name,null);
					if(isset($tmp)){
						if(!empty($tmp)){
							if(is_array($tmp)){
								$tmp = implode("|*|",$tmp);
							}

							$tmp = vRequest::vmSpecialChars($tmp);

							//$tmp = (string)preg_replace('#on[a-z](.+?)\)#si','',$tmp);//replace start of script onclick() onload()...
						}

						$this->cartfields[$name] = $tmp;
						//vmdebug('Store $this->cartfields[$name] '.$name.' '.$tmp);
					}
				}
			}
		}
		$this->BT = array_merge((array)$this->BT,(array)$this->cartfields);
		$this->setCartIntoSession();
	}

	function saveAddressInCart($data, $type, $putIntoDb = true,$prefix='') {

		$userFieldsModel = VmModel::getModel('userfields');

		if ($type == 'STaddress' or $type == 'BTaddress'){
			vmTrace('STaddress found, seek and destroy');
		}
		$prepareUserFields = $userFieldsModel->getUserFieldsFor('cart',$type);

		if(!is_array($data)){
			$data = get_object_vars($data);
		}

		if ($type =='ST') {
			$this->STsameAsBT = 0;
		} else { // BT
			if(empty($data['email'])){
				$jUser = JFactory::getUser();
				if(!empty($jUser->email)) $data['email'] = $jUser->email;
			}
		}

		$address = array();

		foreach ($prepareUserFields as $fld) {
			if(!empty($fld->name)){
				$name = $fld->name;

				if(isset($data[$prefix.$name])){
					//We directly overwrite the $data array
					$address[$name] = $data[$prefix.$name] = $userFieldsModel->prepareFieldDataSave($fld, $data, $prefix);

				} else {
					//vmdebug('Data not found for type '.$type.' and name '.$prefix.$name.' ');
				}
			}
		}

		//dont store passwords in the session
		unset($address['password']);
		unset($address['password2']);

		//We reset the default array
		$this->{'byDefault'.$type} = array();
		$this->{$type} = $address;

		if($putIntoDb){
			$this->setCartIntoSession(true, true);
		}
		vmdebug('saveAddressInCart ',(int)$putIntoDb,$this->STsameAsBT,$type,$this->{$type});
	}


	/**
	 * Returns ST address considering the set options, with fallback
	 * @author Max Milbers
	 */
	public function getST($name=0,$FBBT=true){

		$addr = $this->ST;

		if($this->STsameAsBT == 0){
			if($FBBT){
				if($name!==0){
					if(!isset($this->ST[$name])){
						$addr = $this->BT;
					}
				} else if($this->ST == 0){
					//vmdebug('getST ST=0, use BT');
					$addr = $this->BT;
				}
			}
		} else {
			$addr = $this->BT;
			//vmdebug('getST STsameAsBT is set, use BT',$addr);
		}

		if($name!==0){
			return isset($addr[$name]) ? $addr[$name] : '';
		} else {
			return $addr!==0 ? $addr : array();
		}

	}

	/**
	 * This function controlls the visible methods in the cart and sets the method automatically.
	 * @author Valérie Isaksen, Max Milbers
	 * @param $type
	 * @return bool
	 */
	function checkAutomaticSelectedPlug($type){

		$vm_method_name = 'virtuemart_'.$type.'method_id';

		//if(!empty($this->$vm_method_name)) return false;

		if (count($this->products) == 0 ) {
			return false;
		}

		$setAutomatic = VmConfig::get('set_automatic_'.$type,'0');

		if ($setAutomatic =='-1') {
			return false;
		}

		$d = VmConfig::$_debug;
		if(VmConfig::get('debug_enable_methods',false)){
			VmConfig::$_debug = 1;
		}

		$trigger = 'plgVmOnCheckAutomaticSelected';
		if(VmConfig::get('checkAutomaticLegacy',false)){
			$trigger .= ucfirst($type);
		}

		$counter=0;
		$returnValues = vDispatcher::trigger($trigger, array(  $this,$this->cartPrices, &$counter, $type));

		//vmdebug('checkAutomaticSelectedPlug my return value '.$type,$returnValues);

		$vm_autoSelected_name = 'automaticSelected'.ucfirst($type);
		$this->{$vm_autoSelected_name}=false;
		$nb = 0;
		$method_id = array();
		foreach ($returnValues as $returnValue) {
			if ( isset($returnValue )) {
				if(is_array($returnValue)){
					foreach($returnValue as $method){
						$nb ++;
						$method_id[] = $method;
					}
				} else if ($returnValue){
					$nb ++;
					$method_id[] = $returnValue;
				} else if ( $returnValue === 0) {
					//Here are the old plugins
					//$this->{$vm_autoSelected_name}=true;
					vmdebug('Old plugin detected, no method given');
				}
			}
		}

		//vmdebug('checkAutomaticSelectedPlug my $method_ids '.$type,$nb,$method_id);



		if(empty($method_id) or empty($method_id[0])){
			if($nb==0){
				$this->{$vm_method_name} = 0;
			}
			return false;
		}


		if ($nb==1) {
			$this->{$vm_method_name} = $method_id[0];
			$this->{$vm_autoSelected_name}=true;	//This controlls the variable "automaticSelectedPayment" or "automaticSelectedShipment" which meant before vm3.5, that only one method exists

			vmdebug('FOUND automatic SELECTED '.$type.' !!',$this->{$vm_method_name});
		} else {
			//This check breaks old plugins, which return 0 instead a correct method id.
			//There are a lot checks following executing checkConditions, so it should be okey to uncomment that.
			/*if(!empty($this->{$vm_method_name})){
				if(!in_array($this->{$vm_method_name},$method_id)){

					$this->{$vm_method_name} = 0;
					vmdebug('SELECTED Method not among selectables '.$type.' !!',$this->{$vm_method_name},$method_id);
				}
			}*/

			if(empty($this->{$vm_method_name})){
				if(empty($setAutomatic)){
					$this->{$vm_method_name} = $method_id[0];
					vmdebug('SELECTED automatic method  '.$type.' !!',$this->{$vm_method_name});
				} else {
					if($setAutomatic>0){
						if(in_array($setAutomatic,$method_id)){
							$this->{$vm_method_name} = $setAutomatic;
							vmdebug('SELECTED by automatic method  '.$type.' '.$setAutomatic.'!!',$this->{$vm_method_name});
						} else {
							$this->{$vm_method_name} = 0;
							vmdebug('SELECTED NOT by automatic method  '.$type.' '.$setAutomatic.'!!',$this->{$vm_method_name});
						}
					}
				}
			}

		}
		VmConfig::$_debug = $d;
		$this->setCartIntoSession();
		return true;
	}

	/*
	 * CheckAutomaticSelectedShipment
	* If only one shipment is available for this amount, then automatically select it
	* @deprecated
	* @author Valérie Isaksen
	*/
	function CheckAutomaticSelectedShipment() {
		return $this->checkAutomaticSelectedPlug('shipment');
	}

	/*
	 * CheckAutomaticSelectedPayment
	* If only one payment is available for this amount, then automatically select it
	* @deprecated
	* @author Valérie Isaksen
	*/
	function CheckAutomaticSelectedPayment() {
		return $this->checkAutomaticSelectedPlug('payment');
	}

	/**
	 * Function Description
	 *
	 * @author Max Milbers
	 * @access public
	 * @param array $cart the cart to get the products for
	 * @return array of product objects
	 */

	public function getCartPrices($force=false) {

		if(empty($this->cartPrices) or !$this->_calculated or $force){

			$calculator = calculationHelper::getInstance();

			$this->pricesCurrency = $calculator->_currencyDisplay->getCurrencyForDisplay();

			$calculator->getCheckoutPrices($this);

			//Fallback for old extensions
			$this->pricesUnformatted = $this->cartPrices;

			//We must do this here, otherwise if we have a product more than one time in the cart
			//it has always the same price
			foreach($this->products as $k => $product){
				$this->products[$k]->prices = $product->allPrices[$product->selectedPrice];
			}
			$this->_calculated = true;
			//$this->setCartIntoSession();
		}
		return $this->cartPrices;
	}

	function prepareVendor(){
		if(empty($this->vendor)){
			$vendorModel = VmModel::getModel('vendor');
			$this->vendor = $vendorModel->getVendor($this->vendorId);
			$vendorModel->addImages($this->vendor,1);
			if (VmConfig::get('enable_content_plugin', 0)) {
				shopFunctionsF::triggerContentPlugin($this->vendor, 'vendor','vendor_terms_of_service');
			}
		}
	}

	static function getProduct( $virtuemart_product_id, $quantity){
		$productModel = VmModel::getModel('product');
		//We use here the same params as in order model function getOrder to be able to use the cache
		$productTemp = $productModel->getProduct($virtuemart_product_id, true, false, false,$quantity);
		if(empty($productTemp->virtuemart_product_id) or empty($productTemp->published)){
			vmError('The product is no longer available; cart getProduct is empty','The product is no longer available');
			vmTrace('Product empty');
			return false;
		}
		$productTemp->modificatorSum = null;
		//Very important! must be cloned, else all products with same id get the same productCustomData due the product cache
		return clone($productTemp);
	}

	function prepareCartData($force=true){

		//$this->totalProduct = 0;
		if(count($this->products) != count($this->cartProductsData) or $this->_productAdded){
			$productsModel = VmModel::getModel('product');
			$this->totalProduct = 0;
			$this->productsQuantity = array();

			foreach($this->cartProductsData as $k =>&$productdata){
				$productdata = (array)$productdata;

				if(isset($productdata['virtuemart_product_id'])){
					if(empty($productdata['virtuemart_product_id']) or empty($productdata['quantity'])){
						unset($this->cartProductsData[$k]);
						continue;
					}
					$productdata['quantity'] = (int)$productdata['quantity'];
					//Important, must not use calculation, would lead to wrong prices, because the full cart is not know yet.
					$product = VirtueMartCart::getProduct($productdata['virtuemart_product_id'], $productdata['quantity']);

					if(!$product or ($product->product_discontinued and !VmConfig::get('discontinuedPrdsBrowseable',1))){
						vmError('The product is no longer available; prepareCartData virtuemart_product_id is empty','The product is no longer available');
						unset($this->cartProductsData[$k]);
						continue;
					}


					$productdata['virtuemart_product_id'] = (int)$productdata['virtuemart_product_id'];

					$product -> customProductData = $productdata['customProductData'];
					$product -> quantity = $productdata['quantity'];

					// No full link because Mail want absolute path and in shop is better relative path
					$product->url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id);//JHtml::link($url, $product->product_name);
					$product->cart_item_id = $k ;

					if ( VmConfig::get('oncheckout_show_images')){
						$productsModel->addImages($product,1);
					}

					//$product->customfields = $customFieldsModel->getCustomEmbeddedProductCustomFields($product->allIds,0,1);

					$enough = $this->checkForQuantities($product,$product -> quantity);

					if($product->quantity<=0){
						unset($this->cartProductsData[$k]);
						continue;
					}
					$this->products[$k] = $product;
					$this->totalProduct += $product -> quantity;
					$productdata['quantity'] = $product -> quantity;

					if(isset($this->productsQuantity[$product->virtuemart_product_id])){
						$this->productsQuantity[$product->virtuemart_product_id] += $product -> quantity;
					} else {
						$this->productsQuantity[$product->virtuemart_product_id] = $product -> quantity;
					}

					$product = null;
				} else {
					unset($this->cartProductsData[$k]);
					vmError('prepareCartData $productdata[virtuemart_product_id] was empty');
				}
			}
			$this->setCartIntoSession();
		} else {

		}

		$this->getCartPrices($force);

		vDispatcher::importVMPlugins('vmpayment');
		$returnValues = vDispatcher::trigger('plgVmgetPaymentCurrency', array( $this->virtuemart_paymentmethod_id, &$this->paymentCurrency));

		$this->_productAdded = false;
		self::$_carts[$this->vendorId] = &self::$_cart;
		return $this->cartData ;

	}

	/*
	 * Trigger to place Coupon, payment, shipment advertisement on the cart
	 */
	public function getCheckoutAdvertise() {

		if(!$this->cartAdv){
			$this->cartAdv=array();

			vDispatcher::importVMPlugins('vmpayment');
			$returnValues = vDispatcher::trigger('plgVmOnCheckoutAdvertise', array( $this, &$this->cartAdv));
		}
		return $this->cartAdv;
	}

	public function checkForCartQuantities() {

		$quantities = vRequest::getInt('quantity');
		if(empty($quantities)) return true;
		$updated = false;

		foreach($quantities as $key=>$quantity){
			if (isset($this->cartProductsData[$key]) and !empty($quantity) and !isset($_POST['delete_'.$key])) {
				if($quantity!=$this->cartProductsData[$key]['quantity']){
					$isok = true;
					$newTotal = $quantity;
					if (isset($this->products[$key])) {
						$product = $this->products[$key];
						$this->checkForQuantities($product, $newTotal);
						if ($newTotal !== $quantity) {
							$quantity = $newTotal;
							$isok = false;
						}

					}
					$this->cartProductsData[$key]['quantity'] = $quantity;
					$updated = true;
					if ($isok) {
						vmInfo('COM_VIRTUEMART_PRODUCT_UPDATED_SUCCESSFULLY');
					}
					else {

					}
				}

			} else {
				unset($this->cartProductsData[$key]);
				vmInfo('COM_VIRTUEMART_PRODUCT_REMOVED_SUCCESSFULLY');
				$updated = true;
			}
		}

		$this->setCartIntoSession( false, $updated);
		return $updated;
	}

	/** Checks if the quantity is correct
	 *
	 * @author Max Milbers
	 */
	private function checkForQuantities($product, &$quantity=0) {


		vDispatcher::importVMPlugins('vmcustom');
		// return null to proceed with further VM rules
		// return true to not validate the quantity with OPC or VM
		// return false to return errorMsg
		//if to allow to adjust the current quantity
		$adjustQ = false;
		$errorMsg = '';
		$cart = &$this;
		$retValues = vDispatcher::trigger('plgVmOnCheckoutCheckStock', array(  &$cart, &$product, &$quantity, &$errorMsg, &$adjustQ));

		foreach ($retValues as $v) {
			if ($v === false) {
				vmInfo($errorMsg,$product->product_name);
				return false;
			}
			if ($v === true) {
				return true;
			}
		}

		$stockhandle = VmConfig::get('stockhandle_products', false) && $product->product_stockhandle ? $product->product_stockhandle : VmConfig::get('stockhandle','none');

		// Check for a valid quantity
		if (!is_numeric( $quantity)) {
			$product->errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_ERROR_NO_VALID_QUANTITY', $product->product_name);
			vmWarn($product->errorMsg);
			return false;
		}
		// Check for negative quantity
		if ($quantity < 1) {
			$product->errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_ERROR_NO_VALID_QUANTITY', $product->product_name);
			vmWarn($product->errorMsg);
			return false;
		}

		$checkForDisable = false;
		if ($stockhandle!='none' && $stockhandle!='risetime') {
			$checkForDisable = true;
		}

		// Check for the minimum and maximum quantities
		$min = (int)$product->min_order_level;
		if ($min != 0 && $quantity < $min){
			$quantity = $min;
			$product->errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_MIN_ORDER', $min, $product->product_name);
			vmWarn($product->errorMsg);
			if (!$checkForDisable) return false;
		}



		$step = (int)$product->step_order_level;

		$max = (int)$product->max_order_level;



		if ($step != 0 && ($quantity%$step)!= 0) {
			/* 
				stAn - with step quantity we have these options: 
				- we raise it by default to next step
				- the next step may not be valid against max_order_level (set quantity=0)
				- so we lower it to previous step
				- which may not be valid against min_order_level  (set quantity=0)
				- or previous step is smaller than 0   (set quantity=0)
				
				stAn get next value - example:
				q=500, step=3 => 500 - (2) + 3 = 501 
			*/

			$quantity = $quantity - ($quantity%$step) + $step;
			if ((!empty($product->max_order_level)) && ($quantity > (int)$product->max_order_level)) {
				//get previous step quantity and have it validated by next section: 
				$quantity = $quantity - $step;
				if ($quantity < 0) $quantity = 0;
				if ($quantity < (int)$product->min_order_level) {
					$quantity = 0;
				}
			}

			$product->errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_STEP_ORDER', $step);
			vmWarn($product->errorMsg);
			if (!$checkForDisable) return false;

			/*stAn, next step is larger than max_order_level and previous step is smaller then zero OR smaller then min_order_level*/
			if (empty($quantity)) {
				return false; //stAn - can we really return false for invalid value ?
			}
		}


		if ($max != 0 && $quantity > $max) {
			$quantity = $max;
			$product->errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_MAX_ORDER', $max, $product->product_name);
			vmWarn($product->errorMsg);
			if (!$checkForDisable) return false;
		}

		// Check to see if checking stock quantity
		if ($checkForDisable) {
			$productsleft = $product->product_in_stock - $product->product_ordered;

			if ($quantity > $productsleft ){
				vmdebug('my products left '.$productsleft.' and my quantity '.$quantity);
				if($productsleft>=$min ){
					$quantity = $productsleft;
					$product->errorMsg = vmText::sprintf('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_QUANTITY',$product->product_name,$quantity);
					vmWarn($product->errorMsg);
				} else {
					$quantity = 0;
					$product->errorMsg = vmText::_('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_STOCK');
					vmWarn($product->errorMsg);
					return false;
				}
			}
		}

		return true;
	}

	// Render the code for Ajax Cart
	function prepareAjaxData($withProductImages=false, $force = false){

		$this->prepareCartData($force);
		$data = new stdClass();
		$data->products = array();
		$data->totalProduct = 0;

		//OSP when prices removed needed to format billTotal for AJAX
		$currencyDisplay = CurrencyDisplay::getInstance();

		foreach ($this->products as $i=>$product){

			//Create product URL
			$url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id, FALSE);
			$data->products[$i]['product_name'] = JHtml::link($url, $product->product_name);

			//  custom product fields display for cart
			//Todo the customProductData should be renamed, because it is not the same customProductData as used elsewhere
			$data->products[$i]['customProductData'] = VirtueMartModelCustomfields::CustomsFieldCartModDisplay($product);
			$data->products[$i]['product_sku'] = $product->product_sku;
			$data->products[$i]['prices'] = $currencyDisplay->priceDisplay( $product->allPrices[$product->selectedPrice]['subtotal']);
			if($withProductImages and !empty($product->images[0])) $data->products[$i]['image']= $product->images[0]->displayMediaThumb ('', FALSE);

			// other possible option to use for display
			$data->products[$i]['subtotal'] = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['subtotal']);
			$data->products[$i]['subtotal_tax_amount'] = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['subtotal_tax_amount']);
			$data->products[$i]['subtotal_discount'] = $currencyDisplay->priceDisplay( $product->allPrices[$product->selectedPrice]['subtotal_discount']);
			$data->products[$i]['subtotal_with_tax'] = $currencyDisplay->priceDisplay($product->allPrices[$product->selectedPrice]['subtotal_with_tax']);

			// UPDATE CART / DELETE FROM CART
			$data->products[$i]['quantity'] = $product->quantity;
			$data->totalProduct += $product->quantity ;

		}

		if(empty($this->cartPrices['billTotal']) or $this->cartPrices['billTotal'] < 0){
			$this->cartPrices['billTotal'] = 0.0;
		}

		$data->billTotal = $currencyDisplay->priceDisplay( $this->cartPrices['billTotal'] );
		$data->billTotal_tax_amount = $currencyDisplay->priceDisplay( $this->cartPrices['taxAmount'] );
		$data->billTotal_net = $currencyDisplay->priceDisplay( $this->cartPrices['priceWithoutTax'] );
		$data->billTotal_discounted_net = $currencyDisplay->priceDisplay( $this->cartPrices['discountedPriceWithoutTax'] );
		//end
		$data->dataValidated = $this->_dataValidated ;

		if ($data->totalProduct>1) $data->totalProductTxt = vmText::sprintf('COM_VIRTUEMART_CART_X_PRODUCTS', $data->totalProduct);
		else if ($data->totalProduct == 1) $data->totalProductTxt = vmText::_('COM_VIRTUEMART_CART_ONE_PRODUCT');
		else $data->totalProductTxt = vmText::_('COM_VIRTUEMART_EMPTY_CART');

			$taskRoute = '';
			$data->linkName = vmText::_('COM_VIRTUEMART_CART_SHOW');


		$multixcart = VmConfig::get('multixcart',0);
		if(!empty($multixcart)){
			$taskRoute .= '&virtuemart_vendor_id='.$this->vendorId;
		}
		$data->cart_show_link = JRoute::_("index.php?option=com_virtuemart&view=cart".$taskRoute,$this->useSSL);
		$data->cart_show = '<a class="details" style="float:right;" href="'.$data->cart_show_link.'" rel="nofollow" >'.$data->linkName.'</a>';
		$data->billTotal = vmText::sprintf('COM_VIRTUEMART_CART_TOTALP',$data->billTotal);

		return $data ;
	}

	/**
	 * Get the total weight for the order, based on which the proper shipping rate
	 * can be selected.
	 *
	 * @param object $cart Cart object
	 * @return float Total weight for the order
	 */
	static public function getCartWeight (VirtueMartCart $cart, $to_weight_unit = 0) {


		if(empty($to_weight_unit)) $to_weight_unit = VmConfig::get('weight_unit_default','KG');
		$weight = 0.0;

		if(count($cart->products)>0){

			foreach ($cart->products as $product) {
				$weight += (ShopFunctions::convertWeightUnit ($product->product_weight, $product->product_weight_uom, $to_weight_unit) * $product->quantity);
			}
		}

		return $weight;
	}

	/**
	 * Get the total Quantity for the order, based on which the proper shipping rate
	 * can be selected.
	 *
	 * @param object $cart Cart object
	 * @return float Total weight for the order
	 */
	static public function getCartQuantity (VirtueMartCart $cart) {

		 $qu = 0;	//Must not be static!

		if(count($cart->products)>0 and empty($qu)){

			foreach ($cart->products as $product) {
				$qu += $product->quantity;
			}
		}

		return $qu;
	}

	function unsetForDebug(){
		foreach($this->products as $product){
			$product->unsetForDebug();
		}
		if(!empty($this->user)) $this->user->unsetForDebug();
		//unset($this->user->JUser->_db);

		if(isset($this->vendor)) $this->vendor->unsetForDebug();
	}

	function resetForDebug(){
		foreach($this->products as $product){
			$product->resetForDebug();
		}
		if(!empty($this->user)) $this->user->resetForDebug();
		if(isset($this->vendor)) $this->vendor->resetForDebug();
	}
}
