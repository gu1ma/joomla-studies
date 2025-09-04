<?php

/** @var TYPE_NAME $viewData */
//vmdebug('my data for the button ',$viewData);

$method = $viewData['method'];

if($method->paypal_products == 'buttons') {
    ?><div id="paypal-button-container" class="paypal-button-container"></div> <?php
}

/*if($method->paypal_products == 'sofort') {
	?><div id="paypal-sofort-container" class="paypal-sofort-container"></div>
    <div id="paypal-sofort-btn" class="paypal-sofort-btn"></div><?php
}*/
?>
