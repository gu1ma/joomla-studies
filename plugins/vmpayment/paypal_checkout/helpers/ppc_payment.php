<?php

/**
 *
 * Paypal checkout payment plugin
 *
 * @author Max Milbers
 * @version $Id: ppc_identity.php
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


class PaypalPayment {

	//https://api-m.paypal.com/v2/payments/authorizations/5O190127TN364715T
	static function getCaptureAuthorizedUrl($method, $vmPPOrderId){
		return PayPalToken::getUrl($method).'/v2/payments/authorizations/'.$vmPPOrderId.'/capture';
	}

	static function captureAuthorizedPayment($plugin, $virtuemart_order_id, $authorizationId = 0, $ppOrderId = 0){

		if(empty($authorizationId) and !empty($virtuemart_order_id)){
			$authorizationId = self::getPPAuthorizationId($plugin, $virtuemart_order_id);
		}

		if(empty($authorizationId) or empty($virtuemart_order_id)) return false;

		$url = self::getCaptureAuthorizedUrl($plugin->_currentMethod, $authorizationId);

		$orderModel = VmModel::getModel('orders');
		$order = $orderModel->getOrder($virtuemart_order_id);

		$plugin->getPaymentCurrency($plugin->_currentMethod, $order['details']['BT']->payment_currency_id);
		//$email_currency = $plugin->getEmailCurrency($currentMethod);
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$plugin->_currentMethod->payment_currency);

		$amount = new stdClass();
		$amount->value = (string)round((float)$totalInPaymentCurrency['value'],2);
		$amount->currency_code = shopFunctions::getCurrencyByID($plugin->_currentMethod->payment_currency, 'currency_code_3');

		$data = new stdClass();
		$data->requestId = $amount->value.'.captAuthPaym.'.$order['details']['BT']->order_number;
		$data->amount = $amount;

		//the problem here, we do not have the invoiceNumber yet, we get it after the succesfull capture
		$data->invoice_id = $order['details']['BT']->order_number;  //invoiceId or orderNumber?
		$data->final_capture = true;    //For subscriptions false, if not final payment
		$data->note_to_payer = '';  //Currently unsupported, needs form instead of link, or js which adds the comment to the link
		$vendor = VmModel::getModel('vendor')->getVendor($plugin->_currentMethod->virtuemart_vendor_id);
		$data->soft_descriptor = $vendor->vendor_name;

		$res = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);
		//vmdebug('my $res in AuthorizeOrder',$res);

		if(!empty($res->status) and $res->status == "COMPLETED") {
			$oData['order_status'] = $plugin->_currentMethod->status_success;
			if($orderModel->updateStatusForOneOrder($virtuemart_order_id, $oData, true)){
				//return true;
				$app = JFactory::getApplication();
				$app->redirect('/index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$virtuemart_order_id);
			}
		} else {

		}
	}

	static function getRefundCapturedPaymentUrl($method, $vmPPOrderId){
		return PayPalToken::getUrl($method).'/v2/payments/captures/'.$vmPPOrderId.'/refund';
	}

	static function refundCapturedPayment($plugin,  $virtuemart_order_id, $value = 0){

		$ppCaptureId = self::getPPCaptureId($plugin, $virtuemart_order_id);
		$url = self::getRefundCapturedPaymentUrl($plugin->_currentMethod, $ppCaptureId);

		$vendorM = VmModel::getModel('vendor');
		$vendor = $vendorM->getVendor($plugin->_currentMethod->virtuemart_vendor_id);

		$data = new stdClass();
		$data->requestId = 'refCaptPaym.'.$ppCaptureId;
		$data->note_to_payer = vRequest::getString('comments', 'refunded by '.$vendor->vendor_store_name);
		//$data->invoice_id = vRequest::getString('comments', '');
		$res = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);

		if(!empty($res->status) and $res->status == "COMPLETED") {
			$orderModel = VmModel::getModel('orders');
			$oData['order_status'] = 'R';
			$oData['invoice_locked'] = '0';
			if($orderModel->updateStatusForOneOrder($virtuemart_order_id, $oData, false)){
				return true;
			}
		}
	}

	static function getPPCaptureId($plugin, $virtuemart_order_id){
		if (!($payments = $plugin->_getPaypalInternalData($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return 0;
		}

		foreach($payments as $payment) {
			if (!empty($payment->capture_id)) {
				return $payment->capture_id;
			}
		}

		foreach($payments as $payment){
			if(!empty($payment->body)){
				$body = vmJsApi::safe_json_decode($payment->body);
				$plugin->setPayPalDebug($plugin->_currentMethod);
				if(!empty($body->purchase_units)){
					$purchaseUnits = reset($body->purchase_units);
					if(!empty($purchaseUnits->payments->captures)){

						$id = reset($purchaseUnits->payments->captures)->id;
						vmdebug('getPPCaptureId returning id',$id);
						//return $id;
					}

					if(!empty($id) and !empty($body->status) and $body->status == 'COMPLETED' ){
						//vmdebug('getPPCaptureId found completed',$body->purchase_units);
						return $id;
					}
				}

			}
		}
		if(!empty($id)) return $id;
	}

	static function getPPAuthorizationId($plugin, $virtuemart_order_id){
		if (!($payments = $plugin->_getPaypalInternalData($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return 0;
		}
		foreach($payments as $payment){
			if(!empty($payment->body)){
				$body = vmJsApi::safe_json_decode($payment->body);
				$plugin->setPayPalDebug($plugin->_currentMethod);
				if(!empty($body->status) and $body->status == 'COMPLETED' and !empty($body->purchase_units)){
					//vmdebug('getPPAuthorizationId found completed',$body->purchase_units);
					$purchaseUnits = reset($body->purchase_units);
					if(!empty($purchaseUnits->payments->authorizations)){

						$id = reset($purchaseUnits->payments->authorizations)->id;
						vmdebug('getPPAuthorizationId returning id',$id);
						return $id;
					}
				}
			}
		}
	}


}