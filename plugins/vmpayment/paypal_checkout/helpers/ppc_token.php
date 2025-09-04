<?php

/**
 *
 * Paypal checkout payment plugin
 *
 * @author Max Milbers
 * @version $Id: ppc_token.php
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

class PayPalToken {

	static $transportCurl = array(

		CURLOPT_PROXY => null,
		CURLOPT_PROXYUSERPWD => null,
	);

	static function getUrl($method){

		$sandboxDot ='';
		if(!empty($method->sandbox)){
			$sandboxDot = 'sandbox.';
		}
		return 'https://api-m.'.$sandboxDot.'paypal.com';
	}

	static function getTokenUrl($method){
		return self::getUrl($method).'/v1/oauth2/token';
	}

	static function getMinimalHeaderInOptions($currentMethod){
		$options = new JRegistry();
		$options->set('transport.curl', PayPalToken::$transportCurl);

		$contentType = 'application/json';
		$headers = array('Content-Type' => $contentType,
			'Authorization' => 'Bearer '.$currentMethod->bearToken,
			'PayPal-Partner-Attribution-Id' => plgVmPaymentPaypal_checkout::BNCODE,
			'userAgent' => 'VirtueMart.'.vmVersion::$REVISION);
		$options->set('headers',$headers);

		return $options;
	}

	protected static $_plugin = null;
	protected static $_reTryBearer = 0;

	static function sendCURLDefaultHeader(&$plugin, $url, $data, $extraHeaders = array()){

		self::getPayPalAccessToken($plugin);
		$currentMethod = $plugin->_currentMethod;
		if(empty($currentMethod->bearToken)){
			return false;
		}

		//vmdebug('sendCURLDefaultHeader data', $url, $data);
		$options = new JRegistry();
		$options->set('transport.curl', self::$transportCurl);

		//VmEcho::$logDebug = 1;
		//vmdebug('sendCURLDefaultHeader my $data to json_encode',$data);
		$contentType = 'application/json';
		if(is_object($data)){
			if(isset($data->requestId)){
				$requestId = $data->requestId;
				unset($data->requestId);
			}

			if(isset($data->contentType)){
				$contentType = $data->contentType;
				unset($data->contentType);
			}
			$dataStr = vmJsApi::safe_json_encode($data);

		} else {
			$dataStr = $data;
		}

		//vmdebug('sendCURLDefaultHeader my $dataStr json_encoded',$dataStr);
		if(!isset($requestId)){

			$requestId = hash('sha256',$dataStr);
			//vmdebug('my request id '.$requestId,$dataStr);
		}
		$headers = array('Content-Type' => $contentType,
			'Authorization' => 'Bearer '.$currentMethod->bearToken,
			'PayPal-Request-Id'      => $requestId,
			'Prefer' => 'return=representation',
			'PayPal-Partner-Attribution-Id' => plgVmPaymentPaypal_checkout::BNCODE,
			'userAgent' => 'VirtueMart.'.vmVersion::$REVISION);
		if(!empty($extraHeaders)){
			$headers = array_merge($headers, $extraHeaders);
		}
		$options->set('headers',$headers);

//vmdebug('sendCURLDefaultHeader data',$data);
		//$dataStr = vmJsApi::safe_json_encode($data);

		return self::sendCURL($options, $url, $dataStr);
	}

	static function sendCURL($options, $url, $dataStr, $post = 'post'){

		if(is_object($dataStr)){
			$dataStr = vmJsApi::safe_json_encode($dataStr);
		}
		try{
			//vmTrace('PayPal sendCURL ', false, 5);
			vmdebug('PayPal sendCURL my URL '.$url,vmJsApi::safe_json_decode($dataStr));

			$conn = VmConnector::getHttp($options, array('curl', 'stream'));
			if($post == 'post'){
				plgVmPaymentPaypal_checkout::$Response = $conn->post($url,$dataStr);
			} else if($post == 'get'){
				plgVmPaymentPaypal_checkout::$Response = $conn->get($url,array());
			} else if($post == 'delete'){
				plgVmPaymentPaypal_checkout::$Response = $conn->delete($url,array());
			} else if($post == 'patch'){
				plgVmPaymentPaypal_checkout::$Response = $conn->patch($url,$dataStr);
			}

			plgVmPaymentPaypal_checkout::$Response->body = vmJsApi::safe_json_decode(plgVmPaymentPaypal_checkout::$Response->body);
			plgVmPaymentPaypal_checkout::$PPResult->body = plgVmPaymentPaypal_checkout::$Response->body;
			if( plgVmPaymentPaypal_checkout::$Response->code >= 200 and plgVmPaymentPaypal_checkout::$Response->code <= 204){

				vmdebug('PayPal sendCURL my result by getHttp',plgVmPaymentPaypal_checkout::$PPResult->body);

				return plgVmPaymentPaypal_checkout::$PPResult->body ;
			} else {
				if( plgVmPaymentPaypal_checkout::$Response->code == 401 and
					( plgVmPaymentPaypal_checkout::$Response->body->error == 'invalid_token' )){

					//VmEcho::$logDebug = 1;
					//vmdebug('error bearerToken invalid_token ');
					if(self::$_plugin!==null and self::$_reTryBearer<4){

						self::removeOldBearerToken();

						//vmdebug('Going to reset bearerToken');
						self::getPayPalAccessToken(self::$_plugin);
						if(!empty(self::$_plugin->_currentMethod->bearToken)){
							self::$_reTryBearer++;
							//vmdebug('retry after reset bearerToken');
							return self::sendCURL($options, $url, $dataStr, $post);
						}
					}

				}

				plgVmPaymentPaypal_checkout::setVmdebugPaypalError();
				if(!empty($resObj->headers['paypal-debug-id'])){
					if(is_array($resObj->headers['paypal-debug-id'])){
						$payPalDebugid = ' paypal-debug-id: '.$resObj->headers['paypal-debug-id'][0];
					} else {
						$payPalDebugid = ' paypal-debug-id: '.$resObj->headers['paypal-debug-id'];
					}
				} else {
					$payPalDebugid = '';
				}
				$PPResult = (object)get_object_vars(plgVmPaymentPaypal_checkout::$Response) ;
				//unset($PPResult->headers);
				$PPResult->payPalDebugid = $payPalDebugid;
				$PPResult->error = 1;
				$PPResult->body = plgVmPaymentPaypal_checkout::$Response->body;
				$adminText = 'There was an error get/post '.$url.' Code: '.plgVmPaymentPaypal_checkout::$Response->code.$payPalDebugid."\n";
				$adminText .= 'sent headers: '.vmEcho::varPrintR($options);
				//$adminText .= 'sent: '.vmEcho::varPrintR(vmJsApi::safe_json_decode($dataStr));
				$adminText .= 'recieved: '.vmEcho::varPrintR(plgVmPaymentPaypal_checkout::$Response);
				vmTrace($adminText,4);
				//vmError($adminText,'There was an error get/post in PayPal Checkout');
				return plgVmPaymentPaypal_checkout::$PPResult->body ;
			}

		} catch (Exception $exception){
			vmError('PayPal sendCURL throws Exception', 'PayPal sendCURL throws Exception',5, $exception);
		}
		return false;
	}


	static function getPayPalAccessToken(&$plugin){

		self::$_plugin = &$plugin;
		$currentMethod = $plugin->_currentMethod;

		if(empty($currentMethod->bearToken)){

			if (!extension_loaded('curl')) {
				vmError(vmText::sprintf('VMPAYMENT_PAYPAL_CONF_MANDATORY_PHP_EXTENSION', 'curl'));
				return false;
			}

			/*if (!extension_loaded('openssl')) {
				vmError(vmText::sprintf('VMPAYMENT_' . $this->_name . '_CONF_MANDATORY_PHP_EXTENSION', 'openssl'));
			}*/

			$sandbox = '';
			if($plugin->_currentMethod->sandbox=='' or !empty($plugin->_currentMethod->sandbox)){
				$sandbox = 'sandbox_';
			}

			//VmEcho::$logDebug = 1;
			//Check if we have it already in the session
			$sess = JFactory::getSession();
			$bearToken = $sess->get($sandbox.'vmpp',false,'vm');
			//vmdebug('$bearToken in Session ',$bearToken);
			if($bearToken){
				$bearTokenTime = $sess->get($sandbox.'vmpptime', 0, 'vm');
				//vmdebug('Returning cached beartoken ? '.(time() - $bearTokenTime),$bearTokenTime);
				if((time() - $bearTokenTime) < 10800) {
					$plugin->_currentMethod->bearToken = $bearToken;
					//vmdebug('Returning cached beartoken'); VmEcho::$logDebug = 0;
					return;
				}
			} else {
				vmdebug('Getting new beartoken');
			}


			if(!empty($currentMethod->{$sandbox.'client_id'}) and !empty($currentMethod->{$sandbox.'client_secret'})) {

				$url = self::getTokenUrl($currentMethod); //'https://api-m.'.$sandboxDot.'paypal.com/v1/oauth2/token';

				$options = new JRegistry();
				$options->set('transport.curl', self::$transportCurl);
				$options->set('userauth',$currentMethod->{$sandbox.'client_id'});
				$options->set('passwordauth',$currentMethod->{$sandbox.'client_secret'});

				$options->set('userAgent','VirtueMart.'.vmVersion::$REVISION);

				//$options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;

				$dataStr = 'grant_type=client_credentials';

				$content = self::sendCURL($options, $url, $dataStr);

				if(!empty($content->access_token)){
					$currentMethod->bearToken = $content->access_token;
					$sess->set($sandbox.'vmpp',$currentMethod->bearToken,'vm');
					$sess->set($sandbox.'vmpptime',time(),'vm');
					vmdebug('new Beartoken ',$currentMethod->bearToken);
				}
			}

		}

	}

	static function getPayPalClientToken(&$plugin){

		self::getPayPalAccessToken($plugin);

		if(empty($plugin->client_token)){


			$sandbox = '';
			$sandboxDot = '';
			if($plugin->_currentMethod->sandbox){
				$sandbox = 'sandbox_';
				$sandboxDot = 'sandbox.';
			}

			$sess = JFactory::getSession();
			$clientToken = $sess->get($sandbox.'vmppClientToken',false,'vm');
			vmdebug('$clientToken in Session ',$clientToken);
			if($clientToken){
				$clientTokenTime = $sess->get($sandbox.'vmppClientTokenTime', 0, 'vm');
				//vmdebug('Returning cached beartoken ? '.(time() - $bearTokenTime),$bearTokenTime);
				if((time() - $clientTokenTime) < 3550) {
					$plugin->clientToken = $clientToken;
					vmdebug('Returning cached ClientToken '); VmEcho::$logDebug = 0;
					return $clientToken;
				}
			} else {
				vmdebug('Getting new ClientToken');
			}

			$url = 'https://api.'.$sandboxDot.'paypal.com/v1/identity/generate-token';

			$res = self::sendCURLDefaultHeader($plugin, $url, '');

			if(!empty($res->client_token)){
				vmdebug('Setting client token '.$res->client_token);
				$plugin->clientToken = $res->client_token;
				$sess->set($sandbox.'vmppClientToken',$plugin->clientToken,'vm');
				$sess->set($sandbox.'vmppClientTokenTime',time(),'vm');
				return $res->client_token;
			}

		}
	}

	static function removeOldBearerToken() {

		$sandbox = '';
		if(self::$_plugin->_currentMethod->sandbox=='' or !empty(self::$_plugin->_currentMethod->sandbox)){
			$sandbox = 'sandbox_';
		}

		self::$_plugin->_currentMethod->bearToken = 0;
		$sess = JFactory::getSession();
		$sess->set($sandbox.'vmpp',false,'vm');
		session_write_close();
		session_start();
	}

}