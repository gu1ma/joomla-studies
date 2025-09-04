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

class PayPalWebHooks {

	static $eventTypes = array("PAYMENT.AUTHORIZATION.CREATED", "PAYMENT.AUTHORIZATION.VOIDED"
	,"PAYMENT.CAPTURE.COMPLETED","PAYMENT.CAPTURE.DENIED","PAYMENT.CAPTURE.PENDING","PAYMENT.CAPTURE.REFUNDED","PAYMENT.CAPTURE.REVERSED"
	,"CHECKOUT.PAYMENT-APPROVAL.REVERSED","CHECKOUT.ORDER.COMPLETED","CHECKOUT.ORDER.APPROVED", "MERCHANT.PARTNER-CONSENT.REVOKED" );

	static function getWebHooksUrl($method){
		//$method->sandbox = 0;
		return PayPalToken::getUrl($method).'/v1/notifications/webhooks';
	}

	static function checkWebHooks(&$plugin){

		PayPalToken::getPayPalAccessToken($plugin);

		$urlPP = self::getWebHooksUrl($plugin->_currentMethod);

		$options = PayPalToken::getMinimalHeaderInOptions($plugin->_currentMethod);
		$body = PayPalToken::sendCURL($options, $urlPP, '', 'get');

		$webHookIds = array();
		$eventTypesForCheck = self::$eventTypes;
		$deleteAll = vRequest::getCmd('deleteWebhooks', false);
		if(isset($body->webhooks)){

			//Anything normal, just one webhook Url registered
			if(count($body->webhooks)==1){
				$myWebHooks = reset($body->webhooks);
				if(isset($myWebHooks->id)) $webHookIds[] = $myWebHooks->id;

				if( $myWebHooks->url != self::getWebHooksShopUrl()){
					$deleteAll = true;
				}
				if(!empty($myWebHooks->event_types) and !$deleteAll){
					//vmdebug('checkWebHooks !empty($myWebHooks->event_types',$myWebHooks->event_types);
					foreach($myWebHooks->event_types as $eventType){
						if($eventType->status == 'ENABLED'){
							//vmdebug('checkWebHooks $eventType->status == ENABLED',$eventType);
							if(empty($eventTypesForCheck)) $deleteAll = true;   //That means that there is a not needed hook set
							$key = array_search($eventType->name,$eventTypesForCheck);
							if($key !== false){
								unset($eventTypesForCheck[$key]);
							}
						}
					}
				}
			} else {
				//There is something old there, delete all.
				$deleteAll = true;
				foreach($body->webhooks as $webhook){
					if(isset($webhook->id)) $webHookIds[] = $webhook->id;
				}
			}

		}

		if(empty($eventTypesForCheck) and !$deleteAll){
			vmInfo('All Webhooks set');
		} else
		if(!empty($webHookIds)){
			//if($deleteAll)
			foreach($webHookIds as $webHookId){
				vmdebug('Deleting webHooks with Id '.$webHookId);
				self::deleteWebHooks($plugin, $webHookId);
			}

			self::createWebHooks($plugin);
		} else {
			self::createWebHooks($plugin);
		}

	}

	static function getWebHooksShopUrl(){
		return 'https://'.vmURI::getURI()->getHost().JURI::root(true).'/index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=webhook';
	}

	static function createWebHooks (&$plugin) {

		$currentMethod = $plugin->_currentMethod;
		PayPalToken::getPayPalAccessToken($plugin);

		$urlPP = self::getWebHooksUrl($currentMethod);

		$url = self::getWebHooksShopUrl();
		//$url = 'https://webhook.site/cd880796-06f3-499c-b053-246728bf9d74';
		if(true){
			$dataStr = '{
  "url": "'.$url.'",
  "event_types": [';

			foreach(self::$eventTypes as $event){
				$dataStr .= '{"name": "'.$event.'"}, ';
			}
			$dataStr = substr($dataStr,0,-2);
			$dataStr .= ']
}';
		} else {
			$data = new stdClass();
			$data->url = $url;
			$data->event_types = array();

			foreach($events as $event) {
				$data->event_types[] = '{"name": "' . $event . '"}';
			}
			//vmdebug('sendCURLDefaultHeader data',$data);
		}
		vmInfo('Webhooks created with url '.$url);
		$options = PayPalToken::getMinimalHeaderInOptions($currentMethod);
		$body = PayPalToken::sendCURL($options, $urlPP, $dataStr);

	}

	static function deleteWebHooks($plugin, $webHookId){
		$currentMethod = $plugin->_currentMethod;
		PayPalToken::getPayPalAccessToken($plugin);

		$options = PayPalToken::getMinimalHeaderInOptions($currentMethod);

		$urlPP = self::getWebHooksUrl($currentMethod);
		$body = PayPalToken::sendCURL($options, $urlPP.'/'.$webHookId, '','delete');
	}

	static function verifyWebHookSignature(&$plugin, $raw, $data){

		$currentMethod = $plugin->_currentMethod;

		$headers = apache_request_headers();

		if(!empty($headers['PAYPAL-CERT-URL'])) {
			$pp_certUrl = $headers['PAYPAL-CERT-URL'];
		} else if(!empty($headers['Paypal-Cert-Url'])) {
			$pp_certUrl = $headers['Paypal-Cert-Url'];
		} else {
			plgVmPaymentPaypal_checkout::setVmdebugPaypalError();
			vmdebug('Webhook is missing PAYPAL-CERT-URL in header ',$headers,$raw);
			return false;
		}
		$pubKey = openssl_pkey_get_public(file_get_contents($pp_certUrl));
		$details = openssl_pkey_get_details($pubKey);

		$sigString = $headers['Paypal-Transmission-Id'].'|'.$headers['Paypal-Transmission-Time'].'|'.$currentMethod->webHookId.'|'.crc32($raw);;
		$verifyResult = openssl_verify($sigString, base64_decode($headers['PAYPAL-TRANSMISSION-SIG']), $details['key'], $headers['Paypal-Auth-Algo']);

		if ($verifyResult === 0) {
			//throw new Exception('signature incorrect');
			vmdebug('signature incorrect');
			return false;
		} elseif ($verifyResult === -1) {
			//throw new Exception('error checking signature');
			vmdebug('error checking signature');
			return false;
		}
		vmdebug('Signatured checked',$verifyResult);
		return true;

	}

	static function getNotificationsUrl($method){
		//$method->sandbox = 0;
		return PayPalToken::getUrl($method).'/v1/notifications';
	}

	static function simulateWebHookEvent($plugin){

		$currentMethod = $plugin->_currentMethod;
		PayPalToken::getPayPalAccessToken($plugin);

		$urlPP = PayPalToken::getNotificationsUrl($currentMethod).'/simulate-event';

		$options = PayPalToken::getMinimalHeaderInOptions($currentMethod);

		$data = new stdClass();
		$data->url = 'https://'.vmURI::getURI()->getHost().JURI::root(true);
		$data->event_type = vRequest::getCmd('webhook');

		$body = PayPalToken::sendCURL($options, $urlPP, $data);
	}
}