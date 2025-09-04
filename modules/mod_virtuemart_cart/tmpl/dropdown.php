<?php // no direct access
defined('_JEXEC') or die('Restricted access');

$j = "jQuery(document).ready(function($){
    $('button.dropdownCart-btn').click(function(){
        $('.dropdownCart-container').fadeToggle();
    });

    $(document).click(function(e) {
        var container = $('.dropdownCart');

        // if the target of the click isn't the container nor a descendant of the container
        if (!container.is(e.target) && container.has(e.target).length === 0)
        {
            $('.dropdownCart-container').fadeOut();
        }
    });
});";

vmJsApi::addJScript('vmDropdownCart',$j);

//dump ($cart,'mod cart');
// Ajax is displayed in vm_cart_products
// ALL THE DISPLAY IS Done by Ajax using "hiddencontainer" ?>

<!-- Virtuemart 2 Ajax Card -->
<div class="vmCartModule dropdownCart<?php echo $params->get('moduleclass_sfx'); ?>" id="vmCartModule<?php echo $params->get('moduleid_sfx'); ?>">
    <button class="dropdownCart-btn" type="button">
        <span class="dropdownCart-icon"><?php
        if ( $dropdown_icon ) { ?>
        <img src="<?php echo $dropdown_icon; ?>" alt="" />
        <?php } else { ?><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg><?php } ?>
        </span>
        <div class="total_products">
            <?php echo $data->totalProductTxt ?>
        </div>
    </button>
<?php
if ($show_product_list) {
    ?>
    <div class="hiddencontainer" style=" display: none; ">
        <div class="vmcontainer">
            <div class="product_row">
                    <div class="product_row_info">
                        <div class="image"></div>
                        <div class="product_name"></div>
                         <div class="product_price">
                            <span class="quantity"></span> &times;
                            <span class="subtotal_with_tax"></span>
                        </div>
                    </div>
                    <div class="customProductData"></div>
            </div>
        </div>
    </div>
    <div class="dropdownCart-container<?php echo $dropdown_alignment == 1 ? '' : ' dropdown_align_left' ; ?>">
        <div class="vm_cart_products">
            <div class="vmcontainer">
            <?php
                foreach ($data->products as $i=>$product){
                    ?><div class="product_row">
                        <div class="product_row_info">
                            <div class="image"><?php
                            if (VmConfig::get('oncheckout_show_images')) {
                            echo !empty($cart->products[$i]->images[0]) ? $cart->products[$i]->images[0]->displayMediaThumb ('', FALSE) : '';
                            } ?></div>
                            <div class="product_name"><?php echo  $product['product_name'] ?></div>
                            <div class="product_price">
                                <span class="quantity"><?php echo  $product['quantity'] ?></span> &times;
                                <?php if ($show_price and $currencyDisplay->_priceConfig['salesPrice'][0]) { ?>
                                <span class="subtotal_with_tax"><?php echo $product['subtotal_with_tax'] ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if ( !empty($product['customProductData']) ) { ?>
                            <div class="customProductData"><?php echo $product['customProductData'] ?></div>
                        <?php } ?>
                </div>
            <?php } ?>
            </div>
        </div>
        <div class="total"><?php if ($data->totalProduct and $show_price and $currencyDisplay->_priceConfig['salesPrice'][0]) { ?>
        <?php echo $data->billTotal; ?>
        <?php } ?></div>
        <div class="show_cart">
        <?php if ($data->totalProduct) echo '<a class="details" href="'.$data->cart_show_link.'" rel="nofollow" >'.$data->linkName.'</a>'; ?>
        </div>
    </div>
<?php }

$view = vRequest::getCmd('view');
if($view!='cart' and $view!='user'){
    ?><div class="payments-signin-button" ></div><?php
}
?>
<noscript>
<?php echo vmText::_('MOD_VIRTUEMART_CART_AJAX_CART_PLZ_JAVASCRIPT') ?>
</noscript>
</div>