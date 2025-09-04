<?php

/**
 *
 * Paypal checkout payment plugin
 *
 * @author Max Milbers
 * @version $Id: ppc_webhooks.php
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

class PaypalResponseWebHooks {

	static function webhook(&$plugin, &$render){

		/** @var String $bodyReceived */
		$bodyReceived = file_get_contents('php://input');
		$data = vmJsApi::safe_json_decode($bodyReceived);
		vmdebug('PayPal Webhook, $bodyReceived '.vmEcho::varPrintR( $data));

		if(!in_array($data->event_type,PayPalWebHooks::$eventTypes)) {
			vmError('PayPal Webhook, unknown event type '.vmEcho::varPrintR( $data));
			http_response_code(200);
			$render = 0;
			return 0;
		}

		$verified = PayPalWebHooks::verifyWebHookSignature($plugin, $bodyReceived, $data);
		if(!$verified) {
			vmError('PayPal Webhook,could not be verified '.vmEcho::varPrintR( $data));
			http_response_code(200);
			$render = 0;
			return 0;
		}

		if(empty($data->resource) ){
			vmError('PP Webhook Resource missing '.vmEcho::varPrintR( $data));
			http_response_code(200);
			$render = 0;
			return 0;
		}


		//vmdebug('PayPal Webhook, data '.vmEcho::varPrintR( $data));

		/*if($data->event_type == 'PAYMENT.AUTHORIZATION.CREATED'){
			//lets capture the payment

			//$ppOrderId = $plugin->getPPOrderIdFromLink($data->resource);
			//$virtuemart_order_id = $plugin->getvmOrderIdByPPOrderId($ppOrderId);

			if(!empty($virtuemart_order_id)){
				PaypalPayment::captureAuthorizedPayment($plugin, $virtuemart_order_id);
			}

			$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);

		} else */
		$render = 1;
		if($data->event_type == 'PAYMENT.CAPTURE.COMPLETED'){
			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);

			//Normal Webhook format send by PayPal
			if(!empty($data->resource->supplementary_data) and !empty($data->resource->supplementary_data->related_ids->order_id)){
				$ppOrderId = $data->resource->supplementary_data->related_ids->order_id;
				$virtuemart_order_id = self::getvmOrderIdByPPOrderId($plugin, $ppOrderId);
			}
			/*else {
				if(!empty($data->resource->custom_id)){
					$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($data->resource->custom_id);
				}
			}*/

			vmdebug('PAYMENT.CAPTURE.COMPLETED $virtuemart_order_id '.$virtuemart_order_id);
			if($virtuemart_order_id){
				$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
				$updated = self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_success);
				vmdebug('PAYMENT.CAPTURE.COMPLETED updated order to '.$plugin->_currentMethod->status_success);
				if($updated and $plugin->_currentMethod->paypal_products == 'pui'){
					vmdebug('PAYMENT.CAPTURE.COMPLETED sending pui mail');
					self::sendPUIMail($plugin, $virtuemart_order_id, $data);    //oder $data->resource
				}
			}

		} else if($data->event_type == 'CHECKOUT.ORDER.APPROVED'){      //ingore for PUI
			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			if(isset($data->resource) and isset($data->resource->purchase_units[0]) and !empty($data->resource->purchase_units[0]->custom_id)){

				$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($data->resource->purchase_units[0]->custom_id);
				vmdebug('CHECKOUT.ORDER.APPROVED '.$virtuemart_order_id);
				$orderModel = VmModel::getModel('orders');
				$order = $orderModel->getOrder($virtuemart_order_id);
				$plugin->_currentMethod = $plugin->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id);
				if(!empty($order['details']['BT']) /*and $data->resource->custom_id == $order['details']['BT']->order_number*/) {
					if ($order['details']['BT']->order_status == 'P') {
						$oData['order_status'] = $plugin->_currentMethod->status_confirmed;
						$orderModel->updateStatusForOneOrder($virtuemart_order_id, $oData, true);
						$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
					}
				}
			}

		} else if($data->event_type == 'PAYMENT.AUTHORIZATION.VOIDED'){
			//Cancel order?
			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			if(!empty($data->resource->supplementary_data) and !empty($data->resource->supplementary_data->related_ids->order_id)) {
				$ppOrderId = $data->resource->supplementary_data->related_ids->order_id;
				$virtuemart_order_id = self::getvmOrderIdByPPOrderId($plugin, $ppOrderId);
				$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
				self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_canceled);
			} else {
				vmError('PAYMENT.AUTHORIZATION.VOIDED there was no supplementary_data');
			}
		} else if($data->event_type == 'PAYMENT.CAPTURE.PENDING'){

			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);

			/*} else if($data->event_type == 'PAYMENT.CAPTURE.COMPLETED'){
				//Set order on confirmed
				plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
				$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
				self::updateOrderStatus($plugin, $virtuemart_order_id, 'C');*/

		} else if($data->event_type == 'PAYMENT.CAPTURE.DENIED'){

			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			//Set order on denied
			if(!empty($data->resource->supplementary_data) and !empty($data->resource->supplementary_data->related_ids->order_id)){
				$ppOrderId = $data->resource->supplementary_data->related_ids->order_id;
				$virtuemart_order_id = self::getvmOrderIdByPPOrderId($plugin, $ppOrderId);
				vmdebug('PAYMENT.CAPTURE.DENIED getvmOrderIdByPPOrderId '.$virtuemart_order_id);
			} else {
				$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
				vmdebug('PAYMENT.CAPTURE.DENIED getvmOrderIdByCaptureId '.$virtuemart_order_id);
			}
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_denied);

		} else if($data->event_type == 'PAYMENT.CAPTURE.REFUNDED'){
			//Set order on refunded mit Order_number = custom_id
			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			//$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
			$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($data->resource->custom_id);
			vmdebug('PAYMENT.CAPTURE.REFUNDED my virtuemart order id',$virtuemart_order_id);
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_refunded);

		} else if($data->event_type == 'PAYMENT.CAPTURE.REVERSED'){
			//Set order on cancelled? mit Order_number = custom_id
			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			//$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
			$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($data->resource->custom_id);
			vmdebug('PAYMENT.CAPTURE.REFUNDED my virtuemart order id',$virtuemart_order_id);
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_refunded);

		} else if($data->event_type == 'CHECKOUT.PAYMENT-APPROVAL.REVERSED'){
			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data);

			if(!empty($data->resource->order_id)){
				$virtuemart_order_id = self::getvmOrderIdByPPOrderId($plugin, $data->resource->order_id);
			} else
			if(!empty($data->resource->purchase_units)){
				$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($data->resource->purchase_units[0]->inovice_id);
			}
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_denied);

		} else if($data->event_type == 'MERCHANT.PARTNER-CONSENT.REVOKED'){

			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			//toCheck
			$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_canceled);

			/*} else if($data->event_type == 'PAYMENT.SALE.REVERSED'){
				plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
				$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
				self::updateOrderStatus($plugin, $virtuemart_order_id, 'R');
	*/
		} else if($data->event_type == 'CUSTOMER.DISPUTE.CREATED'){

			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);
			$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			self::updateOrderStatus($plugin, $virtuemart_order_id, $plugin->_currentMethod->status_dispute);
		} else if($data->event_type == 'CUSTOMER.DISPUTE.RESOLVED'){

			plgVmPaymentPaypal_checkout::$PPResult->body = vmJsApi::safe_json_encode($data->resource);

			$virtuemart_order_id = self::getvmOrderIdByCaptureId($plugin, $data->resource->id);
			$plugin->_currentMethod = self::getPaymentMethodByVmOrderId($plugin, $virtuemart_order_id);
			//self::updateOrderStatus($plugin, $virtuemart_order_id, 'C');
			/*} else if($data->event_type == 'CHECKOUT.ORDER.COMPLETED'){
				//no clue, Set order on confirmed?
			} else if($data->event_type == 'CHECKOUT.ORDER.APPROVED'){
				//no clue, Set order on confirmed? */
		}

		http_response_code(200);
		return ;
	}

	static function getPaymentMethodByVmOrderId(&$plugin,$virtuemart_order_id){

		$db = JFactory::getDbo();
		$q = 'SELECT virtuemart_paymentmethod_id FROM #__virtuemart_orders WHERE virtuemart_order_id = "'.(int)$virtuemart_order_id.'"';
		$db->setQuery($q);
		$virtuemart_paymentmethod_id = $db->loadResult();
		return $plugin->getVmPluginMethod($virtuemart_paymentmethod_id);
	}

	static function getVmOrderIdByInvoiceId (&$plugin,$invoice_id) {

		$db = JFactory::getDbo();
		$q = 'SELECT virtuemart_order_id FROM #__virtuemart_invoices WHERE invoice_number = "'.$invoice_id.'"';
		$db->setQuery($q);
		$virtuemart_paymentmethod_id = $db->loadResult();
		return $plugin->getVmPluginMethod($virtuemart_paymentmethod_id);
	}

	static function sendPUIMail($plugin, $virtuemart_order_id, $puiData) {

		$ppOrderId = $puiData->resource->supplementary_data->related_ids->order_id;
		$ppOrder = PayPalOrder::showOrderDetails($plugin, $ppOrderId);

		$orderModel = VmModel::getModel('orders');
		$order = $orderModel->getOrder($virtuemart_order_id);
		VmConfig::ensureMemoryLimit(96);

		$vendorM = VmModel::getModel('vendor');
		$vendor = $vendorM->getVendor($plugin->_currentMethod->virtuemart_vendor_id);


		if(isset($ppOrder->payment_source->pay_upon_invoice)){
			$payment_reference = $ppOrder->payment_source->pay_upon_invoice->payment_reference;
			$deposit_bank_details = $ppOrder->payment_source->pay_upon_invoice->deposit_bank_details;

			//$data = get_object_vars(plgVmPaymentPaypal_checkout::$PPResult);
			$data['id'] = 0;
			$data['virtuemart_order_id'] = $virtuemart_order_id;
			$pui_data = new stdClass();
			///$pui_data->id = 0;
			$pui_data->payment_reference = $payment_reference;
			$pui_data->deposit_bank_details = $deposit_bank_details;

			$data['body'] = vmJsApi::safe_json_encode($pui_data);
			vmTrace('Storing my pp result for orderid '.$data->virtuemart_order_id, FALSE, 3);
			$plugin->storePluginInternalData($data);
		} else {
			$payment_reference = false;
			$deposit_bank_details = false;
		}


		$body = $plugin->renderByLayout('puimail',
			array(
				'order' => $order,
				'vendor' => $vendor,
				'pui_data' => $puiData,
				'payment_reference' => $payment_reference,
				'deposit_bank_details' => $deposit_bank_details,
			)
		);

		$subject = vmText::sprintf( 'VMPAYMENT_PAYPAL_PUI_MAIL_INSTRUCTIONS', $vendor->vendor_store_name );
		$mailer = JFactory::getMailer();

		$recipient = $order['details']['BT']->email;
		$mailer->addRecipient( $recipient );

		$subjectMailer= '=?utf-8?B?'.base64_encode($subject).'?=';
		if(function_exists('mb_decode_mimeheader')){
			$subjectMailer= mb_decode_mimeheader($subjectMailer);
		}
		$mailer->setSubject(  html_entity_decode( $subjectMailer , ENT_QUOTES, 'UTF-8') );
		$mailer->isHTML( VmConfig::get( 'order_mail_html', TRUE ) );
		$mailer->setBody( $body );
		$replyTo = array();
		$replyToName = array();

		$replyTo[0] = $vendorM->getVendorEmail($plugin->_currentMethod->virtuemart_vendor_id);
		$replyToName[0] = $vendor->vendor_name;

		if(count($replyTo)) {
			if(version_compare(JVERSION, '3.5', 'ge')) {
				$mailer->addReplyTo($replyTo, $replyToName);
			} else {
				$replyTo[1] = $replyToName[0];
				$mailer->addReplyTo($replyTo);
			}
		}

		// set proper sender
		$sender = array();
		if(!empty($replyTo[0]) and VmConfig::get( 'useVendorEmail', 0 )) {
			$sender[0] = $replyTo[0];
			$sender[1] = $replyToName[0];
		} else {
			// use default joomla's mail sender
			$app = JFactory::getApplication();
			$sender[0] = $app->getCfg( 'mailfrom' );
			$sender[1] = $app->getCfg( 'fromname' );
			if(empty($sender[0])){
				$config = JFactory::getConfig();
				$sender = array( $config->get( 'mailfrom' ), $config->get( 'fromname' ) );
			}
		}

		$mailer->setSender($sender);

		$debug_email = VmConfig::get('debug_mail', false);
		if (VmConfig::get('debug_mail', false) == '1') {
			$debug_email = 'debug_email';
		}

		if ($debug_email) {
			if (!is_array($recipient)) {
				$recipient = array($recipient);
			}
			$no = '';
			if ($debug_email == 'debug_email') {
				$no = 'no';
			}
			$msg = 'Debug mail active, '.$no.' mail sent. The mail to send subject ' . $subject . ' to "' . implode(' ', $recipient) . '" from ' . $sender[0] . ' ' . $sender[1] . ' ' . vmText::$language->getTag() . '<br>' . $body;
			//if (VmConfig::showDebug()) {
			vmdebug($msg);
			//} else {
			//vmInfo($msg);
			//}
			if ($debug_email == 'debug_email') {
				return true;
			}
		}

		try {

			$return = $mailer->Send();
		}
		catch (Exception $e)
		{
			vmEcho::$logDebug = true;
			vmdebug('Error sending mail ',$e);
			vmError('Error sending mail ');
			// this will take care of the error message
			return false;
		}
	}

	/**
	 * Helpers
	 */
	static function updateOrderStatus ($plugin, $virtuemart_order_id, $orderstatus) {

		$orderModel = VmModel::getModel('orders');
		$order = $orderModel->getOrder($virtuemart_order_id);
		if(!empty($order['details']['BT']) and $order['details']['BT']->order_status != $orderstatus){
			$oData['order_status'] = $orderstatus;
			$orderModel->updateStatusForOneOrder($virtuemart_order_id, $oData, true);
			$plugin->storePayPalData($virtuemart_order_id, $plugin->_currentMethod);
			return true;
		}
		return false;
	}

	static function getVmOrderIdByInternalPayPalTable ($plugin, $ppOrderId, $fieldname = 'ppOrderId') {

		$db = JFactory::getDBO();
		$q = 'SELECT * FROM `' . $plugin->_tablename . '` WHERE ';
		$q .= ' `'.$fieldname.'` = "' . $ppOrderId . '"';

		$db->setQuery($q);
		if (!($payments = $db->loadObjectList())) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		foreach($payments as $payment){
			if(!empty($payment->virtuemart_order_id)){
				return $payment->virtuemart_order_id;
			}
		}
		return 0;
	}

	static function getvmOrderIdByPPOrderId ($plugin, $ppOrderId) {
		return self::getVmOrderIdByInternalPayPalTable($plugin, $ppOrderId, 'ppOrderId');
	}

	static function getvmOrderIdByCaptureId ($plugin, $captureId) {
		return self::getVmOrderIdByInternalPayPalTable($plugin, $captureId, 'capture_id');
	}

	static function getvmOrderIdByAuthorizeId ($plugin, $authorizeId) {
		return self::getVmOrderIdByInternalPayPalTable($plugin, $authorizeId, 'authorize_id');
	}

	function getOrderIdFromLink($resource){
		$ppOrderId = 0;
		if(!empty($resource->links)) {
			$link = end($resource->links);
			if (!empty($link->href) and strpos('v2/checkout/orders', $link->href)) {
				$spl = explode('/', $link->href);
				$ppOrderId = end($spl);
			}
			if (empty($ppOrderId)) {
				foreach ($resource->links as $link) {
					if (!empty($link->href) and strpos('v2/checkout/orders', $link->href)) {
						$spl = explode('/', $link->href);
						$ppOrderId = end($spl);

					}
				}
			}
		}
		return $ppOrderId;
	}

	/**
	 * @param   int $virtuemart_order_id
	 * @param string $order_number
	 * @return mixed|string
	 */
	function getvmPPOrderIdByInternalData($virtuemart_order_id) {

		if (!empty($virtuemart_order_id)) {
			return '';
		}

		$payments = $this->_getPaypalInternalData($virtuemart_order_id);
		foreach($payments as $payment){
			if(!empty($payment->ppOrderId)){
				return $payment->ppOrderId;
			}
		}
		return false;
	}

}