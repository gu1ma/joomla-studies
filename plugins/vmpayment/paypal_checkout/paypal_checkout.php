<?php
/**
 *
 * Paypal checkout payment plugin
 *
 * @author Max Milbers
 * @version $Id: paypal_checkout.php
 * @package VirtueMart
 * @subpackage payment
 * Copyright (C) 2023 Virtuemart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

defined('_JEXEC') or die('Restricted access');

class PPResult {

	var $id = null;
	var $virtuemart_order_id = null;
	var $order_number = null;
	var $virtuemart_paymentmethod_id = null;
	var $payment_name = null;
	var $payment_order_total = null;
	var $payment_currency = null;
	var $email_currency = null;
	var $cost_per_transaction = null;
	var $cost_percent_total = null;
	var $tax_id = null;
	var $body = null;
}

class plgVmPaymentPaypal_checkout extends vmPSPlugin {

	const BNCODE = "VirtueMart_Cart_PPCP";

	static $PPResult = null;//stdClass();
	static $Response = null;

	function __construct(& $subject, $config) {

		parent::__construct ($subject, $config);
		$this->tableFields = array_keys ($this->getTableSQLFields ());

		$varsToPush = $this->getVarsToPush ();
		$this->addVarsToPushCore($varsToPush,1);
		$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);
		//$this->setConvertable(array('min_amount','max_amount','cost_per_transaction','cost_min_transaction'));

		JLoader::register('PayPalToken', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/ppc_token.php');
		JLoader::register('PayPalOrder', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/ppc_order.php');
		JLoader::register('PayPalIdentity', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/ppc_identity.php');
		JLoader::register('PayPalWebHooks', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/ppc_webhooks.php');
		JLoader::register('PayPalPayment', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/ppc_payment.php');
		JLoader::register('PayPalOnboarding', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/ppc_onboarding.php');
		JLoader::register('PayPalCheckoutResponse', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/response.php');
		JLoader::register('PaypalResponseWebHooks', VMPATH_ROOT .'/plugins/vmpayment/paypal_checkout/helpers/response_webhooks.php');

		self::$PPResult = new PPResult();
	}

	function getTableSQLFields() {

		$SQLfields = array(
			'id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id' => 'int(1) UNSIGNED',
			'order_number' => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_name' => 'varchar(600)',
			'payment_order_total' => 'decimal(15,5) NOT NULL',
			'payment_currency' => 'smallint(1)',
			'ppOrderId' => 'varchar(100)',
			'capture_id' => 'varchar(100)',
			'authorize_id' => 'varchar(100)',
			//'email_currency' => 'smallint(1)',
			'cost_per_transaction' => 'decimal(10,2)',
			'cost_percent_total' => 'decimal(10,2)',
			//'tax_id' => 'smallint(1)',
			'body' => 'varchar(10000)'
		);
		return $SQLfields;
	}


/*	function vmdebug($debugdescr,$debugvalues=NULL){
		if(!empty($this->_currentMethod->paypal_debug)){
			vmdebug($debugdescr,$debugvalues);
		}
	}
*/
	
	function plgVmOnProductDisplayPayment($product, &$productDisplay) {
		//return;
		$vendorId = empty($product->virtuemart_vendor_id)? 1: $product->virtuemart_vendor_id;
		if ($this->getPluginMethods($vendorId) === 0) {
			return FALSE;
		}

		if($product->orderable and !empty($product->prices['salesPrice'])) {
			//if(empty($this->_currentMethod->button_for_login)) $user = JFactory::getUser();
			foreach ($this->methods as $this->_currentMethod) {

				if($this->_currentMethod->button_show == "1" ){
					$this->getPaymentCurrency($this->_currentMethod, true);
					vmdebug('getPaymentCurrency plgVmOnProductDisplayPayment',$this->_currentMethod->payment_currency);
					$this->addPaypalJSSDK('product-details', $this->_currentMethod);

					$productDisplay[] = $this->renderButtonsHtml('product-details');
				}

			}
		}

		return TRUE;
	}

	function plgVmDisplayLogin(VmView $view, &$html, $from_cart = FALSE) {

		// only to display it in the cart, not in list orders view
		if (!$from_cart) {
			return NULL;
		}

		$user = JFactory::getUser();
		if(!empty($user->id)){
			return;
		}
		//$vmPPOrderId = self::getvmPPOrderId();
		//if(!empty($vmPPOrderId)) return;

		$cart = VirtueMartCart::getCart();

		if($cart->_dataValidated){
			return;
		}
		if ($this->getPluginMethods($cart->vendorId) === 0) {
			return FALSE;
		}

		foreach ($this->methods as $this->_currentMethod) {

			$vmPPOrderId = self::getvmPPOrderId();
			if(!empty($vmPPOrderId)) return;

			if(empty($this->_currentMethod->button_for_login)) continue;
			//if(empty($this->_currentMethod->paypal_products->pui)) continue;  //leads to loop

			//if($cart->virtuemart_paymentmethod_id == $this->_currentMethod->virtuemart_paymentmethod_id) continue;
			$this->getPaymentCurrency($this->_currentMethod, true);


			$price = isset($cart->cartPrices['billTotal']) ? $cart->cartPrices['billTotal']:0.0;

			$this->addPaypalJSSDK('cart',$this->_currentMethod);
			$html .=  '<div id="paypal-button-login"></div>';
			$html .= '<div
        data-pp-message
        data-pp-message data-pp-placement="home"
        data-pp-placement="product"
        data-pp-style-layout="flex" data-pp-style-ratio="8x1"
        data-pp-amount="'.$price.'">
</div>';
			break;
			//$this->renderButtonsHtml($this->_currentMethod, 'cart');  //or checkout ??
		}

	}

/*	function plgVmOnCheckoutAdvertise($cart, &$payment_advertise) {

		if ($this->getPluginMethods($cart->vendorId) === 0) {
			return FALSE;
		}
		//if (!($selectedMethod = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id))) {
		//	return NULL;
		//}
		if (isset($cart->cartPrices['salesPrice']) && $cart->cartPrices['salesPrice'] <= 0.0) {
			return NULL;
		}

		foreach ($this->methods as $this->_currentMethod) {

			if($this->_currentMethod->payment_element != 'paypal_checkout') continue;

			$this->getPaymentCurrency($this->_currentMethod, true);

			//PayPalToken::getPayPalAccessToken($this->_currentMethod);

			$this->addPaypalJSSDK('cart','');
			$productDisplayHtml = 'Muuuh';
			/*$this->renderByLayout('button',
				array(
					'paypal' => $this->_currentMethod,
				)
			);
			$payment_advertise[] = $productDisplayHtml;

		}
	}*/


	/**
	 * Runs before login
	 * @param VirtueMartCart $cart
	 * @param $selected
	 * @param $htmlIn
	 */
	public function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected, &$htmlIn) {


		if($this->displayListFE ($cart, $selected, $htmlIn)){
			$idN = 'virtuemart_'.$this->_psType.'method_id';
			$addJsSDK = false;
			foreach ($this->methods as $this->_currentMethod) {

				//Fragwürdig
				//if(empty($this->_currentMethod->button_for_guests)) continue;

				if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {
					$this->getPaymentCurrency($this->_currentMethod, true);

					$pageType = 'cart';

					$htmlIn[$this->_psType][$this->_currentMethod->{$idN}] .=  $this->renderButtonsHtml($pageType);
					$this->addPaypalJSSDK($pageType,$this->_currentMethod);
				}
			}

		}

	}

	protected function renderPluginName ($method) {

		$text = vmText::_('VMPAYMENT_PAYPAL_PLUGIN_'.str_replace('-','_', strtoupper($method->paypal_products)));
		if(strpos('VMPAYMENT_PAYPAL_PLUGIN_', $text) !== FALSE){
			$text = $method->payment_name;
		}
		$pluginName = '<span class="' . $this->_type . '_name">' . $text . '</span>';
		if (!empty($method->payment_desc)) {
			$pluginName .= '<span class="' . $this->_type . '_description">' . $method->payment_desc . '</span>';
		}

		$this->_currentMethod = $method;
		$vmPPOrderId = $this->getvmPPOrderId();
		if( ($method->paypal_products == 'buttons' or $method->paypal_products == 'hosted-fields') and $method->button_show != '2' and !empty($vmPPOrderId)){
			$pluginName .=  ' '.vmText::sprintf('VMPAYMENT_PAYPAL_PPC_LOGGED_IN','COM_VIRTUEMART_ORDER_CONFIRM_MNU',$method->payment_name);
		}
		return $pluginName;

	}

	static $vmPPOrderId = '';
	static $vmPPOrderIdHash = '';

	function getCartHash(){
		$cart = VirtueMartCart::getCart();

		/*if(!isset($cart->cartPrices['salesPriceShipment'])){
			$cart = VirtueMartCart::getCart();
			$cart->prepareCartData();
			if(empty($cart->cartPrices['salesPriceShipment'])) $cart->cartPrices['salesPriceShipment'] = 0.0;
		}
		vmdebug('getCartHash ',$cart->cartPrices['salesPriceShipment']);*/
		$strHash = vmJsApi::safe_json_encode($cart->cartProductsData).$cart->virtuemart_paymentmethod_id.'S'.$cart->virtuemart_paymentmethod_id/*.'PS'.floatval($cart->cartPrices['salesPriceShipment'])*/;
		$h = hash('md5', $strHash);
		vmdebug('getCartHash '.$strHash);
		return $h;
	}

	function setvmPPOrderId($vmPPOrderId = ''){
		if($vmPPOrderId == ''){
			$vmPPOrderId = self::$vmPPOrderId;
		} else {
			self::$vmPPOrderId = $vmPPOrderId;
		}
		$sess = JFactory::getSession();
		$sess->set('vmPPOrderId', $vmPPOrderId,'vm');

		self::$vmPPOrderIdHash = $this->getCartHash();
		$sess->set('vmPPOrderIdHash', self::$vmPPOrderIdHash,'vm');
	}

	function getvmPPOrderId(){
		$sess = JFactory::getSession();
		self::$vmPPOrderId = $sess->get('vmPPOrderId',self::$vmPPOrderId,'vm');

		if(empty(self::$vmPPOrderId)) return '';

		$hash = $this->getCartHash();

		self::$vmPPOrderIdHash = $sess->get('vmPPOrderIdHash',self::$vmPPOrderIdHash,'vm');
		if($hash == self::$vmPPOrderIdHash){
			vmdebug('getvmPPOrderId '.self::$vmPPOrderId);
			return self::$vmPPOrderId;
		} else {
			vmdebug('getvmPPOrderId '.self::$vmPPOrderIdHash.' != '.$hash. ' Deleting '.self::$vmPPOrderId);
			self::emptyvmPPOrderId();

			return '';
		}

	}

	static function emptyvmPPOrderId(){

		vmTrace('emptyvmPPOrderId');
		$sess = JFactory::getSession();
		$sess->set('vmPPOrderId',null,'vm');
		$sess->set('vmPPOrderIdHash',null,'vm');
		self::$vmPPOrderId = '';
		self::$vmPPOrderIdHash = '';
		$sess = JFactory::getSession();
		$sess->set('vmpp',false,'vm');
	}


	function setPayPalDebug($currentMethod){
		if(!empty($currentMethod->paypal_debug)){
			//VmEcho::$_debug = 1;
			VmEcho::$logFileName = 'paypal_checkout';
			VmEcho::$logDebug = 1;
		}
	}

	static function setVmdebugPaypalError(){
		//VmEcho::$_debug = 1;
		VmEcho::$logFileName = 'paypal_checkout';
		VmEcho::$logDebug = 1;
	}

	function plgVmOnSelfCallFE($type, $name, &$render) {
		if ($type != $this->_type) {
			return;
		}
		if ($name != $this->_name) {
			return;
		}

		$task = vRequest::getCmd('task',false);
		//task=emptyOrderId
		if ($task == 'emptyOrderId'){
			self::emptyvmPPOrderId();
			$redirect = vRequest::get('redirect',false);
			if($redirect){
				$app = JFactory::getApplication();
				$app->redirect('index.php?option=com_virtuemart&view=cart');
			} else {
				return $render=1;
			}

		}

		$pmId = vRequest::getInt('pm', false);
		if($pmId){
			$this->_currentMethod = $this->getVmPluginMethod($pmId);
			$this->setPayPalDebug($this->_currentMethod);
		}


		vmdebug('PayPal Checkout, I am in plgVmOnSelfCallFE '.$task);
		if ($task == 'checkout'){
			PayPalCheckoutResponse::checkout();
		} //dev.stuprechtpro.local/VM4j3/index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=getUserInfo&pm=3&id=9P774773NK9525228
		else if($task == 'getUserInfo'){

			PayPalCheckoutResponse::getUserInfo($this, $render);

		} else if($task == 'createOrder'){

			PayPalCheckoutResponse::createOrder($this, $render);;

		} else if ($task == 'captureOrder'){

			PayPalCheckoutResponse::captureOrder($this, $render);

		} else if ($task == 'authorizeOrder'){

			PayPalCheckoutResponse::authorizeOrder($this, $render);

		} else if ($task == 'webhook'){
			vmdebug('PayPal in task webhook '.$task);
			PaypalResponseWebHooks::webhook($this, $render);

		} else if ($task == 'captureAndComplete'){

			$render = 'captureAndComplete '.self::$vmPPOrderId;
			/*$cart = VirtueMartCart::getCart();
			$cart->prepareCartData();*/
			self::getvmPPOrderId();
			if($virtuemart_order_id = PayPalOrder::captureOrder($this, self::$vmPPOrderId) ) {

				$this->finalizeOrder(false);

			} else {
				$render .= ' '.vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG');
				vmInfo($render);
				$app = JFactory::getApplication();
				$app->redirect('index.php?option=com_virtuemart&view=cart');
			}


		} else if ($task == 'ordercompleted'){

			$this->finalizeOrder();

		} else if ($task == 'checkDs') {
			PayPalCheckoutResponse::checkDs($this, $render);
		} else {
		 	vmdebug('PayPal checkout unknown task '.$task);
		 }

	}

	function finalizeOrder($storePayPalData = true){
		$cart = VirtueMartCart::getCart();
		$orderM = VmModel::getModel('orders');
		$order = $orderM->getOrder($cart->virtuemart_order_id);

		$this->_currentMethod = $this->getPluginMethod($cart->virtuemart_paymentmethod_id);
		PaypalResponseWebHooks::updateOrderStatus($this, $cart->virtuemart_order_id, $this->_currentMethod->status_success);

		//$cart->emptyCart();
		//plgVmPaymentPaypal_checkout::emptyvmPPOrderId();
		if($storePayPalData){
			$this->storePayPalData($order['details']['BT'], $this->_currentMethod);
		}

		//$this->completeOrder($cart,);
		$this->orderCompleted($order, $cart);

		$app = JFactory::getApplication();

		$orderDoneItemid = '';
		if(!empty($this->_currentMethod->orderDoneItemId)){
			$orderDoneItemid .= '&Itemid='.$this->_currentMethod->orderDoneItemId;
		}
		$app->redirect('index.php?option=com_virtuemart&view=cart&layout=orderdone'.$orderDoneItemid);
		return true;
	}

	function plgVmOnSelfCallBE($type, $name, &$render) {

		if ($name != $this->_name) {
			return;
		}

		$pmId = vRequest::getInt('pm');
		$this->_currentMethod = $this->getVmPluginMethod($pmId);
		$this->setPayPalDebug($this->_currentMethod);
		$task = vRequest::getCmd('task', false);

		//VmEcho::$echoDebug = 1;
		vmdebug('PayPal Checkout, I am in plgVmOnSelfCallBE ' . $task);
		if ($task == 'captureAuthorizedPayment'){

			PayPalCheckoutResponse::captureAuthorizedPayment($this, $render);

		} else if ($task == 'captureOrderByOrderId'){

			PayPalCheckoutResponse::captureOrderByOrderId($this, $render);

		} else if ($task == 'refundCapturedPayment'){
			PayPalCheckoutResponse::refundCapturedPayment($this, $render);
		} else if ($task == 'getAccessTokenFromAuthCode'){
			PayPalOnboarding::getAccessTokenFromAuthCode($this, $render);
		} else if ($task == 'getCredentials'){
			PayPalOnboarding::getCredentials($this, $render);
		} else if ($task == 'setCredentials'){
			PayPalOnboarding::setCredentials($this, $render);
		} else if ($task == 'disconnectMerchant'){
			PayPalOnboarding::disconnectMerchant($this, $render);
		} else {
			PayPalOnboarding::checkMerchant($this, $render);
		}

	}

	function plgVmConfirmedOrder($cart, $order){

		if (!($this->_currentMethod = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($this->_currentMethod->payment_element)) {
			return FALSE;
		}

		$this->setPayPalDebug($this->_currentMethod);
		if($this->_currentMethod->paypal_products == 'pui') {
			$cart->_blockConfirmedCheckout = true;
			$cart->BT['paypal_int_number'] = vRequest::getCmd('paypal_int_number');
			$cart->BT['phone_1'] = vRequest::getCmd('phone_1');
			$cart->BT['paypal_date_of_birth'] = vRequest::getCmd('paypal_date_of_birth');
			$cart->setCartIntoSession();
			$resp = PayPalOrder::createOrder($this, $cart, $order);
			if($resp) self::$vmPPOrderId = $resp->id;
			//Does not work? why?
			vmdebug('plgVmPaymentPaypal_checkout::$PPResult ',plgVmPaymentPaypal_checkout::$PPResult->body,plgVmPaymentPaypal_checkout::$Response);
			if(!empty(self::$vmPPOrderId)){
				if(!empty(plgVmPaymentPaypal_checkout::$Response->body->status) and plgVmPaymentPaypal_checkout::$Response->body->status == 'PENDING_APPROVAL'){

					$oData['order_status'] = $this->_currentMethod->status_confirmed;
					$oData['invoice_locked'] = '1';
					$orderM = VmModel::getModel('orders');
					$orderM->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $oData, true);
					vmInfo('VMPAYMENT_PAYPAL_PUI_AWAIT_EMAIL');
					$cart->_blockConfirmedCheckout = false;
					$this->orderCompleted($order, $cart);
					return true;
				} else {
					vmInfo('Checkout not completed.');
					vmdebug('PayPal Pui plgVmConfirmedOrder, no PENDING_APPROVAL');
					$cart->_confirmDone = FALSE;
					$cart->_dataValidated = FALSE;
					$cart->setCartIntoSession();
					return false;
				}
			} else {
				vmInfo('Checkout not completed.');
				vmdebug('PayPal Pui plgVmConfirmedOrder, no $vmPPOrderId');
				$cart->_confirmDone = FALSE;
				$cart->_dataValidated = FALSE;
				$cart->setCartIntoSession();
				return false;
			}
		} else if ($this->_currentMethod->paypal_products == 'buttons' or $this->_currentMethod->paypal_products == 'hosted-fields') {

			self::$vmPPOrderId = self::getvmPPOrderId();
			if(!empty(self::$vmPPOrderId)){

				//$hash = $this->getCartHash();
				//if($hash != self::$vmPPOrderIdHash){
					//Patch
					$body =  PayPalOrder::updateOrder($this,self::$vmPPOrderId, $cart, $order);
				//}


				$sucess = false;
				if(empty($body)){
					if($this->_currentMethod->paypal_intent == 'capture'){
						$sucess = PayPalOrder::captureOrder($this, self::$vmPPOrderId);
					} else if($this->_currentMethod->paypal_intent == 'authorize'){
						$sucess = PayPalOrder::authorizeOrder($this, self::$vmPPOrderId);
					}
					//$this->orderCompleted();
				}

				if(!$sucess){
					$cart->orderdoneHtml = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG');
					$cart->_confirmDone = FALSE;
					$cart->_dataValidated = FALSE;
					$cart->_blockConfirmedCheckout = true;
					$cart->setCartIntoSession();
					return false;
				}
			} else {
				$resp = PayPalOrder::createOrder($this, $cart, $order, true);
				vmdebug('Created order here '.self::$vmPPOrderId);
				if($resp) self::$vmPPOrderId = $resp->id;

				$bTask = vRequest::getCmd('btask','');
				if($bTask != 'captureOrder'){
					if( /* empty($this->_currentMethod->button_show) and */ !empty($resp->links)){
						foreach($resp->links as $link){
							if($link->rel == 'payer-action'){

								$redLink = $link->href;
								$app = JFactory::getApplication();
								$app->redirect($redLink, false);
							}
						}
					}
					//No redirect found, so show this
					$cart->orderdoneHtml = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG');
					$cart->_confirmDone = FALSE;
					$cart->_dataValidated = FALSE;
					$cart->_blockConfirmedCheckout = true;
					$cart->setCartIntoSession();
				}

			}

		} else {

			$cart->_blockConfirmedCheckout = true;
			$cart->setCartIntoSession();
			$resp = PayPalOrder::createOrder($this, $cart, $order);
			if($resp) self::$vmPPOrderId = $resp->id;
			if(!empty(self::$vmPPOrderId)){

				if(plgVmPaymentPaypal_checkout::$Response->body->status == 'PAYER_ACTION_REQUIRED'){

					if(!empty(plgVmPaymentPaypal_checkout::$Response->body->links)){
						$actionLink = '';
						foreach(plgVmPaymentPaypal_checkout::$Response->body->links as $l){
							if($l->rel == "payer-action"){
								$actionLink = $l->href;
								break;
							}
						}

						if(!empty($actionLink)){
							$app = JFactory::getApplication();
							$app->redirect($actionLink);
						}
					}

				}
			}

			if(plgVmPaymentPaypal_checkout::$Response->body->name == 'UNPROCESSABLE_ENTITY'){

				if(!empty(plgVmPaymentPaypal_checkout::$Response->body->details[0]->issue)){
					vmInfo('VMPAYMENT_PAYPAL_PLUGIN_'.plgVmPaymentPaypal_checkout::$Response->body->details[0]->issue);
				}
			}
			//$cart->_confirmDone = FALSE;
			//$cart->_dataValidated = FALSE;
			return false;

		}


		return true;
	}

	function orderCompleted($order = null, $cart = null, $orderId = null){
		if(!isset($cart))$cart = VirtueMartCart::getCart();

		$cart->emptyCart();
		self::emptyvmPPOrderId();

		VmEcho::$logDebug = 0;
		return true;
	}

	function storePayPalData($orderDetailsIn, $method){

		$this->setPayPalDebug($method);
		if(!is_object($orderDetailsIn) and !is_array($orderDetailsIn)){
			//vmTrace('storePayPalData my $orderDetailsIn as int '.$orderDetailsIn);
			$orderModel = VmModel::getModel('orders');
			$order = $orderModel->getOrder((int)$orderDetailsIn);
			$orderDetails = $order['details']['BT'];
			//vmdebug('storePayPalData my $order',$orderDetails->virtuemart_order_id);
		} else {
			$orderDetails = $orderDetailsIn;
		}

		//vmdebug('storePayPalData my ',$orderDetails->virtuemart_order_id, $orderDetailsIn);
//if(empty(self::$PPResult->body))

	//vmTrace('storePayPalData ');

		self::$PPResult->payment_name = $method->payment_name;
		self::$PPResult->virtuemart_order_id = $orderDetails->virtuemart_order_id; //$order['details']['BT']->virtuemart_order_id;
		self::$PPResult->order_number = $orderDetails->order_number; //$order['details']['BT']->order_number;
		self::$PPResult->virtuemart_paymentmethod_id = $orderDetails->virtuemart_paymentmethod_id; //$order['details']['BT']->virtuemart_paymentmethod_id;
		if(!empty(self::$PPResult->body) and is_object(self::$PPResult->body)){
			if(!empty(self::$PPResult->body->id))self::$PPResult->ppOrderId = self::$PPResult->body->id;
			self::$PPResult->body = vmJsApi::safe_json_encode(self::$PPResult->body);
		}

		$data = get_object_vars(self::$PPResult);
		$data['id'] = 0;
		if(empty($data['virtuemart_order_id']) or empty($data['virtuemart_paymentmethod_id'])){
			vmdebug('storePayPalData, There is something wrong ',$data);
			vmTrace('storePayPalData my pp result for orderid '.self::$PPResult->virtuemart_order_id, true, 4);
		} else {
			$this->storePSPluginInternalData($data, $this->_tablepkey, 0);
		}

	}
/*	
	function plgVmOnUpdateOrderPayment (&$order,$old_order_status,$inputOrder){

		//Load the method
		if (!($this->_currentMethod = $this->getVmPluginMethod($order->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}

		if (!$this->selectedThisElement($this->_currentMethod -> payment_element)) {
			return NULL;
		}

		//This does not work with partial refunds, we must disable it
	if($order->order_status == "R" and !empty($this->_currentMethod->allow_status_refunds)){

			PaypalPayment::refundCapturedPayment($this, $order->virtuemart_order_id);

			$this->storePayPalData($order, $this->_currentMethod);
			return true;
		}

	}
*/
	function getOverCart($name, $default = ''){
		$v = vRequest::getCmd($name,$default);

		if( (empty($name) or $name==$default) and !empty($this->cart->BT[$name])){
			$v = $this->cart->BT[$name];
		}
		return $v;
	}

	static function configuredButtons($_currentMethod, $pageType){
		$renderButtons = false;
		if( $_currentMethod->button_show != "2" ){
			if( $_currentMethod->button_show == "1" ){
				$renderButtons = true;
			} else if( $pageType=='cart' ) {
				$renderButtons = true;
			}
		}
		return $renderButtons;
	}

	function renderButtonsHtml ($pageType) {

		$html = '';
		//$renderBtProds = array('buttons','sofort');
		if($this->_currentMethod->paypal_products == 'buttons'){
			
			$renderButtons = self::configuredButtons($this->_currentMethod, $pageType);

			if($renderButtons){
				$html .= $this->renderByLayout('button',
					array(
						'method' => $this->_currentMethod
					)
				);
			}

		}

		if($this->_currentMethod->paypal_products == 'hosted-fields'){

			$creditCardAddress = new stdClass();
			$this->cart = VirtueMartCart::getCart();
			$creditCardAddress->card_holder_name = $this->getOverCart('card-holder-name','');
			if(empty($creditCardAddress->card_holder_name)){
				if(!empty($this->cart->BT['first_name'])) $creditCardAddress->card_holder_name = $this->cart->BT['first_name'];
				if(!empty($this->cart->BT['last_name'])) $creditCardAddress->card_holder_name .= ' '.$this->cart->BT['last_name'];
				$creditCardAddress->card_holder_name = trim($creditCardAddress->card_holder_name);
			}
			$creditCardAddress->street = $this->getOverCart('card-billing-address-street');
			if(empty($creditCardAddress->street) and !empty($this->cart->BT['address_1'])) $creditCardAddress->street = $this->cart->BT['address_1'];

			$creditCardAddress->unit = $this->getOverCart('card-billing-address-unit');

			$creditCardAddress->city = $this->getOverCart('card-billing-address-city');
			if(empty($creditCardAddress->city) and !empty($this->cart->BT['city'])) $creditCardAddress->city = $this->cart->BT['city'];

			$creditCardAddress->zip = $this->getOverCart('card-billing-address-zip');
			if(empty($creditCardAddress->zip) and !empty($this->cart->BT['zip'])) $creditCardAddress->zip = $this->cart->BT['zip'];

			$creditCardAddress->country = vRequest::getCmd('card-billing-address-country','');
			if( empty($creditCardAddress->country) and !empty($this->cart->BT['virtuemart_country_id'])){
				$creditCardAddress->country = VirtueMartModelCountry::getCountryFieldByID($this->cart->BT['virtuemart_country_id'],'country_2_code');
			}

			$creditCardAddress->state = vRequest::getCmd('card-billing-address-state','');
			if( empty($creditCardAddress->state) and !empty($this->cart->BT['virtuemart_state_id'])){
				$creditCardAddress->state = VirtueMartModelState::getStateFieldByID($this->cart->BT['virtuemart_state_id'],'state_2_code');
			}

			$html .= $this->renderByLayout('hosted',
				array( 'address' => $creditCardAddress )
			);
		}

		if(!empty($this->_currentMethod->pay_later_messages)){

			if($pageType == 'product-details'){
				$productM = VmModel::getModel('product');
				$id = vRequest::getInt('virtuemart_product_id',false);
				if($id){
					$product = $productM->getProduct($id);
					$price = $product->prices['salesPrice'];
				}
			} else {
				$cart = VirtueMartCart::getCart();
				$price = $cart->cartPrices['billTotal'];
				//vmdebug('my cart $price',$price);
			}

			//vmdebug('my product',$product);
			$html .= $this->renderByLayout('messages',
				array(
					'price' => $price,
					'paypal' => $this->_currentMethod,
				)
			);
		}

		if($this->_currentMethod->paypal_products == 'pui'){

			$sandboxBool = 'false';
			if($this->_currentMethod->sandbox){
				$sandboxBool = 'true';
			}

			$ppIntNumber = $this->getOverCart('paypal_int_number','+');
			$phone_1 = $this->getOverCart('phone_1','');
			$dob = $this->getOverCart('paypal_date_of_birth');
			/*$ppIntNumber = vRequest::getInt('paypal_int_number','+');
			if( (empty($ppIntNumber) or $ppIntNumber == '+') and !empty($cart->BT['paypal_int_number'])){
				$ppIntNumber = $cart->BT['paypal_int_number'];
			}

			$phone_1 = vRequest::getCmd('phone_1','');
			if(empty($phone_1) and !empty($cart->BT['phone_1'])){
				$phone_1 = $cart->BT['phone_1'];
			}

			$dob = vRequest::getCmd('paypal_date_of_birth');
			if(empty($dob)){
				if(!empty($cart->BT['paypal_date_of_birth'])){
					$dob = $cart->BT['paypal_date_of_birth'];
				} else if(!empty($cart->BT['birth_date'])){
					$dob = $cart->BT['birth_date'];
				}
			}*/
			$cart = VirtueMartCart::getCart();
			$nonce = PayPalOrder::getPuiNonce($this, $cart);//'md5',$this->getCartHash(). vmJsApi::safe_json_encode($cart->BT));

			$html .= $this->renderByLayout('pui',
				array(
					'sandboxBool' => $sandboxBool,
					'ppIntNumber' => $ppIntNumber,
					'phone_1' => $phone_1,
					'paypal_date_of_birth' => $dob,
					'pageType' => $pageType,
					'nonce' => $nonce,
				)
			);

		}

		return $html;
	}

	function addFunding($name, &$_currentMethod){

		if(!empty($_currentMethod->enable_funding)){

			if(is_array($this->_currentMethod->enable_funding)){
				if(!in_array($name,$this->_currentMethod->enable_funding)){
					$this->_currentMethod->enable_funding[] = $name;
				}
			} else if( $name != $this->_currentMethod->enable_funding){
				$this->_currentMethod->enable_funding = array($this->_currentMethod->enable_funding);
				$this->_currentMethod->enable_funding[] = $name;
			}

		} else {
			$_currentMethod->enable_funding[] = $name;
		}
	}

	/**
	 * @param $pageType product-listing, search-results, product-details, mini-cart, cart or checkout
	 * @return bool
	 */
	function addPaypalJSSDK($pageType, $method = null){

		static $added = false;

		if($added) return;
		$added = true;
		//vmTrace('addPaypalJSSDK '.$pageType);

		if($method == null){
			$method = $this->_currentMethod;
		}
		$cart = VirtueMartCart::getCart();

		/*if ($this->getPluginMethods($cart->vendorId) === 0) {
			return FALSE;
		}*/

		$vmPPOrderId = self::getvmPPOrderId();

		/*if(!empty($vmPPOrderId)){
			return '';
		}*/

		if($pageType!='product-details' and count($cart->products)== 0){
			return '';
		}

		$components = array();
		$products = array();
		$withLogin = 'false';
		$withButton = 'false';
		$user = JFactory::getUser();
		$selected = null;
		$jsExtra = '';
		$bt_styles= '';
		//We need this, because it can happen, that someone selected Advanced Creditcard but uses the button. So the JS needs to
		//differ the selected methodId and the button method id.
		$button_pm_id='';
		$vmPP = 'vmPP = new Object();';
		$debug=0;
		foreach ($this->methods as $_currentMethod) {

			if($_currentMethod->payment_element == 'paypal_checkout') {

				if($cart->virtuemart_paymentmethod_id == $_currentMethod->virtuemart_paymentmethod_id){
					$selected = $_currentMethod->paypal_products;
					$debug = $_currentMethod->paypal_debug;
				}

				if(!empty($_currentMethod->pay_later_messages)){
					$components['messages'] = 'messages';
				}

				if($_currentMethod->paypal_products == 'buttons'){

					$wButtonB = self::configuredButtons($_currentMethod,$pageType);
					if($wButtonB){
						$withButton = 'true';
					}

					$button_pm_id = $_currentMethod->virtuemart_paymentmethod_id;
					$components['buttons'] = 'buttons';

					if(empty($_currentMethod->button_for_login) or $pageType!='cart' or $cart->_dataValidated or !$user->guest){
						$withLogin = 'false';
					} else {
						$withLogin = 'true';
					}
					$products[$_currentMethod->paypal_products] = $_currentMethod->paypal_products;
					$bt_styles = 	'vmPPStyle = new Object();
					vmPPStyle.layout = "'.$_currentMethod->button_layout.'";
					vmPPStyle.color = "'.$_currentMethod->button_color.'";
					vmPPStyle.shape = "'.$_currentMethod->button_shape.'";
					vmPPStyle.label = "'.$_currentMethod->button_label.'";';
					$vmPP .= 'vmPP.bt_error_txt = "' . vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG') . '";';
					if( $wButtonB ){
						$vmPP .= 'vmPP.bt_checkout_txt = "0";';
					} else {
						$vmPP .= 'vmPP.bt_checkout_txt = "' . vmText::_('VMPAYMENT_PAYPAL_CHECKOUT_TXT') . '";';
					}

					if(!empty($_currentMethod->disable_funding)){
						if(is_array($_currentMethod->disable_funding)){
							$nvp['disable-funding'] = implode(',', $_currentMethod->disable_funding);
						} else if(!empty($_currentMethod->disable_funding)){
							$nvp['disable-funding'] = $_currentMethod->disable_funding;
						}
					}

					if(!empty($_currentMethod->enable_funding)){
						if(is_array($_currentMethod->enable_funding)){
							$nvp['enable-funding'] = implode(',', $_currentMethod->enable_funding);
						} else if(!empty($_currentMethod->enable_funding)){
							$nvp['enable-funding'] = $_currentMethod->enable_funding;
						}
					}

				}
				if($pageType == 'cart') {
					//$products[$_currentMethod->paypal_products] = $_currentMethod->paypal_products;
					/*if ($_currentMethod->paypal_products == 'buttons') {
						$products[$_currentMethod->paypal_products] = $_currentMethod->paypal_products;
					}*/
					if ($_currentMethod->paypal_products == 'pui') {
						$components['legal'] = 'legal';
						$vmPP .= 'vmPP.pui_checkout_txt = "' . vmText::_('VMPAYMENT_PAYPAL_PUI_CHECKOUT_TXT') . '";';
						$products[$_currentMethod->paypal_products] = $_currentMethod->paypal_products;

					}
					if ($_currentMethod->paypal_products == 'hosted-fields') {
						//$components['buttons'] = 'buttons';
						$components['hosted-fields'] = 'hosted-fields';
						$vmPP .= 'vmPP.hosted_txt = "' . vmText::_('VMPAYMENT_PAYPAL_HOSTED_FILL_DATA') . '";
						vmPP.hosted_error_txt = "' . vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG_HOSTED') . '";';
						$products[$_currentMethod->paypal_products] = $_currentMethod->paypal_products;
					}
				}
			}
		}

		$vmPP .= 'vmPP.debug = "'.(int)$debug.'";';

		//vmdebug('my enabled components and products', $components, $products);
		//If we have only PUI published and are on a product detail
		if(empty($components)){
			return false;
		}

		if(!isset($selected)) {
			$selected = ($cart->virtuemart_paymentmethod_id == $method->virtuemart_paymentmethod_id)? 1:0;
			vmdebug('PayPal checkout selected?',$selected);
		}

		//vmdebug('my components',$components);
		$sandbox = '';
		$sandboxBool = 'false';
		if(!empty($method->sandbox)){
			$sandbox = 'sandbox_';
			$sandboxBool = 'true';
		}

		//vmdebug('addPaypalJSSDK', $this->_currentMethod);
		//PayPalToken::getPayPalAccessToken($this->_currentMethod);

		$nvp = array();

		$nvp['client-id'] = $method->{$sandbox.'client_id'};

		$currencyM = VmModel::getModel('currency')->getCurrency($method->payment_currency);
		$nvp['currency'] = $currencyM->currency_code_3;

		if(!empty($method->debug)){
			$nvp['debug'] = $method->debug;
		}


		$nvp['components'] = implode(',',$components);

		$cart = VirtuemartCart::getCart();

		if( !empty($method->sandbox) and !empty($cart->BT['virtuemart_country_id']) ){
			$nvp['buyer-country'] = VirtueMartModelCountry::getCountry($cart->BT['virtuemart_country_id'])->country_2_code;
		}

		if($cart->_dataValidated and $pageType!='product-details'){
			$nvp['commit'] = 'true';
		} else {
			$nvp['commit'] = 'false';
		}




		//if( count($products) == 1 and isset($products['buttons']) ){
			$nvp['intent'] = strtolower($method->paypal_intent);
		//}

		$nvp['integration-date'] = '2023-08-08';

		/*if(!empty($method->merchant_id)){
			$nvp['merchant_id'] = $method->merchant_id;
		}*/

		$nvpUrl = '';
		foreach($nvp as $k=>$v){
			$nvpUrl .= '&'.$k.'='.$v;
		}
		$nvpUrl = substr($nvpUrl,1);


		//$html .=
		$client_token = null;
		if(isset( $products['hosted-fields'])){
			$client_token = PayPalToken::getPayPalClientToken($this);
		}

		//$nvp['nvpUrl'] = $nvpUrl;
		$vmPP .= "\n".'vmPP.nvpUrl = "https://www.paypal.com/sdk/js?'.$nvpUrl.'";'."\n";
		$attribs = $this->addJSScriptWithPayPalHeader($pageType, $nvpUrl, $client_token);
		$vmPP .= 'vmPP.attribs = '.vmJsApi::safe_json_encode($attribs).';';
		//$html .= '<script src="/VM4j3/plugins/vmpayment/paypal_checkout/assets/js/site.js" defer="" async=""></script>';

		vmJsApi::addJScript('/plugins/vmpayment/paypal_checkout/assets/js/site.js',false, false, true);

		//vmJsApi::addJScript('/plugins/vmpayment/paypal_checkout/assets/js/onready.js',false, true, true);

		if( (isset($products['buttons']) and empty($vmPPOrderId)) or isset($products['hosted-fields']) or isset($products['pui'])){


			//$withLegal = $this->_currentMethod->components == 'pui'? 'true':'false';

			$view = vRequest::getCmd('view',false);

			//if(empty($user->id) /*or empty($vmPPOrderId)*/){
			//	$task = 'getUserInfo';
			// } else

			if( !$cart->_dataValidated){
				$task = 'getUserInfo';
			} else {
				if(strtolower($method->paypal_intent) == 'capture'){
					$task = 'captureOrder';
				} else if(strtolower($method->paypal_intent) == 'authorize'){
					$task = 'authorizeOrder';
				} else {
					$task = 'createOrder';
				}
			}

			//$task = 'getUserInfo';

			//$withLogin = empty($this->_currentMethod->button_for_guests)?'false':'true';
			$j = $bt_styles."
			".$vmPP.'
    vmPP.view = "'.$view.'";
	vmPP.button_pm_id = "'.$button_pm_id.'";
	vmPP.methodId = "'.$cart->virtuemart_paymentmethod_id.'";
	vmPP.selected = "'.$selected.'";
	vmPP.task = "'.$task.'";
	vmPP.products = "'.implode(',',$products).'";
	vmPP.vmPPOrderId = "'.$vmPPOrderId.'";
	vmPP.withLogin = "'.$withLogin.'";
	vmPP.withButton = "'.$withButton.'";
	vmPP.BtConfirmTxt = "'.vmText::_('COM_VIRTUEMART_ORDER_CONFIRM_MNU').'";
			
	jQuery(document).ready(function() {
			killmeReady = setInterval(function(){
                    
                    if(typeof Virtuemart !== "undefined" && typeof Virtuemart.onReadyPP !== "undefined"){
                        if(vmPP.debug == "1")console.log("onReadyPP loaded");
                        
                        clearInterval(killmeReady);
                        clearTimeout(finalHit);
                        Virtuemart.onReadyPP();
                    } else {
                        console.log("onReadyPP not loaded yet ");
                    }
                },50);
	        finalHit = setTimeout(function(){
                    clearInterval(killmeReady);
                    console.log("Loading killmeReady killed");
            },5000);
	});
';
			vmJsApi::addJScript('ppButtonRender',$j, true);
		}

	}

	function addJSScriptWithPayPalHeader($pageType, $nvpUrl, $client_token){
		$document = JFactory::getDocument();

		$options = array();
		$attribs = array('data-partner-attribution-id' => plgVmPaymentPaypal_checkout::BNCODE);

		if(isset($client_token)){
			$attribs['data-client-token'] = $client_token;
		}

		//$attribs['data-namespace'] = $namespace;
		$attribs['async'] = 'async';
		//$attribs['defer'] = 'defer';
		$attribs['mime'] = "text/javascript";
		$attribs['data-page-type'] = $pageType;

		$document->addScript( 'https://www.paypal.com/sdk/js?' .$nvpUrl, $options, $attribs );

		return $attribs;
	}

	function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {
		return $this->onStoreInstallPluginTable ($jplugin_id);
	}

	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg) {

		if($this->OnSelectCheck ($cart)){
			$this->_currentMethod = $this->getPluginMethod($cart->virtuemart_paymentmethod_id);
			self::$vmPPOrderId = self::getvmPPOrderId();
			if(self::$vmPPOrderId){
				$cart = VirtueMartCart::getCart();
				if(!$cart->_dataValidated){
					$cart->checkoutData(false,true);
				}
			}
			return true;
		} else {
			return NULL;
		}

		return $this->OnSelectCheck ($cart);
	}

	public function plgVmOnSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
	}

	//This functions are like that in classic paypal already
	function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices, &$paymentCounter) {
		return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
	}

	function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id, $orderDetails) {

		if (!$this->selectedThisByMethodId($payment_method_id)) {
			return NULL; // Another method was selected, do nothing
		}
		if (!($this->_currentMethod = $this->getVmPluginMethod($payment_method_id))) {
			return FALSE;
		}
		if (!($payments = $this->_getPaypalInternalData($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return 'nothing found';
		}
		//vmdebug('My PayPal Payment data',$payments);
		$html = '<table class="adminlist table">' . "\n";

		foreach($payments as $entr){

			$entr->body = vmJsApi::safe_json_decode($entr->body);

			$html .= $this->getHtmlHeaderBE ();
			if(!empty($entr->payment_name))$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_PAYMENT_NAME', $entr->payment_name);

			if(isset($entr->body->seller_payable_breakdown)){
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_PAYMENT_REFUND', $entr->body->amount->value);
				if(isset($entr->body->custom_id)) $html .= $this->getHtmlRowBE ('COM_VIRTUEMART_CUSTOM_ID', $entr->body->custom_id);
				if(isset($entr->body->invoice_id)) $html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_INVOICE_ID', $entr->body->invoice_id);
				if(isset($entr->body->note_to_payer)) $html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_NOTE_TO_PAYER', $entr->body->note_to_payer);
				if(isset($entr->body->create_time)) $html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_CREATION_TIME', $entr->body->create_time);
				if(isset($entr->body->update_time)) $html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_UPD_TIME', $entr->body->update_time);
			} else {
				if(isset($entr->body->id)){
					$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_PPID', $entr->body->id);
				}
			}

			if(isset($entr->body->intent)){

				$button = '';
				if(strtoupper($entr->body->intent) == 'AUTHORIZE'){
					$url = 'index.php?option=com_virtuemart&view=plugin&type=vmpayment&name=paypal_checkout&pm='.$this->_currentMethod->virtuemart_paymentmethod_id.'&task=captureAuthorizedPayment&id='.$entr->body->id.'&virtuemart_order_id='.$virtuemart_order_id;
					$button .= '<a class="btn btn-primary showcart floatright" href="'.$url.'">Capture Authorized order</a>';
				}
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_INTENT', $entr->body->intent.$button);

			}

			if(isset($entr->body->status)){
				$button = '';
				/*if($entr->body->status == 'PAYER_ACTION_REQUIRED'){
					$url = 'index.php?option=com_virtuemart&view=plugin&type=vmpayment&name=paypal_checkout&pm='.$this->_currentMethod->virtuemart_paymentmethod_id.'&task=captureOrderByOrderId&id='.$entr->body->id.'&virtuemart_order_id='.$virtuemart_order_id;
					$button .= '<a class="btn btn-primary showcart floatright" href="'.$url.'">Capture order</a>';
				}*/
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_PAYMENT_STATUS', $entr->body->status.$button);
			}

			if(isset($entr->body->purchase_units)){
				$main = reset($entr->body->purchase_units);
				if(isset($main->amount)){
					$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_CURRENCY_CODE', $main->amount->currency_code);
					$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_VALUE', $main->amount->value);
				}
				/*if($main->payee){
					$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_EMAIL_PAYEE', $main->payee->email_address);
					$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_MERCHANT_ID', $main->payee->merchant_id);
				}*/
				if(isset($main->description)){
					$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_DESCRIPTION', $main->description);
					$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_CUSTOM_ID', $main->custom_id);
				}
				if(isset($main->shipping)){
					if(isset($main->shipping) and isset($main->shipping->name) and isset($main->shipping->name->full_name)){
						$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_SHIPPING_NAME', $main->shipping->name->full_name);
					}
					//$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_MERCHANT_ID', $main->payee->merchant_id);
				}

			}

			if(isset($entr->body->name)){
				$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_PAYMENT_NAME', $entr->body->name);
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_PAYMENT_MESSAGE', $entr->body->message);
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_PAYMENT_DETAILS', vmEcho::varPrintR($entr->body->details));
			}

			if(isset($entr->body->created_on)){
				$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_CREATED_ON', $main->body->created_on);
				$html .= $this->getHtmlRowBE ('COM_VIRTUEMART_CREATED_BY', $main->body->created_by);
			}

			if(isset($entr->body->payment_reference)){
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_USE_ACCOUNT');
				$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_USE_REFERENCE', $entr->body->payment_reference);
				if(!empty($entr->body->deposit_bank_details)){
					if(isset($entr->body->deposit_bank_details->bank_name))
						$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_USE_BANK_NAME', $entr->body->deposit_bank_details->bank_name);
					if(isset($entr->body->deposit_bank_details->account_holder_name))
						$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_USE_BANK_ACCOUNTHOLDER', $entr->body->deposit_bank_details->account_holder_name);
					if(isset($entr->body->deposit_bank_details->iban))
						$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_USE_BANK_IBAN', $entr->body->deposit_bank_details->iban);
					if(isset($entr->body->deposit_bank_details->bic))
						$html .= $this->getHtmlRowBE ('VMPAYMENT_PAYPAL_USE_BANK_BIC', $entr->body->deposit_bank_details->bic);

				}

			}
		}
		$html .= '</table>' . "\n";

		return $html;
	}

	public function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $payment_method_id, &$payment_name) {

		if (!$this->selectedThisByMethodId($payment_method_id)) {
			return NULL; // Another method was selected, do nothing
		}

		$payment_name = $this->getOrderMethodNamebyOrderId ($virtuemart_order_id);

		if (!($this->_currentMethod = $this->getVmPluginMethod($payment_method_id))) {
			return FALSE;
		}

		//$this->onShowOrderFE ($virtuemart_order_id, $payment_method_id, $payment_name);

		if($this->_currentMethod->paypal_products == 'pui'){
			if (!($payments = $this->_getPaypalInternalData($virtuemart_order_id))) {
				// JError::raiseWarning(500, $db->getErrorMsg());
				return 'nothing found';
			}

			foreach($payments as $payment){
				if(!empty($payment->body)){
					//if(strpos('payment_reference',$payment->body)!==FALSE){
						$bankData = vmJsapi::safe_json_decode($payment->body);
						if(isset($bankData->payment_reference)){
							$body = $this->renderByLayout('puimail',
								array(
									'order' => false,
									'vendor' => false,
									'pui_data' => false,
									'payment_reference' => $bankData->payment_reference,
									'deposit_bank_details' => $bankData->deposit_bank_details,
								)
							);
							$payment_name .= '<br>'.$body;
							break;
						}
					//}

				}
			}

		}

	}

	/**
	 * @param   int $virtuemart_order_id Give either the id
	 * @param string $order_number or the order_number
	 * @return mixed|string
	 */
	public function _getPaypalInternalData($virtuemart_order_id, $order_number = '') {

		$db = JFactory::getDBO();
		$q = 'SELECT * FROM `' . $this->_tablename . '` WHERE ';

		if (!empty($virtuemart_order_id)) {
			$q .= " `virtuemart_order_id` = '" . $virtuemart_order_id . "'";
		} else if(!empty($order_number)){
			$q .= " `order_number` = '" . $order_number . "'";
		}
		$db->setQuery($q);
		if (!($payments = $db->loadObjectList())) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		return $payments;
	}


	function plgVmOnShowOrderPrintPayment ($order_number, $method_id) {
		return $this->onShowOrderPrint ($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}

	function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table) {
		$this->setOnTablePluginParams($name, $id, $table);
	}

	function OnStoreInstallPluginTable () {
		//$this->setOnTablePluginParams ($name, $id, $table);

		if(empty($this->_currentMethod)){
			//if(empty($table->virtuemart_paymentmethod_id)){
				$id = vRequest::getInt('virtuemart_paymentmethod_id', vRequest::getInt('pm', 0));
				if(is_array($id)) $id = reset($id);
			/*} else {
				$id = $table->virtuemart_paymentmethod_id;
			}*/
			$this->_currentMethod = $this->getVmPluginMethod($id);
			//vmdebug('my $this->_currentMethod',$this->_currentMethod);
		}
		//unset($this->_currentMethod->_db);
//vmdebug('plgVmSetOnTablePluginParamsPayment'.$id,$this->_currentMethod);

		$sandbox = 'sandbox_';
		$sand = 'sandbox';
		if($this->_currentMethod->paypal_developer == "1"){
			vmdebug('PP developer ALKTIV');
			if($this->_currentMethod->sandbox == "0" ){
				$sandbox = '';
				$sand = '';
			} else {
				//$this->_currentMethod->sandbox == "1";
			}
		} else {
			$sandbox = '';
			$sand = '';
			//$this->_currentMethod->sandbox == "0";
		}


		$render = false;
		$publishedOK = PayPalOnboarding::checkMerchant($this,$render);
		$methods = $this->getPluginMethods($this->_currentMethod->virtuemart_vendor_id, false, false);
		vmdebug('my $methods',$methods);
		if($methods>1){
			$fieldsToClone = array($sandbox.'paypal_merchant_email', $sandbox.'client_id', $sandbox.'client_secret', $sandbox.'paypal_merchant_id');
				//$sandbox.'webHookId', $sandbox.'webHookUrl');

			$alreadyEntered = array();

			foreach($fieldsToClone as $name){
				if(!empty($this->_currentMethod->{$name})){
					$alreadyEntered[$name] = $this->_currentMethod->{$name};
				}
			}

			if(count($alreadyEntered)<4){
				$storeOnCurrentMethod = true;
				foreach($this->methods as $method){

					if($method->virtuemart_paymentmethod_id == $this->_currentMethod->virtuemart_paymentmethod_id) continue;

					foreach($fieldsToClone as $name){
						if(!empty($method->{$name})){
							if(empty($alreadyEntered[$name])) $alreadyEntered[$name] = $method->{$name};
						}
					}
					if(count($alreadyEntered)==4){
						break;
					}

				}
			} else {
				//$storeOnCurrentMethod = false;
			}

			vmdebug('my $alreadyEntered',$alreadyEntered);
			if(count($alreadyEntered)==3 or count($alreadyEntered)==4){
				$pModel = VmModel::getModel('paymentmethod');
				foreach($this->methods as $method){

					/*if(!$storeOnCurrentMethod and $method->virtuemart_paymentmethod_id == $this->_currentMethod->virtuemart_paymentmethod_id){
						continue;
					}*/

					$payment = $pModel->getPayment($method->virtuemart_paymentmethod_id);
					foreach($alreadyEntered as $name => $val){
						$payment->{$name} = $val;
					}

					if($method->paypal_products == 'pui' ) {
						$payment->payment_name = vmText::_('VMPAYMENT_PAYPAL_PLUGIN_PUI');
						$payment->pay_later_messages = '0';
						$payment->paypal_intent = 'capture';
						$payment->shipping_preference = 'SET_PROVIDED_ADDRESS';
						$payment->withBreakdown = "1";
						$payment->button_show = "2";
						$payment->button_for_login = "0";
						$payment->allow_status_refunds= "0";
					} else if($method->paypal_products != 'buttons' /*and $method->paypal_products != 'hosted-fields'*/){

						$payment->payment_name = vmText::_('VMPAYMENT_PAYPAL_PLUGIN_'.str_replace('-','_', strtoupper($method->paypal_products)));

						//$payment->payment_name = vmText::_('VMPAYMENT_PAYPAL_PLUGIN_'.strtoupper($method->paypal_products));
						//if(empty($payment->enable_funding)) $payment->enable_funding = 'sofort';
						$payment->pay_later_messages = '0';
						if($method->paypal_products != 'hosted-fields'){
							$payment->paypal_intent = 'capture';
						}

						$payment->shipping_preference = 'SET_PROVIDED_ADDRESS';
						//$payment->withBreakdown = "0";
						$payment->button_show = "2";
						$payment->button_for_login = "0";
						$payment->allow_status_refunds= "0";
						if($method->paypal_products == 'blik' ){
							$payment->currency_id = shopFunctions::getCurrencyIDByName('PLN');
						} else {
							$payment->currency_id = shopFunctions::getCurrencyIDByName('EUR');
						}
					} else {
						//$payment->payment_name = vmText::_('VMPAYMENT_PAYPAL_PLUGIN_'.str_replace('-','_', strtoupper($method->paypal_products)));
						$payment->payment_name = vmText::_('VMPAYMENT_PAYPAL_PLUGIN_BUTTONS');
					}

					if($method->virtuemart_paymentmethod_id == $this->_currentMethod->virtuemart_paymentmethod_id ){
						if($this->_currentMethod->paypal_developer == "0") {
							if( !$publishedOK ){
								$payment->published = 0;
								vmInfo('VMPAYMENT_PAYPAL_PLUGIN_UNPUBLISHED_REQU');
							}
							if($this->_currentMethod->sandbox == "1"){
								unset($payment->bearToken);
								unset($this->_currentMethod->bearToken);
								$payment->sandbox = 0;
							}
						}
					}
					//unset($payment->payment_name);
					unset($payment->payment_desc);
					unset($payment->slug);
					$payment->_update = true;
					$payment->_xParams = 'payment_params';
					//vmdebug('my payment store', $payment->virtuemart_paymentmethod_id, $payment->sandbox_client_id);


					$payment->bindChecknStore($payment->getProperties());

				}
			} else {

				//if developer mode is disabled sandbox is not available, so disabling developer must disable sandbox
				if($this->_currentMethod->paypal_developer == "0" and $this->_currentMethod->sandbox == "1"){
					$data = $this->_currentMethod->getProperties();

					unset($data['bearToken']);
					unset($this->_currentMethod->bearToken);
					$data['sandbox'] = 0;
					$this->_currentMethod->bindChecknStore($data);//*/
				}

			}
		}

		if($this->_currentMethod->virtuemart_paymentmethod_id){
			if(!empty($this->_currentMethod->{$sandbox.'client_id'})){
				PayPalWebHooks::checkWebHooks($this);
			}

			if(empty($this->_currentMethod->{$sandbox.'paypal_merchant_email'})){
				vmInfo('Merchant email misssing, not needed but you should enter it to know which email is in use');
			}

			if(empty($this->_currentMethod->{$sandbox.'client_id'})){
				vmInfo($sand.'Client id misssing');
			}
			if(empty($this->_currentMethod->{$sandbox.'client_secret'})){
				vmInfo($sand.'Client secret misssing');
				//return false;
			}
		}


		if($this->_currentMethod->paypal_products == 'pui' ){

			if(empty($this->_currentMethod->pui_instructions)){
				vmInfo('Pay Upon Invoice instructions are missing, but mandatory');
			}
		}

		$SQLfields = $this->getTableSQLFields(); vmdebug('my $SQLfields',$SQLfields);
		if(empty($SQLfields)) return false;

		$loggablefields = $this->getTableSQLLoggablefields();
		$tablesFields = array_merge($SQLfields, $loggablefields);

		$keys = array('id'=>'PRIMARY KEY (`id`)');
		if(isset($tablesFields['virtuemart_order_id'])){
			$keys['virtuemart_order_id'] = 'KEY (`virtuemart_order_id`)';
		}
		$update[$this->_tablename] = array($tablesFields, $keys, array());
		vmdebug('my update',$update);
		$updater = new GenericTableUpdater();
		return $updater->updateMyVmTables($update);
	}

	function redirectToCart ($msg = NULL) {
		if (!$msg) {
			$msg = vmText::_('VMPAYMENT_PAYPAL_ERROR_TRY_AGAIN');
		}
		$this->customerData->clear();
		$app = JFactory::getApplication();
		$app->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&Itemid=' . vRequest::getInt('Itemid'), false), $msg);
	}

}