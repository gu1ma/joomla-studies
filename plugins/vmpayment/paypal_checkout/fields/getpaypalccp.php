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
class JFormFieldGetPaypalCCP extends JFormField {

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	var $type = 'getPaypal';

	protected function getLabel() {

		//return vmText::_('Merchant onboarding means to create an application in your PayPal account to work with VirtueMart.');
	}

	protected function getInput() {

		$pId = vRequest::getInt('cid',false);
		if(is_array($pId)){
			$pId = reset($pId);
		}
		$method = VmModel::getModel('paymentmethod')->getPayment($pId);

		//new created method has nothing set
		if($method->sandbox=='' or !empty($method->sandbox)){
			$sandboxDot = 'sandbox.';
			$sandboxTxt = '_SANDBOX';
			$sandbox = 'sandbox_';
			$partnerClientId="AR2WhK4TpHhOZ6dEVBgy8YY6PSmBgNywtSs46GDpaS5VJm0yaUzlgrjd-EC2q4zuG_5gv1kVJFQTcmnA";
			$partnerId = "J4B84GGFGKHTS";
		} else {
			$sandboxDot = '';
			$sandboxTxt = '';
			$sandbox = '';
			$partnerClientId="AQRQ3RmCHRNTo_PLNqGXTjYavsMjK9dvxwMVHrR2HqBxF6ciX5lainLRzYj0ifJ-mtWEoYNLIyl6EGbK";
			$partnerId = "WBA3Y7FQXGVW4";
		}

		$userId = VirtueMartModelVendor::getUserIdByVendorId($method->virtuemart_vendor_id);
		$userM = VmModel::getModel('user');
		$vendor = $userM->getUser($userId);
		$address = reset($vendor->userInfo);

		if(empty($method->{$sandbox.'paypal_merchant_id'})){


			$sellerNonce = hash( 'sha256',time().vmURI::getURI()->getHost().JURI::root(true));

			$returnUrl = urlencode(vmURI::getURI()->getScheme().'://'.vmURI::getURI()->getHost().JURI::root(true).'/administrator/index.php?option=com_virtuemart&view=paymentmethod&task=edit&cid='.$pId);

			$hrefNvp = array('partnerId' => $partnerId, 'partnerClientId' => $partnerClientId, /*'returnToPartnerUrl' => $returnUrl,*/ 'features' => 'PAYMENT,REFUND', 'sellerNonce' => $sellerNonce);
			$href = 'https://www.'.$sandboxDot.'paypal.com/US/merchantsignup/partner/onboardingentry?channelId=partner&productIntentId=addipmt&integrationType=FO&displayMode=minibrowser';
			//$href = 'https://www.sandbox.paypal.com/US/bizsignup/partner/entry?channelId=partner&productIntentId=addipmt&integrationType=FO&displayMode=minibrowser';
			foreach($hrefNvp as $k=>$v){
				$href .= '&'.$k.'='.$v;
			}
			$ppMerchantlink = '<a href="https://developer.paypal.com/docs/multiparty/seller-onboarding/" target="_blank">PayPal developer page</a>';
			$docsLink = '<a href="https://docs.virtuemart.net/tutorials/plugins-payment-shipment-and-others/paypal-checkout" target="_blank">docs.virtuemart.net</a>';
			$html = vmText::sprintf('VMPAYMENT_PAYPAL_MERCHANT_ONBOARDING_DESC',$ppMerchantlink,$docsLink);
			$html .= '<a id="ppOnboarding" target="_blank" data-paypal-onboard-complete="vmPPConboardedCallback" href="'.$href.'" data-paypal-button="PPLtBlue">'.vmText::_('VMPAYMENT_PAYPAL_MERCHANT_ONBOARDING'.$sandboxTxt).'</a>';


			$html .= '<script id="paypal-js" src="https://www.'.$sandboxDot.'paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>';
			//vmJsApi::addJScript( 'paypal','https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js', '', true, true);


			//vmdebug('my $userId',$address);
			$j = '
	vmPP = new Object();
	vmPP.pm = "'.$pId.'";
	vmPP.sellerNonce = "'.$sellerNonce.'";
	vmPP.reqApprovalPP = "'.vmText::_('VMPAYMENT_PAYPAL_PIU_REQUEST_APPROVAL').'";
	vmPP.country = "'.VirtueMartModelCountry::getCountryFieldByID($address->virtuemart_country_id, 'country_2_code').'";
	';
			vmJsApi::addJScript('ppOnboarding',$j, true);
		} else {

			$href = vmURI::getURI()->getScheme().'://'.vmURI::getURI()->getHost().JURI::root(true).'/administrator/index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&pm='.$method->virtuemart_paymentmethod_id.'&task=disconnectMerchant';

			$html = '<a id="paypal-dialog"  href="'.$href.'" data-paypal-button="PPLtBlue">'.vmText::_('VMPAYMENT_PAYPAL_MERCHANT_DISCONNECT'.$sandboxTxt).'</a>';

			/*$j = 'onClickButton = function (){
		javascript::confirmation("'.vmText::_('VMPAYMENT_PAYPAL_MERCHANT_DISCONNECT_WARN').'");
	}
	//Query("#ppOnboarding").off("click", onClickButton);
	jQuery("#ppOnboarding").on("click", onClickButton);
	';
			vmJsApi::addJScript('ppOnboarding',$j, true);*/

			$userId = VirtueMartModelVendor::getUserIdByVendorId($method->virtuemart_vendor_id);
			$userM = VmModel::getModel('user');
			$vendor = $userM->getUser($userId);
			$address = reset($vendor->userInfo);

			$html .= '<style>.ui-dialog{z-index:1000}</style>';

			$html .= '<div id="dialog" title="Confirmation Required" >
  '.vmText::_('VMPAYMENT_PAYPAL_MERCHANT_DISCONNECT_WARN').'
</div>';
			$html .= '<div id="pp-messages" style="visibility: hidden"></div>';
			$j = '
	vmPP = new Object();
	vmPP.pm = "'.$pId.'";
	vmPP.reqApprovalPP = "'.vmText::_('VMPAYMENT_PAYPAL_PIU_REQUEST_APPROVAL').'";
	vmPP.country = "'.VirtueMartModelCountry::getCountryFieldByID($address->virtuemart_country_id, 'country_2_code').'";
	
	jQuery(document).ready(function() {

    jQuery("#dialog").dialog({
        modal: true,
        bgiframe: true,
        width: 400,
        height: 100,
        autoOpen: false
    });


    jQuery("#paypal-dialog").click(function(e) {

        e.preventDefault();
        var theHREF = jQuery(this).attr("href");

        jQuery("#dialog").dialog("option", "buttons", {
            "Confirm" : function() {
                window.location.href = theHREF;
            },
            "Cancel" : function() {
                jQuery(this).dialog("close");
            }
        });

        jQuery("#dialog").dialog("open");

    });

});
	';
			vmJsApi::addJScript('ppOnboarding',$j, true);
		}

		vmJsApi::addJScript( '/plugins/vmpayment/paypal_checkout/assets/js/admin.js');
		return $html;
	}

}