<?php
/**
 *
 * Realex payment plugin
 *
 * @author Valerie Isaksen
 * @version $Id$
 * @package VirtueMart
 * @subpackage payment
 * Copyright (C) 2004 - 2019 Virtuemart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
class JFormFieldGetPaypal extends JFormField {

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	var $type = 'getPaypal';

	protected function getLabel() {

	}

	protected function getInput() {

		//JHtml::_('behavior.colorpicker');

		vmJsApi::addJScript( '/plugins/vmpayment/paypal/paypal/assets/js/admin.js');
		vmJsApi::css('paypal', 'plugins/vmpayment/paypal/paypal/assets/css/');

		$url = "https://www.paypal.com/us/webapps/mpp/referral/paypal-payments-standard?partner_id=83EP5DJG9FU6L";
		$logo = '<img src="https://www.paypalobjects.com/en_US/i/logo/PayPal_mark_60x38.gif" />';
		$html = '<p><a target="_blank" href="' . $url . '"  >' . $logo . '</a></p>';
		$html .= '<p><a target="_blank" href="' . $url . '" class="signin-button-link">' . vmText::_('VMPAYMENT_PAYPAL_REGISTER') . '</a>';
		$html .= ' <a target="_blank" href="http://docs.virtuemart.net/manual/shop-menu/payment-methods/paypal.html" class="signin-button-link">' . vmText::_('VMPAYMENT_PAYPAL_DOCUMENTATION') . '</a></p>';

		$paymentMethodId = vRequest::getInt('cid');
		if(isset($paymentMethodId[0])){
			$method = VmModel::getModel('paymentmethod')->getPayment($paymentMethodId[0]);
			//vmdebug('my method',$paymentMethodId);

			if($method->paypalproduct=='exp'){

				$env = 'production';
				$sandbox = '';
				$url = 'https://api-3t.paypal.com/nvp';
				if ($method->sandbox ) {
					$env = 'sandbox';
					$sandbox = 'sandbox_';
					$url = 'https://api-3t.sandbox.paypal.com/nvp';
				}

				/*if(!class_exists('vmPPButton')) require(VMPATH_PLUGINS .'/vmpayment/paypal/paypal/tmpl/ppbuttons.php');
				$html .= vmPPButton::renderCheckoutButton($method,$env).'<div class="clear"></div>';*/


				$page = '/nvp';
				//$options = new \Joomla\Registry\Registry;

				$apiLoginId = $sandbox.'api_login_id';
				$apiPassword = $sandbox.'api_password';
				$apiSignature = $sandbox.'api_signature';

				if(empty($method->{$apiLoginId})){
					return $html;
				}

				$optionsArray = array('USER' => $method->{$apiLoginId},
				'PWD' => $method->{$apiPassword},
				'SIGNATURE' => $method->{$apiSignature},
				'METHOD' => 'SetExpressCheckout',
				'VERSION' => 98,
				'PAYMENTREQUEST_0_AMT' => 10,
				'PAYMENTREQUEST_0_CURRENCYCODE' => 'USD',
				'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
				'cancelUrl' => 'https://example.com/cancel.html',
				'returnUrl' => 'https://example.com/success.html');

				$options = http_build_query($optionsArray);
				$resultPage = self::get_web_page($url, $page, $options);
				//vmdebug('My result page',$resultPage);

				$html .= '<p style="padding-top:20px;padding-left:300px;float:right">';
				if ($method->sandbox ) {
					$html .= '<span>SandBox </span>';
				} else {
					$html .= '<span>LIVE </span>';
				}

				if($resultPage and isset($resultPage[0]['http_code']) and $resultPage[0]['http_code']=='200' and strpos($resultPage[1],'TOKEN=')!==FALSE and strpos($resultPage[1],'ACK=Success')!==FALSE){
					$html .= '<span style="color:green" >User credentials are valid</span>';
				} else {
					$html .= '<span style="color:red" >User credentials are invalid</span>';
				}
				$html .= '</p>';
			}
		}

		return $html;
	}

	/** It only works with cURL, we need it this way here
	 * @param $url
	 * @param $page
	 * @param $curl_data
	 * @return array
	 */
	function get_web_page( $url, $page, $curl_data ) {

		$headers = array(
			"POST ".$page." HTTP/1.1",
			"Content-type: text/xml;charset=\"utf-8\"",
			"Accept: text/xml",
			"Cache-Control: no-cache",
			"Pragma: no-cache",
			"SOAPAction: \"run\"",
		);

		$options = array(
			CURLOPT_RETURNTRANSFER => true,         // return web page
			//CURLOPT_HEADER         => false,        // don't return headers
			CURLOPT_FOLLOWLOCATION => true,         // follow redirects
			/*CURLOPT_ENCODING       => "",           // handle all encodings
			/*CURLOPT_USERAGENT      => "spider",     // who am i*/
			CURLOPT_AUTOREFERER    => true,         // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
			CURLOPT_TIMEOUT        => 120,          // timeout on response
			CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
			CURLOPT_POST            => 1,            // i am sending post data
			CURLOPT_POSTFIELDS     => $curl_data,    // this are my post vars
			CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false,        //
			CURLOPT_VERBOSE        => 1,                //
			CURLOPT_URL             => $url,
			CURLOPT_HTTPHEADER      => $headers,
			CURLOPT_FRESH_CONNECT    =>1,
			CURLOPT_FAILONERROR     =>1

		);

		$ch      = curl_init($url);
		curl_setopt_array($ch,$options);
		$content = curl_exec($ch);
		$err     = curl_errno($ch);
		$errmsg  = curl_error($ch) ;
		$header  = curl_getinfo($ch);
		curl_close($ch);

		return array($header, $content, $err, $errmsg);
	}

}