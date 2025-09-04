<?php

/** @var TYPE_NAME $viewData */
$address = $viewData['address'];
//vmdebug('my address',$address);
vmJsApi::css('ccf', 'plugins/vmpayment/paypal_checkout/assets/css/');

?>
<!--div id="paypal-button-container" class="paypal-button-container"></div-->
<div class="card_container" style="display:none">

    <label for='card-number'>Card Number</label><div id='card-number' class='card_field'></div>
    <div style="width:49%; display: inline-block;">
        <label for='expiration-date'>Expiration Date</label><div id='expiration-date' class='card_field'></div>
    </div>
    <div style="width:50%; display: inline-block; float:right">
        <label for='cvv'>CVV</label><div id='cvv' class='card_field'></div>
    </div>
    <label for='card-holder-name'>Name on Card</label>
    <input type='text' id='card-holder-name' name='card-holder-name' autocomplete='off' placeholder='card holder name' value="<?php echo $address->card_holder_name?>" />
    <div style="width:49%; display: inline-block;">
        <label for='card-billing-address-street'>Billing Address</label>
        <input type='text' id='card-billing-address-street' name='card-billing-address-street' autocomplete='off' placeholder='street address' value="<?php echo $address->street?>" />
    </div>
    <div style="width:50%; display: inline-block; float:right">
        <label for='card-billing-address-unit'>&nbsp;</label>
        <input type='text' id='card-billing-address-unit' name='card-billing-address-unit' autocomplete='off' placeholder='unit' value="<?php echo $address->unit?>" />
    </div>
    <div style="width:49%; display: inline-block;">
        <input type='text' id='card-billing-address-city' name='card-billing-address-city' autocomplete='off' placeholder='city' value="<?php echo $address->city?>" />
    </div>
    <div style="width:50%; display: inline-block; float:right">
        <input type='text' id='card-billing-address-state' name='card-billing-address-state' autocomplete='off' placeholder='state' value="<?php echo $address->state?>" />
    </div>
    <div style="width:49%; display: inline-block;">
        <input type='text' id='card-billing-address-zip' name='card-billing-address-zip' autocomplete='off' placeholder='zip / postal code' value="<?php echo $address->zip?>" />
    </div>
    <div style="width:50%; display: inline-block; float:right">
        <input type='text' id='card-billing-address-country' name='card-billing-address-country' autocomplete='off' placeholder='country code' value="<?php echo $address->country?>"  />
    </div-->
</div>

