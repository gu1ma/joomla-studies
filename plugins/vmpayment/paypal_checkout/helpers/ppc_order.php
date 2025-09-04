<?php

/**
 *
 * Paypal checkout payment plugin
 *
 * @author Max Milbers
 * @version $Id: ppc_order.php
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

class PayPalOrder {

	static function getOrdersUrl($method){
		return PayPalToken::getUrl($method).'/v2/checkout/orders';
	}

	static function createExperienceContext($plugin, $cart, $capture = false){
		$exp_contxt = new stdClass();
		$exp_contxt->payment_method_preference = 'IMMEDIATE_PAYMENT_REQUIRED';
		$exp_contxt->payment_method_selected = 'PAYPAL';
		$exp_contxt->locale = vmLanguage::$currLangTag;
		$exp_contxt->landing_page = 'LOGIN';
		if(empty($plugin->_currentMethod->shipping_preference)){
			if(VirtueMartCart::getCartWeight($cart)>0.0){
				$exp_contxt->shipping_preference = 'SET_PROVIDED_ADDRESS';
			} else {
				$exp_contxt->shipping_preference = 'NO_SHIPPING';   //virtual good 'NO_SHIPPING'
			}
		} else {
			$exp_contxt->shipping_preference = (string)$plugin->_currentMethod->shipping_preference;
		}

		if($exp_contxt->shipping_preference == 'SET_PROVIDED_ADDRESS'){
			$address = $cart->getST();
			if(count($address)>3){
				$exp_contxt->shipping_preference = 'SET_PROVIDED_ADDRESS';
			} else {
				$exp_contxt->shipping_preference = 'GET_FROM_FILE';
			}
		}

		//$view = vRequest::getCmd('view','0');
		if($cart->_dataValidated){
			$exp_contxt->user_action = 'PAY_NOW';
			//vmdebug('PPC ExpContext user_action = PAY_NOW');
		} else {
			$exp_contxt->user_action = 'CONTINUE';
			//vmdebug('PPC ExpContext user_action = CONTINUE');
		}
		$task = 'ordercompleted';
		if($capture){
			$task = 'captureAndComplete&pm='.$plugin->_currentMethod->virtuemart_paymentmethod_id;
		}

		$exp_contxt->return_url = JURI::root(false).'index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task='.$task;
		$exp_contxt->cancel_url = JURI::root(false).'index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=emptyOrderId&redirect=1&pm='.$plugin->_currentMethod->virtuemart_paymentmethod_id;
		//$exp_contxt->cancel_url = JURI::root(false).'index.php?option=com_virtuemart&view=cart';
		//vmdebug('createExperienceContext ',$exp_contxt,vmJsApi::safe_json_encode($exp_contxt));
		return $exp_contxt;
	}

	static function createOrderFromCart($plugin, $cart){
		$currentMethod = $plugin->_currentMethod;

		$plugin->setPayPalDebug($currentMethod);

		$url = self::getOrdersUrl($currentMethod);

		$reference_id = 'default';    //can be used for update of an order, not given, just "default" we can mainly ignore it for now
		$plugin->getPaymentCurrency($currentMethod, $cart->paymentCurrency);
		$currency_code_3 = shopFunctions::getCurrencyByID($currentMethod->payment_currency, 'currency_code_3');
		//$email_currency = $plugin->getEmailCurrency($currentMethod);
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($cart->cartPrices['billTotal'],$currentMethod->payment_currency);

		$amount = new stdClass();
		$amount->currency_code = $currency_code_3;
		$amount->value = (string)round((float)$totalInPaymentCurrency['value'],2);

		vmdebug('createOrderFromCart $totalInPaymentCurrency = '.$totalInPaymentCurrency['value'].'  discount '.round((float)$cart->cartPrices['discountAmount'],2));

		$shipmentValue = 0.0;

		if(!empty($cart->virtuemart_shipmentmethod_id)){

			$address = $cart->getST();
			if(count($address)>3){
				vmdebug('Create order with address ',$address);
				$shipping = new stdClass();
				$name = new stdClass();
				self::createShippingObj($name, $shipping, (object) $address);
			}

		}


		if($currentMethod->withBreakdown){

			$breakdown = new stdClass();

			$item_total = new stdClass();
			$discount = new stdClass();

			$items = array();

			$discount->currency_code = $item_total->currency_code = $currency_code_3;
			$discount->value = (string)abs(round((float)$cart->cartPrices['discountAmount'],2));
			$item_total->value = (string) (round((float)$totalInPaymentCurrency['value'],2) + abs(round((float)$cart->cartPrices['discountAmount'],2)));

			$secureTotal = 0.0;
			//$secureTotalTax = round($cart->cartPrices['shipmentTax'],2);
			foreach( $cart->products as $key=>$product){

				$item = new stdClass();
				$item->name = $product->product_name;
				$item->description = $product->product_sku . VirtueMartModelCustomfields::CustomsFieldOrderDisplay($item,'FE', true);
				$item->quantity = $product->quantity;
				$item->unit_amount = new stdClass();
				$item->unit_amount->currency_code = $currency_code_3;

				//vmdebug('createOrderFromCart $product->prices',$product->prices);
				$item->unit_amount->value = (string) round($product->prices['priceBeforeTax'],2);

				$tax = new stdClass();
				$tax->currency_code = $currency_code_3;
				$tax->value = (string) round((float)$product->prices['taxAmount'],2);
				$item->tax = $tax;

				$items[] = $item;

				//$secureTotalTax += round($tax->value,2) * $product->quantity;;
				$secureTotal += round($product->prices['priceBeforeTax'],2) * $product->quantity;
			}

			$shipmentValue = 0.0;
			if(!empty($cart->virtuemart_shipmentmethod_id)){
				$amountS = new stdClass();
				$amountS->currency_code = $currency_code_3;
				$shipmentValue = !empty($cart->cartPrices['salesPriceShipment'])? round($cart->cartPrices['salesPriceShipment'],2): "0.0";  //Exception; always with tax shipmentValue + shipmentTax
				$amountS->value = (string) $shipmentValue;
				$breakdown->shipping = $amountS;
			}
			$item_total->value = (string) (round($secureTotal, 2) /*++ round($cart->cartPrices['salesPriceShipment'],2)*/);
			$discount->value = (string) round(abs(round((float)$totalInPaymentCurrency['value'],2) - round((float)$shipmentValue,2) - round($item_total->value,2) - round((float)$cart->cartPrices['taxAmount'] ,2)),2 );
vmdebug('my discount is '.round((float)$totalInPaymentCurrency['value'],2).' - '.round((float)$shipmentValue,2).' - '.round($item_total->value,2).' - '.round((float)$cart->cartPrices['taxAmount'] ,2).' = '.$discount->value);

			$breakdown->item_total = $item_total;
			$breakdown->discount = $discount;

			$tax_total = new stdClass();
			$tax_total->currency_code = $currency_code_3;
			//vmdebug('createOrderFromCart $product->prices',$cart);
			$tax_total->value = (string)round((float)$cart->cartPrices['taxAmount'] ,2);    //Not billTaxAmount has the tax amount of shipping in it
			$breakdown->tax_total = $tax_total;

			$amount->breakdown = $breakdown;

			$hashing = $items;
		} else {
			$items = array();
			$hashing = $amount;
		}



		//2 fach länder code nutzen
		$vendor = VmModel::getModel('vendor')->getVendor($plugin->_currentMethod->virtuemart_vendor_id);

		$data = new stdClass();
		$data->intent = strtoupper($plugin->_currentMethod->paypal_intent);

		$tempOrderId = substr(hash('md5', vmJsApi::safe_json_encode($hashing).time()),0,15); //$order['details']['BT']->order_number
		$purchase_units = array( 'reference_id' => $reference_id,
			'amount'    => $amount,
			'items'     => $items,
			'description' => $tempOrderId. ' ' .$vendor->vendor_name,  //optional Textstring
			/*'custom_id' => $tempOrderId*/) ;  //nachfragen ob das hier damit gemeint ist;);
		if(isset($shipping)){
			$purchase_units['shipping'] = $shipping;
		}

		$data->purchase_units =  array($purchase_units);

		$experience_context = self::createExperienceContext($plugin, $cart);
		$data->payment_source = new stdClass();
		$data->payment_source->paypal = new stdClass();
		$data->payment_source->paypal->experience_context = $experience_context;
		//$data->payment_source = array(  'paypal' => array('experience_context' => $experience_context));//*/

		//VmEcho::$logDebug = 1;
		$body = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);

		vmdebug('my $content in createOrder',$data,$body);
		//plgVmPaymentPaypal_checkout::$PPResult->body = $body;
		if(isset($body->id)){

			vmdebug('createOrderFromCart returning OrderId '.$body->id);
			//VmEcho::$logDebug = 0;
			return $body->id;
		} else {
			return false;
		}

	}

	/**
	 * Nach create order kommt capture
	 * @param $plugin
	 * @param $currentMethod
	 * @param $order
	 */
	static function createOrder($plugin, &$cart, $order, $capture = false){

		$currentMethod = $plugin->_currentMethod;
		$plugin->setPayPalDebug($currentMethod);
		$url = self::getOrdersUrl($currentMethod);

		$reference_id = 'default';    //can be used for update of an order, not given, just "default" we can mainly ignore it for now
		$plugin->getPaymentCurrency($currentMethod, $order['details']['BT']->payment_currency_id);
		$currency_code_3 = shopFunctions::getCurrencyByID($currentMethod->payment_currency, 'currency_code_3');
		//$email_currency = $plugin->getEmailCurrency($currentMethod);
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$currentMethod->payment_currency);

		$amount = new stdClass();
		$amount->currency_code = $currency_code_3;
		$amount->value = (string)round((float)$totalInPaymentCurrency['value'],2);

vmdebug('createOrder $totalInPaymentCurrency = '.$totalInPaymentCurrency['value'].'  discount '.round((float)$order['details']['BT']->order_discount,2));


		$shipmentValue = 0.0;
		$breakdown = new stdClass();
		if(!empty($order['details']['BT']->virtuemart_shipmentmethod_id)){
			$shipping = new stdClass();
			$name = new stdClass();

			if(!empty($order['details']['BT']->STsameAsBT)){
				$address = $order['details']['BT'];
			} else {
				$address = $order['details']['ST'];
			}
			//vmdebug('my order',$order['details']);
			self::createShippingObj($name, $shipping, $address);

		}


		if($currentMethod->withBreakdown){

			$item_total = new stdClass();
			$discount = new stdClass();

			$items = array();

			$discount->currency_code = $item_total->currency_code = $currency_code_3;
			$discount->value = (string)abs(round((float)$order['details']['BT']->order_discount,2));
			$item_total->value = (string) (round((float)$totalInPaymentCurrency['value'],2) + abs(round((float)$order['details']['BT']->order_discount,2)));

			$secureTotal = 0.0;
			$secureTotalTax = 0.0;//$order['details']['BT']->order_shipment_tax;
			foreach($order['items'] as $key=>$oItem){

				$item = new stdClass();
				$item->name = $oItem->order_item_name;
				$item->description = $oItem->order_item_sku . VirtueMartModelCustomfields::CustomsFieldOrderDisplay($item,'FE', true);
				$item->quantity = $oItem->product_quantity;
				$item->unit_amount = new stdClass();
				$item->unit_amount->currency_code = $currency_code_3;
				//vmdebug('createOrder $oItem',$oItem);
				/*if(empty($oItem->product_basePriceWithTax)){
					$itemPrice = $oItem->product_priceWithoutTax;
				} else {
					$itemPrice = $oItem->product_basePriceWithTax;
				}*/

				$item->unit_amount->value = (string) round($oItem->product_item_price,2);
				if($currentMethod->paypal_products == 'pui'){
					foreach($order['calc_rules'] as $rule){
						if($rule->virtuemart_order_item_id == $oItem->virtuemart_order_item_id){
							$item->tax_rate = (string) round((float)$rule->calc_value,2);
						}
					}
					if(empty($item->tax_rate)) $item->tax_rate = '0';
					$item->category = "PHYSICAL_GOODS"; //Only physical goods are allowed for PUI
				}

				//Maybe not just PUI
				$tax = new stdClass();
				$tax->currency_code = $currency_code_3;
				$tax->value = (string) round((float)$oItem->product_tax,2);
				$item->tax = $tax;

				$items[] = $item;
				$secureTotalTax += round((float)$oItem->product_tax,2) * $oItem->product_quantity;;
				$secureTotal += round($oItem->product_item_price,2) * $oItem->product_quantity;
			}

			$shipmentValue = 0.0;
			if(!empty($order['details']['BT']->virtuemart_shipmentmethod_id)){
				$amountS = new stdClass();
				$amountS->currency_code = $currency_code_3;
				$shipmentValue = round($order['details']['BT']->order_shipment + $order['details']['BT']->order_shipment_tax,2);
				$amountS->value = (string) $shipmentValue;

				$breakdown->shipping = $amountS;
			}

			$item_total->value = (string) (round($secureTotal, 2) /*++ round($cart->cartPrices['salesPriceShipment'],2)*/);
			$discount->value = (string) round( abs(round((float)$totalInPaymentCurrency['value'],2) - round((float)$shipmentValue,2) - round($item_total->value,2) - $secureTotalTax) ,2);

			$breakdown->item_total = $item_total;
			$breakdown->discount = $discount;

			$tax_total = new stdClass();
			$tax_total->currency_code = $currency_code_3;
			$tax_total->value = (string)round((float)$order['details']['BT']->order_tax,2); //Not order_billTaxAmount has the shipping tax in it
			$breakdown->tax_total = $tax_total;

			$amount->breakdown = $breakdown;
		}

		//2 fach länder code nutzen
		$vendor = VmModel::getModel('vendor')->getVendor($plugin->_currentMethod->virtuemart_vendor_id);

		$data = new stdClass();

		$purchase_unit = array( 'reference_id' => $reference_id,
			'amount'    => $amount,
			'description' => $order['details']['BT']->order_number. ' ' .$vendor->vendor_name,  //optional Textstring
			'custom_id' => $order['details']['BT']->order_number) ;  //nachfragen ob das hier damit gemeint ist;);

		if($currentMethod->withBreakdown){
			$purchase_unit['items'] = $items;
		}

		$data->payment_source = new stdClass();
		vmdebug('Create Order my product ',$currentMethod->paypal_products);
		if($currentMethod->paypal_products == 'pui') {
			$data->intent = 'CAPTURE';
			$data->processing_instruction = "ORDER_COMPLETE_ON_PAYMENT_APPROVAL";
			$pay_upon_invoice_context = self::createExperienceContextPUI($plugin, $order);

			$data->payment_source->pay_upon_invoice = $pay_upon_invoice_context;


			$invM = VmModel::getModel('invoice');
			$invoiceNumberDate = array();
			$order['details']['BT']->invoice_locked = 0;
			$invM->getExistingIfUnlockedCreateNewInvoiceNumber($order['details']['BT'], $invoiceNumberDate, $order['details']['BT']->order_create_invoice_pass);
			$purchase_unit['invoice_id'] = $invoiceNumberDate[0];


			vmdebug('PayPal createOrder Invoice_id generated ' . $purchase_unit['invoice_id']);
		} else if($currentMethod->paypal_products == 'buttons' or $currentMethod->paypal_products == 'hosted-fields'){
			$data->intent = strtoupper($plugin->_currentMethod->paypal_intent);
			$experience_context = self::createExperienceContext($plugin, $cart, $capture);
			$data->payment_source->paypal = new stdClass();
			$data->payment_source->paypal->experience_context = $experience_context;
		} else {

			if(in_array($currentMethod->status_success,VmConfig::get('inv_os',array('C')))){
				$invM = VmModel::getModel('invoice');
				$invoiceNumberDate = array();
				$order['details']['BT']->invoice_locked = 0;
				$invM->getExistingIfUnlockedCreateNewInvoiceNumber($order['details']['BT'], $invoiceNumberDate, $order['details']['BT']->order_create_invoice_pass);
				$purchase_unit['invoice_id'] = $invoiceNumberDate[0];
				vmdebug('PayPal createOrder Invoice_id generated ' . $purchase_unit['invoice_id']);
			}

			$data->intent = 'CAPTURE';

			if($currentMethod->paypal_products != 'mybank'){
				$purchase_unit['reference_id'] = self::getPuiNonce($plugin, $cart);
			}
			$data->payment_source = new stdClass();

			$paymentSource = $currentMethod->paypal_products;

			$data->payment_source->{$paymentSource} = new stdClass();
			$data->payment_source->{$paymentSource}->country_code = VirtueMartModelCountry::getCountry( $order['details']['BT']->virtuemart_country_id)->country_2_code;

			$name = '';
			if(!empty($order['details']['BT']->first_name) ){
				$name = $cart->BT['first_name'];
			}
			if(!empty($order['details']['BT']->last_name) ){
				$name .= ' '.$order['details']['BT']->last_name;
			}

			$data->payment_source->{$paymentSource}->name = trim($name);
			if($paymentSource == 'sofort' or $paymentSource == 'p24'){
				$data->payment_source->{$paymentSource}->email = $order['details']['BT']->email;
			}

			if($paymentSource == 'ideal'){
				//	$data->payment_source->{$paymentSource}->bic = 'INGBNL2A';  //Must be with Inputfield like pui, but optional
			}

			$data->processing_instruction = "ORDER_COMPLETE_ON_PAYMENT_APPROVAL";

			$data->application_context = new stdClass();
			$data->application_context->locale = vmLanguage::$currLangTag;

			//We need here ordercompleted, to clear the cart for a successfull transaction
			$data->application_context->return_url = vmURI::getURI()->getScheme().'://'.vmURI::getURI()->getHost().JURI::root(true).'/index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=ordercompleted';
			//$data->application_context->return_url = vmURI::getURI()->getScheme().'://'.vmURI::getURI()->getHost().JURI::root(true).'/index.php?option=com_virtuemart&view=cart&layout=orderdone';
			$data->application_context->cancel_url = vmURI::getURI()->getScheme().'://'.vmURI::getURI()->getHost().JURI::root(true).'/index.php?option=com_virtuemart&view=cart';
		}


		if(isset($shipping)){
			$purchase_unit['shipping'] = $shipping;
		}

		$data->purchase_units =  array($purchase_unit);

		//self::convertCurrencySetString($currentMethod, $data);

		if($currentMethod->paypal_products == 'pui'){
			$extraHeader = array();
			$nonce = self::getPuiNonce($plugin, $cart);
			$extraHeader['PayPal-Client-Metadata-Id'] = $nonce;
			$resp = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data, $extraHeader);
		} else {
			$resp = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);
		}
		//vmdebug('createOrder plgVmPaymentPaypal_checkout::$PPResult ',plgVmPaymentPaypal_checkout::$PPResult->body);
		$plugin->storePayPalData($order['details']['BT'], $plugin->_currentMethod);

		//plgVmPaymentPaypal_checkout::$PPResult->body = $body;
		if(!empty($resp->error)) {
			vmdebug('!empty($body->error)',$resp);
			$cart->_blockConfirmedCheckout = true;

			$msg = '';
			$adminMsg = '';
			if(!empty($resp->body->name)){
				if($resp->body->name == 'UNPROCESSABLE_ENTITY'){
					if(!empty($resp->body->details[0]->issue)){
						if($resp->body->details[0]->issue == 'PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED' or
							$resp->body->details[0]->issue == 'PAYMENT_SOURCE_DECLINED_BY_PROCESSOR'){
							$msg = vmText::_($resp->body->details[0]->issue);
							$adminMsg = vmText::_($resp->body->details[0]->issue);
						} else {
							$adminMsg = vmText::_($resp->body->details[0]->issue);
							$adminMsg .= '<br />'.vmText::_($resp->body->details[0]->description);
						}
						unset($resp->body->details[0]);
					}
					if(!empty($resp->body->message)){
						$adminMsg .= '<br />message: '.$resp->body->message;
						unset($resp->body->message);
					}
					if(!empty($resp->body->debug_id)){
						$adminMsg .= '<br />debug_id: '.$resp->body->debug_id;
						unset($resp->body->debug_id);
					}
					unset($resp->body->name);
					if(empty($resp->body->details)){
						unset($resp->body->details);
					}
				}
				//$msg .= $resp->body->name.': ';
			}

			if(!empty($resp->body->links)){
				unset($resp->body->links);
			}

			plgVmPaymentPaypal_checkout::setVmdebugPaypalError();
			VmError($adminMsg. VmEcho::varPrintR($resp->body), $msg);
			vmdebug('Error createOrder '.$msg,$resp);
			//}

			return 0;
		} else
		if(isset($resp->id)){

			vmdebug('createOrder returning OrderId '.$resp->id);
			//PUI part
			/*if(!empty($resp->status) and $resp->status == 'PENDING_APPROVAL'){
				$cart->emptyCart();
				plgVmPaymentPaypal_checkout::emptyvmPPOrderId();
				$oData['order_status'] = 'U';
				$orderM = VmModel::getModel('orders');
				$orderM->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $oData, false);
				vmInfo('Checkout completed, await email with instructions');
			}*/
			//vmdebug('createOrder plgVmPaymentPaypal_checkout::$PPResult ',plgVmPaymentPaypal_checkout::$PPResult->body);
			//VmEcho::$logDebug = 0;
			$plugin->setvmPPOrderId($resp->id);
			return $resp;
		} else {
			vmdebug('createOrder no OrderId ',$resp);
			return false;
		}

	}

	static function convertCurrencySetString($currentMethod,&$data){

		if(!empty($data->purchase_units[0])){

			$cCurId = $currentMethod->payment_currency;
			$paymentCurrency = CurrencyDisplay::getInstance();

			$purUnit = &$data->purchase_units[0];
			if(!empty($purUnit->amount->value)){
				$purUnit->amount->value = $paymentCurrency->convertCurrencyTo($cCurId, $purUnit->amount->value);
				if(!empty($purUnit->breakdown)){
					foreach($purUnit->breakdown as $name=>$item){
						if(!empty($item->value)) {
							//$item->value = (string)$paymentCurrency->convertCurrencyTo($cCurId, $item->value);
							$data->purchase_units[0]->breakdown->{$name}->value = (string)$paymentCurrency->convertCurrencyTo($cCurId, $item->value);
						}
					}
				}
			}

			if(!empty($purUnit->items)){
				foreach($purUnit->items as $item){
					if(!empty($item->unit_amount->value)){
						$item->unit_amount->value = (string)$paymentCurrency->convertCurrencyTo($cCurId, $item->unit_amount->value);
					}
					if(!empty($item->tax->value)){
						$item->tax->value = (string)$paymentCurrency->convertCurrencyTo($cCurId, $item->tax->value);
					}
				}
			}
		}

	}

	static function getPuiNonce($plugin, $cart){

		$addressToHash = array('PUI');
		if(!empty($cart->BT['address_1'])) $addressToHash['address_1'] = $cart->BT['address_1'];
		if(!empty($cart->BT['city'])) $addressToHash['city'] = $cart->BT['city'];
		if(!empty($cart->BT['zip'])) $addressToHash['zip'] = $cart->BT['zip'];
		if(!empty($cart->BT['virtuemart_country_id'])) $addressToHash['virtuemart_country_id'] = $cart->BT['virtuemart_country_id'];
		$nonce = hash('md5',$plugin->getCartHash(). vmJsApi::safe_json_encode($addressToHash));
		vmdebug('getPuiNonce',$nonce);
		return $nonce;
	}

	static function createExperienceContextPUI($plugin, $order){

		$orderDetails = $order['details']['BT'];

		$pui_context = new stdClass();
		$pui_context->name = new stdClass();
		$pui_context->name->given_name = $orderDetails->first_name;
		$pui_context->name->surname = $orderDetails->last_name;

		$pui_context->email = $orderDetails->email;

		$pui_context->birth_date = vRequest::getCmd('paypal_date_of_birth');
		if(empty($pui_context->birth_date)){
			if(!empty($orderDetails->birth_date) and $orderDetails->birth_date!='0000-00-00') {
				$pui_context->birth_date = $orderDetails->birth_date;
			}
		}

		$pui_context->phone = new stdClass();
		//This is a bit problematic, either the merchant provides a dropdown, or imho better idea, a mapping, which sets this by the country given in the address.
		//A foreign number may anyway prevent this type of payment anyways.
		$pui_context->phone->national_number = vRequest::getCmd('phone_1');
		if(empty($pui_context->phone->national_number)){
			if(!empty($orderDetails->phone_1)) {
				$pui_context->phone->national_number = $orderDetails->phone_1;
			}
		}

		$pui_context->phone->country_code = ltrim(vRequest::getInt('paypal_int_number','+'),'+');

		$pui_context->billing_address = new stdClass();
		$pui_context->billing_address->address_line_1 = $orderDetails->address_1;
		$pui_context->billing_address->country_code = VirtueMartModelCountry::getCountry( $orderDetails->virtuemart_country_id)->country_2_code;
		vmdebug('createExperienceContextPUI ',$orderDetails->virtuemart_country_id, $pui_context->billing_address->country_code);
		$pui_context->billing_address->admin_area_2 = $orderDetails->city;
		if(!empty($orderDetails->virtuemart_state_id)){
			$pui_context->billing_address->admin_area_1 = shopFunctions::getStateByID($orderDetails->virtuemart_state_id, 'state_2_code');
		}
		$pui_context->billing_address->postal_code = $orderDetails->zip;

		$exp_contxt = new stdClass();

		$vendorM = VmModel::getModel('vendor');
		$vendor = $vendorM->getVendor($plugin->_currentMethod->virtuemart_vendor_id);
		//$vendorM->addImages($vendor);
		$exp_contxt->locale = vmLanguage::$currLangTag;
		$exp_contxt->brand_name = $vendor->vendor_store_name;
		$exp_contxt->logo_url = 'https://'.vmURI::getURI()->getHost().JURI::root(true).'/'.$vendor->file_url_thumb;
		$exp_contxt->customer_service_instructions = array($plugin->_currentMethod->pui_instructions);
		$pui_context->experience_context = $exp_contxt;

		return $pui_context;
	}

	static function createShippingObj(&$name, &$shipping, $orderDetails){

		$fullname = array();
		if(!empty($orderDetails->company)) $fullname[] = $orderDetails->company;
		if(!empty($orderDetails->title)) $fullname[] = $orderDetails->title;
		if(!empty($orderDetails->first_name)) $fullname[] = $orderDetails->first_name;
		if(!empty($orderDetails->last_name)) $fullname[] = $orderDetails->last_name;

		$name->full_name = implode(' ',$fullname);
		$shipping->name = $name;

		$address = new stdClass();
		if(!empty($orderDetails->address_1)) $address->address_line_1 = $orderDetails->address_1;
		if(!empty($orderDetails->virtuemart_country_id)) $address->country_code = VirtueMartModelCountry::getCountry( $orderDetails->virtuemart_country_id)->country_2_code;

		if(!empty($orderDetails->city)) $address->admin_area_2 = $orderDetails->city; //always needed
		//$address->admin_area_1 is the state
		if(!empty($orderDetails->virtuemart_state_id)){
			$address->admin_area_1 = shopFunctions::getStateByID($orderDetails->virtuemart_state_id, 'state_2_code');
		}

		if(!empty($orderDetails->zip)) $address->postal_code = $orderDetails->zip;
		$shipping->address = $address;

	}

	static function updateOrder($plugin, $vmPPOrderId, $cart, $order){


		$plugin->setPayPalDebug($plugin->_currentMethod);

		$ppOrder = self::showOrderDetails($plugin, $vmPPOrderId);
		vmdebug('updateOrder $order["details"]["BT"]',$ppOrder,$order['details']['BT']);
		$data = array();

		$shipment = new stdClass();
		if(empty($ppOrder->purchase_units[0]->shipping->address)){
			$shipment->op = 'add';
		} else{
			$shipment->op = 'replace';
		}
		$shipment->path = "/purchase_units/@reference_id=='default'/shipping/address";

		$shipping = new stdClass();
		$name = new stdClass();

		if(!empty($order['details']['BT']->STsameAsBT)){
			$address = $order['details']['BT'];
		} else {
			$address = $order['details']['ST'];
		}

		self::createShippingObj($name, $shipping, $address);
		$shipment->value = $shipping->address;

		$data[] = $shipment;

		$shipName = new stdClass();
		if(empty($ppOrder->purchase_units[0]->shipping->name)){
			$shipName->op = 'add';
		} else{
			$shipName->op = 'replace';
		}
		$shipName->path = "/purchase_units/@reference_id=='default'/shipping/name";
		$shipName->value = $name;

		$data[] = $shipName;

		$customId = new stdClass();
		if(empty($ppOrder->purchase_units[0]->custom_id)){
			$customId->op = 'add';
		} else{
			$customId->op = 'replace';
		}
		$customId->path = "/purchase_units/@reference_id=='default'/custom_id";
		$customId->value = $order['details']['BT']->order_number;

		$data[] = $customId;



		if(in_array($plugin->_currentMethod->status_success,VmConfig::get('inv_os',array('C')))){

			$invoiceId = new stdClass();
			if(empty($ppOrder->purchase_units[0]->invoice_id)){
				$invoiceId->op = 'add';
			} else{
				$invoiceId->op = 'replace';
			}
			$invoiceId->path = "/purchase_units/@reference_id=='default'/invoice_id";

			$invM = VmModel::getModel('invoice');
			$invoiceNumberDate = array();
			$order['details']['BT']->invoice_locked = 0;
			$invM->getExistingIfUnlockedCreateNewInvoiceNumber($order['details']['BT'], $invoiceNumberDate, $order['details']['BT']->order_create_invoice_pass);
			$invoiceId->value = $invoiceNumberDate[0];
			$data[] = $invoiceId;
		}




		//if(empty($plugin->_currentMethod->withBreakdown)){
		$amount = new stdClass();
		if(empty($ppOrder->purchase_units[0]->amount)){
			$amount->op = 'add';
		} else{
			$amount->op = 'replace';
		}
		$amount->path = "/purchase_units/@reference_id=='default'/amount";
		$plugin->getPaymentCurrency($plugin->_currentMethod, $order['details']['BT']->payment_currency_id);
		$currency_code_3 = shopFunctions::getCurrencyByID($plugin->_currentMethod->payment_currency, 'currency_code_3');
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$plugin->_currentMethod->payment_currency);
		$amountP = new stdClass();
		$amountP->value = $totalInPaymentCurrency['value'];
		$amountP->currency_code = $currency_code_3;
		$amount->value = $amountP;


		if(!empty($plugin->_currentMethod->withBreakdown)){

			$breakDown = new stdClass();
			$item_total = new stdClass();

			$secureTotal = 0.0;
			$secureTotalTax = 0.0;
			foreach($order['items'] as $key=>$oItem){
				//vmdebug('updateOrder $oItem',$oItem);
				/*if(empty($oItem->product_basePriceWithTax)){
					$itemPrice = $oItem->product_priceWithoutTax;
				} else {
					$itemPrice = $oItem->product_basePriceWithTax;
				}*/
				$secureTotalTax += round((float)$oItem->product_tax,2) * $oItem->product_quantity;;
				$secureTotal += round($oItem->product_item_price,2) * $oItem->product_quantity;
			}

			if(!empty($ppOrder->purchase_units[0]->amount->breakdown->item_total)){
				//$item_total->value = $ppOrder->purchase_units[0]->amount->breakdown->item_total->value;
				$item_total->value = (string) (round($secureTotal, 2) );
				$item_total->currency_code = $currency_code_3;
				$breakDown->item_total = $item_total;
			}

			$shipping = new stdClass();
			//if(!empty($ppOrder->purchase_units[0]->amount->breakdown->item_total)){
			$shipmentValue = round($order['details']['BT']->order_shipment + $order['details']['BT']->order_shipment_tax,2);;
			$shipping->value = (string) $shipmentValue;
			$shipping->currency_code = $currency_code_3;
				$breakDown->shipping = $shipping;
			//}
			$handling = new stdClass();
			if(!empty($ppOrder->purchase_units[0]->amount->breakdown->handling)){
				$handling->value = $ppOrder->purchase_units[0]->amount->breakdown->handling->value;
				$handling->currency_code = $currency_code_3;
				$breakDown->handling = $handling;
			}

			$tax_total = new stdClass();
			if(!empty($ppOrder->purchase_units[0]->amount->breakdown->tax_total)){
				$tax_total->currency_code = $currency_code_3;
				$tax_total->value = (string)round((float)$order['details']['BT']->order_tax,2);  //Not order_billTaxAmount has the shipping tax in it
				$breakDown->tax_total = $tax_total;
			}

			$discount = new stdClass();
			if(!empty($ppOrder->purchase_units[0]->amount->breakdown->discount)){
				//$discount->value = $ppOrder->purchase_units[0]->amount->breakdown->discount->value;
				$discount->value = (string) round( abs(round((float)$totalInPaymentCurrency['value'],2) - round((float)$shipmentValue,2) - round($item_total->value,2) - $secureTotalTax) ,2);

				//$discount->value = (string) round( abs(round((float)$totalInPaymentCurrency['value'],2) - round((float)$shipping->value,2) - round($item_total->value,2) ), 2);

				$discount->currency_code = $currency_code_3;
				$breakDown->discount = $discount;
			}

			$amount->value->breakdown = $breakDown;
		}

		$data[] = $amount;

		PayPalToken::getPayPalAccessToken($plugin);
		$options = PayPalToken::getMinimalHeaderInOptions($plugin->_currentMethod);
		$url = self::getShowOrderDetailsUrl($plugin->_currentMethod, $vmPPOrderId);

		$dataStr = vmJsApi::safe_json_encode($data);
		$body = PayPalToken::sendCURL($options, $url, $dataStr, 'patch');
		//$plugin->storePayPalData($order['details']['BT']->virtuemart_order_id, $plugin->_currentMethod);
		return $body;
	}

	static function getCaptureUrl($method, $vmPPOrderId){
		return PayPalToken::getUrl($method).'/v2/checkout/orders/'.$vmPPOrderId.'/capture';
	}

	static function captureOrder(&$plugin, $vmPPOrderId = 0){

		if($vmPPOrderId == 0) $vmPPOrderId = plgVmPaymentPaypal_checkout::getvmPPOrderId();//->get('vmPPOrderId',false,'vm');

		if($vmPPOrderId){

			$url = self::getCaptureUrl($plugin->_currentMethod,$vmPPOrderId);
			$data = new stdClass();
			$data->requestId = 'captureOrder.'.$vmPPOrderId;


			$res = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);

			//VmEcho::$logDebug = 1;
			//vmdebug('my $res in captureOrder',$res);
			if(isset($res->status) and $res->status == "COMPLETED" and isset($res->purchase_units[0])
				and isset ($res->purchase_units[0]->payments->captures[0])
				and $res->purchase_units[0]->payments->captures[0]->status == "COMPLETED"
				and !empty($res->purchase_units[0]->custom_id)	){

				plgVmPaymentPaypal_checkout::$PPResult->capture_id = $res->purchase_units[0]->payments->captures[0]->id;
				$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($res->purchase_units[0]->custom_id);
				vmdebug('captureOrder my $virtuemart_order_id',$virtuemart_order_id);
				$oData['order_status'] = $plugin->_currentMethod->status_success;

				$invNu = VirtueMartModelInvoice::getInvoiceEntry( $virtuemart_order_id, true, '*' );
				if($invNu){
					$oData['invoice_locked'] = '1';
				} else {
					$oData['invoice_locked'] = '0';
				}


				vmdebug('captureOrder my ordernumber and id',$res->purchase_units[0]->custom_id, $virtuemart_order_id, $oData['order_status']);
				//VmEcho::$logDebug = 0;
				$orderModel = VmModel::getModel('orders');
				//$order = $orderModel->getOrder($virtuemart_order_id);
				//$data = new stdClass();

				if($orderModel->updateStatusForOneOrder($virtuemart_order_id, $oData, true)){
					//VmEcho::$logDebug = 1;

					vmdebug('captureOrder my $virtuemart_order_id after updateStatusForOneOrder',$virtuemart_order_id);
					//$cart->emptyCart();
					//plgVmPaymentPaypal_checkout::emptyvmPPOrderId();
					$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);

					$cart = VirtueMartCart::getCart();
					$cart->virtuemart_order_id = $virtuemart_order_id;

					$plugin->orderCompleted(false, $cart);
					//Why return, why not redirect? Seems to work now, if fired with js, we need it this way
					return $virtuemart_order_id;
					//$app = JFactory::getApplication();
					//$app->redirect('/index.php?option=com_virtuemart&view=cart&layout=orderdone');
				} else {
					return 1;
				}
			} else {

				if(isset($res->status) and $res->status != "COMPLETED"){
					vmError('captureOrder Payment not completed ', 'captureOrder Payment not completed ',7,$res);
				} else if(!empty($res->purchase_units[0]->custom_id)){
					vmError('captureOrder Ordernumber not found ', $res);
				}
				plgVmPaymentPaypal_checkout::emptyvmPPOrderId();
				return 0;
			}
			//VmEcho::$logDebug = 0;
		} else {
			//VmEcho::$logDebug = 1;
			//vmTrace('Cannot capture without ppOrderid');
			vmError('Cannot capture without ppOrderid');
		}
		//$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
		return false;
	}

	static function getShowOrderDetailsUrl($method, $vmPPOrderId){
		return PayPalToken::getUrl($method).'/v2/checkout/orders/'.$vmPPOrderId;
	}

	static function showOrderDetails($plugin, $vmPPOrderId, $ds=''){
		$url = self::getShowOrderDetailsUrl($plugin->_currentMethod, $vmPPOrderId).$ds;
		//$data = new stdClass();

		$options = new JRegistry();
		$options->set('transport.curl', PayPalToken::$transportCurl);
		PayPalToken::getPayPalAccessToken($plugin);
		$contentType = 'application/json';
		$headers = array(
			'Content-Type' => $contentType,
			'Authorization' => 'Bearer '.$plugin->_currentMethod->bearToken,
			'PayPal-Request-Id'      => 'showOrderDetails.' . $vmPPOrderId,
			'PayPal-Partner-Attribution-Id' => plgVmPaymentPaypal_checkout::BNCODE,
			'userAgent' => 'VirtueMart.'.vmVersion::$REVISION
			);
		$options->set('headers',$headers);
		$plugin->setPayPalDebug($plugin->_currentMethod);
		$res = PayPalToken::sendCURL($options, $url, '', 'get');
		return $res;
	}

	//to get
	static function confirmOrder(){

	}

	static function getAuthorizeUrl($method, $vmPPOrderId){
		return PayPalToken::getUrl($method).'/v2/checkout/orders/'.$vmPPOrderId.'/authorize';
	}

	static function authorizeOrder(&$plugin, $vmPPOrderId){

		//$vmPPOrderId = plgVmPaymentPaypal_checkout::getvmPPOrderId();

		//if($vmPPOrderId) {
			$plugin->setPayPalDebug($plugin->_currentMethod);
			$url = self::getAuthorizeUrl($plugin->_currentMethod, $vmPPOrderId);
			$data = new stdClass();
			$data->requestId = 'AuthorizeOrder.' . $vmPPOrderId;

			$res = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);

			vmdebug('my $res in AuthorizeOrder',$res);
			if($res->status == "COMPLETED") {

				plgVmPaymentPaypal_checkout::$PPResult->authorize_id = $res->purchase_units[0]->payments->authorizations[0]->id;

				$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($res->purchase_units[0]->custom_id);
				vmdebug('authorizeOrder my ordernumber and id',$res->purchase_units[0]->custom_id, $virtuemart_order_id);
				$orderModel = VmModel::getModel('orders');
				//$order = $orderModel->getOrder($virtuemart_order_id);
				//$data = new stdClass();
				//VmEcho::$logDebug = 0;
				$oData['order_status'] = $plugin->_currentMethod->status_confirmed;

				$task = vRequest::getCmd('task');
				if($task == 'authorizeOrder'){
					$oData['invoice_locked'] = '0';
				} else {
					$oData['invoice_locked'] = '1';
				}

				if($orderModel->updateStatusForOneOrder($virtuemart_order_id, $oData, true)){
					//VmEcho::$logDebug = 0;
					/*$cart = VirtueMartCart::getCart();

					$cart->emptyCart();
					plgVmPaymentPaypal_checkout::emptyvmPPOrderId();*/
					$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
					$cart = VirtueMartCart::getCart();

					$cart->virtuemart_order_id = $virtuemart_order_id;
					$plugin->orderCompleted(false,$cart);

					return $virtuemart_order_id;
					//$app = JFactory::getApplication();
					//$app->redirect('/index.php?option=com_virtuemart&view=cart&layout=orderdone');
				} else {
					return 0;
				}
			}
		/*} else {
			return 'there was no orderId';
		}*/
		//$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
		//VmEcho::$logDebug = 0;
	}


}