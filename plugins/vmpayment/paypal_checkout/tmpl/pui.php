<?php
/** @var TYPE_NAME $viewData */
//vmdebug('my data for the pui ',$viewData);
$ppIntNumber = $viewData['ppIntNumber'];
$pageType = $viewData['pageType'];
$sandboxBool = $viewData['sandboxBool'];
$nonce = $viewData['nonce'];

$html = '<div><label>'.vmText::_('VMPAYMENT_PAYPAL_INTERNATIONAL_DIALLING_CODE').'</label>
<input class="pui-required" type="text" id="paypal_int_number" name="paypal_int_number" size="4" value="'.$ppIntNumber.'">';



$html .= '<label>'.vmText::_('COM_VIRTUEMART_SHOPPER_FORM_PHONE').'</label>
<input class="pui-required" type="text" id="phone_1_field" name="phone_1" size="30" maxlength="32" value="'.$viewData['phone_1'].'" >';


$html .= '<label>'.vmText::_('Date of birth').'</label>'.
	vmJsApi::jDate($viewData['paypal_date_of_birth'], 'paypal_date_of_birth', 'paypal_date_of_birth', false, '-105:-12','minDate:"-105y",maxDate:"-12y",defaultDate:"-35y",','Virtuemart.checkPUIData();').'</div>';
$html .= '<div id="paypal-pui-container"></div>';

$html .= '<noscript>
  <img src="https://c.paypal.com/v1/r/d/b/ns?f="'.$nonce.'"&s='.$this->_currentMethod->paypal_merchant_id.'_'.$pageType.'&js=0&r=1" />
</noscript>';
$html .= '<script type="application/json" fncls="fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99" defer="">
      {
          "f":"'.$nonce.'",
          "s":"'.$this->_currentMethod->paypal_merchant_id.'_'.$pageType.'",
          "sandbox":'.$sandboxBool.'
      }
  </script>';
$html .= '<script type="text/javascript" src="https://c.paypal.com/da/r/fb.js"></script>';
echo $html;
