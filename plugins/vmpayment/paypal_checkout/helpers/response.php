<?php

class PayPalCheckoutResponse {

	static function checkout(){
		vmdebug('vmPPCC in checkout');
		$cart = VirtueMartCart::getCart();
		$cart->prepareCartData();
		if($cart->checkoutData(false)){
			echo 1;
		} else {
			echo 0;
		}
		die;
	}

	static function checkDs($plugin, &$render){

		vmdebug('PayPal I am in checkDs');
		$pmId = vRequest::getInt('pm');
		$plugin->_currentMethod = $plugin->getVmPluginMethod($pmId);

		//plgVmPaymentPaypal_checkout::$vmPPOrderId = vRequest::getCmd('id',0);
		$plugin->getvmPPOrderId();

		$app = JFactory::getApplication();

		if($ppPSourceData = PayPalOrder::showOrderDetails($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId, '?fields=payment_source') ) {

			if(empty($ppPSourceData->payment_source) or empty($ppPSourceData->payment_source->card) or empty($ppPSourceData->payment_source->card->authentication_result)){
				$render = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG');
				$app->redirect('index.php?option=com_virtuemart&view=cart');
			}
			$EnrollmentStatus = '';
			$authenticationStatus ='';
			$liabilityShift = '';

			$authentication_result = $ppPSourceData->payment_source->card->authentication_result;
			if(!empty($authentication_result->liability_shift)){
				$liabilityShift = $authentication_result->liability_shift;
			}
			if(!empty($authentication_result->three_d_secure)){
				if(!empty($authentication_result->three_d_secure->enrollment_status)){
					$EnrollmentStatus = $authentication_result->three_d_secure->enrollment_status;
				}
				if(!empty($authentication_result->three_d_secure->enrollment_status)){
					$authenticationStatus = $authentication_result->three_d_secure->enrollment_status;
				}
			}
			vmdebug('My $EnrollmentStatus '.$EnrollmentStatus.'  $authenticationStatus '.$authenticationStatus.' $liabilityShift'.$liabilityShift);

			if(     ($EnrollmentStatus =='Y' and $authenticationStatus =='Y' and $liabilityShift == 'POSSIBLE')
				or  ($EnrollmentStatus =='Y' and $authenticationStatus =='A' and $liabilityShift == 'POSSIBLE')
				or  ($EnrollmentStatus =='N' and $authenticationStatus =='' and $liabilityShift == 'NO')
				or  ($EnrollmentStatus =='U' and $authenticationStatus =='' and $liabilityShift == 'NO')
				or  ($EnrollmentStatus =='B' and $authenticationStatus =='' and $liabilityShift == 'NO')
			){

				if(strtolower($plugin->_currentMethod->paypal_intent)=='capture'){
					//only capture if intent capture
					if($virtuemart_order_id = PayPalOrder::captureOrder($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId) ) {
						$render = 1;
					} else {
						$render = '';
					}
				} else {
					if($virtuemart_order_id = PayPalOrder::authorizeOrder($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId) ) {
						$render = 1;
					} else {
						$render = '';
					}
				}


			}
		}


	}

	static function getUserInfo($plugin, &$render){
		//vmEcho::$logDebug = 1;
		vmdebug('PayPal I am in get UserInfo');
		$pmId = vRequest::getInt('pm');
		$plugin->_currentMethod = $plugin->getVmPluginMethod($pmId);

		/*$orderM = VmModel::getModel('orders');
		$cart = VirtueMartCart::getCart();
		$cart->prepareCartData();
		$orderId = $orderM->createOrderFromCart($cart);

		$order = $orderM->getOrder($orderId);
		plgVmPaymentPaypal_checkout::$vmPPOrderId = PayPalOrder::createOrder($plugin, $cart, $order);*/
		//$vmPPOrderId = plgVmPaymentPaypal_checkout::getvmPPOrderId();
		plgVmPaymentPaypal_checkout::$vmPPOrderId = vRequest::getCmd('id',0);


		$plugin->setvmPPOrderId();
		if($ppOrderData = PayPalOrder::showOrderDetails($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId) ) {
			vmdebug('PayPal I am in get UserInfo',$ppOrderData);
			if(     $ppOrderData->status == 'APPROVED'
				/*and $ppOrderData->payment_source->paypal->account_status == 'VERIFIED'*/){

				$cart = VirtuemartCart::getCart();
				$source = $ppOrderData->payment_source->paypal;

				if(empty($cart->BT['email']) and !empty($source->email_address) ){
					$cart->BT['email'] = $source->email_address;
					vmdebug('PayPal getUserInfo write email');
				}

				if(empty($cart->BT['phone_1']) and !empty($source->phone_number->national_number) ){
					$cart->BT['phone_1'] = $source->phone_number->national_number;
					vmdebug('PayPal getUserInfo write phone_1');
				}

				if(empty($cart->BT['first_name']) and !empty($source->name->given_name) ){
					$cart->BT['first_name'] = $source->name->given_name;
					vmdebug('PayPal getUserInfo write first_name');
				}
				if(empty($cart->BT['last_name']) and !empty($source->name->surname) ){
					$cart->BT['last_name'] = $source->name->surname;
					vmdebug('PayPal getUserInfo write last_name');
				}
				if(empty($cart->BT['virtuemart_country_id']) and !empty($source->address->country_code) ){
					$cart->BT['virtuemart_country_id'] = ShopFunctions::getCountryIDByName($source->address->country_code);
					vmdebug('PayPal getUserInfo write virtuemart_country_id');
				}

				if(!empty($ppOrderData->purchase_units)){
					$punits = reset($ppOrderData->purchase_units);
					vmdebug('PayPal getUserInfo found purchase_units[0]->shipping');
					if(!empty( $punits->shipping)){
						$shipping = $punits->shipping;
						vmdebug('PayPal getUserInfo write Address by purchase_units[0]->shipping');
						if(empty($cart->BT['first_name']) and empty($cart->BT['last_name'])and !empty($shipping->name->full_name) ){
							$split = explode(' ',$shipping->name->full_name);
							vmdebug('PayPal getUserInfo write names by split',$split);
							if(count($split) == 2){
								$cart->BT['first_name'] = $split[0];
								$cart->BT['last_name'] = $split[1];
							} else {
								$cart->BT['last_name'] = $split[0];
							}
						}

						if(!empty($shipping->address)){
							vmdebug('PayPal getUserInfo write Address by !empty($shipping->address)');
							if(empty($cart->BT['address_1']) and !empty($shipping->address->address_line_1) ){
								$cart->BT['address_1'] = $shipping->address->address_line_1;
								vmdebug('PayPal getUserInfo write address_1');
							}
							if(empty($cart->BT['city'])) {
								if( !empty($shipping->address->admin_area_2) ){
									$cart->BT['city'] = $shipping->address->admin_area_2;
									vmdebug('PayPal getUserInfo write city by admin area 2');
								} else if( !empty($shipping->address->admin_area_1) ){
									$cart->BT['city'] = $shipping->address->admin_area_1;
									vmdebug('PayPal getUserInfo write city by admin area 1');
								}
							}

							if(empty($cart->BT['zip']) and !empty($shipping->address->postal_code) ){
								$cart->BT['zip'] = $shipping->address->postal_code;
								vmdebug('PayPal getUserInfo write zip');
							}
							if(empty($cart->BT['virtuemart_country_id']) and !empty($shipping->address->country_code) ){
								$cart->BT['virtuemart_country_id'] = ShopFunctions::getCountryIDByName($shipping->address->country_code);
							}
						}
					}
				}
				$cart->setCartIntoSession();
				$obj = new stdClass();
				$obj->id = plgVmPaymentPaypal_checkout::$vmPPOrderId;
				$render = $obj;
			}
		}
		vmEcho::$logDebug = 0;
	}

	static function createOrder($plugin,&$render){
		//vmEcho::$logDebug = 1;

		//vmEcho::$logDebug = 0;
		$cart = VirtueMartCart::getCart();
		$cart->prepareCartData();

		$paymentId = vRequest::getInt('pm');

		if($paymentId != $cart->virtuemart_paymentmethod_id and !$cart->setPaymentMethod(false, false, $paymentId)){
			vmdebug('PayPal Checkout createOrder Failed to set the payment method ');
			//return 0;
		}

		$bTask = vRequest::getCmd('btask','');

		vmdebug('vmPPCC Response in create Order '.$bTask);
		$obj = new stdClass();

		if($bTask=='getUserInfo'){
			vmdebug('vmPPCC in createOrder and getUserInfo');

			$plugin->_currentMethod = $plugin->getVmPluginMethod($paymentId);

			plgVmPaymentPaypal_checkout::$vmPPOrderId = PayPalOrder::createOrderFromCart($plugin, $cart);

			$plugin->setvmPPOrderId();

			if(!empty(plgVmPaymentPaypal_checkout::$vmPPOrderId)){

				$obj->id = plgVmPaymentPaypal_checkout::$vmPPOrderId;
				$render = $obj;
			}
			/*if(PaypalIdentity::getUserInfo($plugin) ) {

			}*/
			vmEcho::$logDebug = 0;
		} else {
			if($cart->checkoutData(false)){

				$cart->_confirmDone = true;
				if($id = $cart->confirmedOrder()){

					//if(!empty(plgVmPaymentPaypal_checkout::$vmPPOrderId)){
						$obj = new stdClass();
						$obj->id = plgVmPaymentPaypal_checkout::$vmPPOrderId;
						vmdebug('order confirmed '.plgVmPaymentPaypal_checkout::$vmPPOrderId);
						$render = $obj;

					//}
					//echo $id; die;
					//vmdebug('order confirmed ');
					//PayPalOrder::createOrder($plugin, $cart->orderDetails);
					/*$data = array('orderId' =>  $cart->orderDetails->order_number);
					return $cart->orderDetails->order_number;*/
				}

				//die('Confirmed');
			} else {
				$obj->id = 0;
				$render = $obj;
			}

		}

	}

	static function checkAndUpdateOrder($plugin){
		if($ppOrderData = PayPalOrder::showOrderDetails($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId) ) {
			vmdebug('vmPPCC in checkAndUpdateOrder with showOrderDetails');
			if(!empty($ppOrderData->purchase_units)){
				$purchase_unit = reset($ppOrderData->purchase_units);
				$virtuemart_order_id = self::getvmOrderIdByPPOrderId($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId);
				$orderModel = VmModel::getModel('orders');
				$order = $orderModel->getOrder($virtuemart_order_id);

				$secureTotal = round($order['details']['BT']->order_shipment + $order['details']['BT']->order_shipment_tax,2);
				foreach($order['items'] as $key=>$oItem){
					$secureTotal += round($oItem->product_basePriceWithTax,2) * $oItem->product_quantity;
				}
				vmdebug('vmPPCC in captureOrder with showOrderDetails my old and new values ',$purchase_unit->amount->value,$secureTotal);
				if($purchase_unit->amount->value!=$secureTotal){
					PayPalOrder::updateOrder($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId, $ppOrderData, $order);
					//return false;
				}
			}
		}
	}

	static function captureOrder($plugin, &$render){

		//VmEcho::$logDebug = 1;
		vmdebug('vmPPCC response in captureOrder');
		$pmId = vRequest::getInt('pm');
		$plugin->_currentMethod = $plugin->getVmPluginMethod($pmId);

		plgVmPaymentPaypal_checkout::$vmPPOrderId = vRequest::getCmd('id',0);
		if(!empty(plgVmPaymentPaypal_checkout::$vmPPOrderId)){
			$plugin->setvmPPOrderId(plgVmPaymentPaypal_checkout::$vmPPOrderId);
		} else {
			plgVmPaymentPaypal_checkout::$vmPPOrderId =$plugin->getvmPPOrderId();
		}


		//self::checkAndUpdateOrder($plugin);

		if($virtuemart_order_id = PayPalOrder::captureOrder($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId) ) {
			$render = 1;
			//echo '1';
			//die;
		} else {
			$render = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG');
		}

	}

/*	static function captureOrderByOrderId($plugin, &$render){
		VmEcho::$logDebug = 1;
		vmdebug('vmPPCC in captureOrderByOrderId');
		$pmId = vRequest::getInt('pm');
		$plugin->_currentMethod = $plugin->getVmPluginMethod($pmId);

		plgVmPaymentPaypal_checkout::$vmPPOrderId = vRequest::getCmd('id',0);

		if(!empty(plgVmPaymentPaypal_checkout::$vmPPOrderId)){
			$plugin->setvmPPOrderId(plgVmPaymentPaypal_checkout::$vmPPOrderId);
		} else {
			plgVmPaymentPaypal_checkout::$vmPPOrderId =$plugin->getvmPPOrderId();
		}

		//show Order details
		$url = PayPalOrder::getOrdersUrl($plugin->_currentMethod);

		vmdebug('my url',$url);
		$data = array();
		$resp = PayPalToken::sendCURLDefaultHeader($plugin, $url.'/'.plgVmPaymentPaypal_checkout::$vmPPOrderId, '');


		vmdebug('captureOrderByOrderId',$resp);
	}
*/
	static function authorizeOrder($plugin, &$render){

		//vmEcho::$logDebug = 1;
		vmdebug('vmPPCC in authorizeOrder');
		$pmId = vRequest::getInt('pm');
		$plugin->_currentMethod = $plugin->getVmPluginMethod($pmId);

		plgVmPaymentPaypal_checkout::$vmPPOrderId = vRequest::getCmd('id',0);
		$plugin->setvmPPOrderId();

		//self::checkAndUpdateOrder($plugin);

		if($virtuemart_order_id = PayPalOrder::authorizeOrder($plugin, plgVmPaymentPaypal_checkout::$vmPPOrderId) ) {
			$render = 1;
			//echo '1';
			//die;
		} else {
			$render = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG_AUTH');
		}

	}

	/**
	 * Backend functions
	 */

	/**
	 * @param $plugin
	 * @param $render
	 */
	static function captureAuthorizedPayment($plugin, &$render){
		vmdebug(' in captureAuthorizedPayment');
		//VmEcho::$logDebug = 0;


		//$vmPPOrderId = vRequest::getCmd('id',0);
		$virtuemart_order_id = vRequest::getInt('virtuemart_order_id');
		//$ppAuthorisationId = $plugin->getPPAuthorizationId($virtuemart_order_id);
		if(PaypalPayment::captureAuthorizedPayment($plugin, $virtuemart_order_id) ) {
			$render = 1;

		} else {
			$render = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG_AUTH_CAPTURE');
		}

		//$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($orderId);

		$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$virtuemart_order_id);
	}

	static function refundCapturedPayment($plugin, &$render){
		vmdebug(' in refundCapturedPayment');
		//VmEcho::$logDebug = 0;
		$pmId = vRequest::getInt('pm');
		$plugin->_currentMethod = $plugin->getVmPluginMethod($pmId);

		$virtuemart_order_id = vRequest::getInt('virtuemart_order_id');

		//$vmPPOrderId = vRequest::getCmd('id',0);

		if(PaypalPayment::refundCapturedPayment($plugin, $virtuemart_order_id) ) {
			$render = 1;
		} else {
			$render = vmText::_('VMPAYMENT_PAYPAL_SMTHING_WENT_WRONG_REFUND');
		}

		//$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($orderId);
		$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$virtuemart_order_id);
	}

}