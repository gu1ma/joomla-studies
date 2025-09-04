<?php

/**
*
* Layout for the add to cart popup
*
* @package	VirtueMart
* @subpackage Cart
* @author Max Milbers
*
* @link https://virtuemart.net
* @copyright Copyright (c) 2013 - 2019 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: cart.php 2551 2010-09-30 18:52:40Z milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>

<div class="vm-padded">
	<?php if ($this->products and is_array($this->products) and count($this->products) > 0 ) : ?>
		<?php foreach ($this->products as $product) : ?>
			<?php if ($product->quantity > 0) : ?>
				<?php $quantity = isset($product->quantityAdded)? $product->quantityAdded: $product->quantity; ?>
				<div class="alert alert-success d-flex align-items-center justify-content-center">
					<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
						<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
						<path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
					</svg>
					<div class="ms-2"><?php echo vmText::sprintf('COM_VIRTUEMART_CART_PRODUCT_ADDED',$product->product_name,$quantity); ?></div>
				</div>
			<?php endif; ?>

			<?php if (!empty($product->errorMsg)) : ?>
				<div class="alert alert-danger"><?php echo $product->errorMsg; ?></div>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php if (VmConfig::get('popup_rel',1)) : ?>
		<?php if ($this->products and is_array($this->products) and count($this->products)>0 ) : ?>
			<?php
				$product = reset($this->products);
				$customFieldsModel = VmModel::getModel('customfields');
				$product->customfields = $customFieldsModel->getCustomEmbeddedProductCustomFields($product->allIds,'R');
				$customFieldsModel->displayProductCustomfieldFE($product,$product->customfields);
			?>

			<?php if (!empty($product->customfields)) : ?>
				<h3 class="h5 fw-normal text-center pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_RELATED_PRODUCTS'); ?></h3>
				<div class="overflow-hidden mb-3">
					<div class="product-related-products row align-items-stretch justify-content-center gx-2 gy-4">
						<?php
						foreach($product->customfields as $rFields){
							if(!empty($rFields->display)){ ?>
								<div class="product-field product-field-type-<?php echo $rFields->field_type ?> col-6 col-md-4 col-lg-3">
									<div class="product-field-display h-100"><?php echo $rFields->display ?></div>
								</div>
								<?php
							}
						} ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

	<div class="d-flex justify-content-between border-top pt-2">
		<a class="btn btn-secondary continue_link" href="<?php echo $this->continue_link; ?>"><?php echo vmText::_('COM_VIRTUEMART_CONTINUE_SHOPPING'); ?></a>
		<a class="btn btn-primary showcart" href="<?php echo $this->cart_link; ?>"><?php echo vmText::_('COM_VIRTUEMART_CART_SHOW'); ?></a>
	</div>
</div>
