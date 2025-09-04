<?php

/**
 *
 * Layout for the shopping cart
 *
 * @package    VirtueMart
 * @subpackage Cart
 * @author Max Milbers
 *
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2020 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 2551 2010-09-30 18:52:40Z milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

$user = Factory::getUser();

// Load Bootstrap collapse
\Joomla\CMS\HTML\HTMLHelper::_('bootstrap.collapse');

shopFunctionsF::loadOrderLanguages(VmConfig::get('vmDefLang'));
vmJsApi::vmValidator();

if (!isset( $this->from_cart )) $this->from_cart = true;
//vmdebug('my cart',$this->cart);
?>

<?php if (!empty ($this->cart->products)) : ?>
	<div class="vm-cart-header-container">
		<div class="vm-cart-header">
			<h1 class="vm-page-title mb-4 text-center"><?php echo vmText::_ ('COM_VIRTUEMART_CART_TITLE'); ?></h1>
		</div>
	</div>

	<div id="cart-view" class="cart-view">
		<?php
		// This displays the form to change the current shopper
		if ($this->allowChangeShopper and !$this->isPdf){
			echo $this->loadTemplate ('shopperform');
		}

		$taskRoute = '';
		?>

		<?php if (VmConfig::get('oncheckout_show_register', 0) || (VmConfig::get('oncheckout_only_registered', 0) && VmConfig::get('oncheckout_show_register', 0))) : ?>
			<div class="vm-checkout-login row mb-3">
				<?php if ($user->guest) : ?>
					<div class="col-12">
						<p><?php echo vmText::_('Do you have an account?'); ?> <a href="#collapseLogin" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapseLogin">Login</a></p>
					</div>
				<?php endif; ?>

				<div class="collapse col-12<?php echo $user->guest ? '' : ' show';?>" id="collapseLogin">
					<?php
						$uri = vmUri::getCurrentUrlBy('get');
						$uri = str_replace(array('?tmpl=component','&tmpl=component'),'',$uri);
						echo shopFunctionsF::getLoginForm ($this->cart, FALSE,$uri);
					?>
				</div>
			</div>
		<?php endif; ?>

		<div class="payments-signin-button row mb-3">
			<?php
				$html = '';
				vDispatcher::importVMPlugins('vmpayment');
				$returnValues = vDispatcher::trigger('plgVmDisplayLogin', array($this, &$html, true));

				if (is_array($html)) {
					foreach ($html as $login) {
						echo '<div class="col-6">' . $login . '</div>';
					}
				} else {
					echo '<div class="col">' . $html . '</div>';
				}
			?>
		</div>

		<form class="row gy-4 gx-xl-5" id="checkoutForm" method="post" name="checkoutForm" action="<?php echo Route::_ ('index.php?option=com_virtuemart&view=cart' . $taskRoute, $this->useXHTML, $this->useSSL); ?>">
			<div class="col-12 col-lg-6 border-end py-3">
				<?php if ($user->guest) : ?>
				<div class="vm-checkout-register-userfields mb-4">
					<fieldset>
                        <?php echo $this->loadTemplate ('userfields'); ?>
					</fieldset>
				</div>
				<?php endif; ?>

				<?php if (!$user->guest) : ?>
				<div class="vm-checkout-billing-userfields mb-4">
					<fieldset>
							<div class="output-billto p-3 mb-3 bg-light">
								<?php
									$cartfieldNames = array();
									foreach( $this->userFieldsCart['fields'] as $fields) {
										$cartfieldNames[] = $fields['name'];
									}
								?>

								<?php foreach ($this->cart->BTaddress['fields'] as $item) : ?>
									<?php if ((in_array($item['name'],$cartfieldNames)) || $item['name'] == 'delimiter_billto') continue; ?>
									<div class="row">
										<?php if (!empty($item['value'])) : ?>
											<?php if ($item['name'] === 'agreed') {
												$item['value'] = ($item['value'] === 0) ? vmText::_ ('COM_VIRTUEMART_USER_FORM_BILLTO_TOS_NO') : vmText::_ ('COM_VIRTUEMART_USER_FORM_BILLTO_TOS_YES');
											} ?>
											<div class="col-6"><?php echo $item['title'] ?> :</div>
											<div class="col-6"><?php echo $item['value'] ?></div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<?php
							if ($this->pointAddress) {
								$this->pointAddress = 'required invalid';
							}
							?>
							<a class="details <?php echo $this->pointAddress ?>" href="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=BT', $this->useXHTML, $this->useSSL) ?>" rel="nofollow">
								<?php echo vmText::_ ('COM_VIRTUEMART_USER_FORM_EDIT_BILLTO_LBL'); ?>
							</a>

							<input type="hidden" name="billto" value="<?php echo $this->cart->lists['billTo']; ?>"/>
					</fieldset>
				</div>
				<?php endif; ?>

				<div class="vm-checkout-shipping-userfields mb-4">
					<fieldset>
						<h2 class="h5 fw-normal pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_USER_FORM_SHIPTO_LBL'); ?></h2>
						<div class="output-shipto mb-2">
							<div class="form-check">
								<?php
									if ($this->cart->user->virtuemart_user_id==0) {
										echo '<label class="form-check-label" for="STsameAsBTjs">' . vmText::_ ('COM_VIRTUEMART_USER_FORM_ST_SAME_AS_BT') . '</label>';
										echo VmHtml::checkbox ('STsameAsBT', $this->cart->STsameAsBT,1,0,'id="STsameAsBTjs" class="form-check-input" data-dynamic-update=1');
									} else if(!empty($this->cart->lists['shipTo'])){
										echo $this->cart->lists['shipTo'];
									}
								?>
							</div>

							<?php if (empty($this->cart->STsameAsBT) and !empty($this->cart->ST) and !empty($this->cart->STaddress['fields'])) : ?>
								<div id="output-shipto-display" class="p-3 bg-light">
									<?php foreach ($this->cart->STaddress['fields'] as $item) : ?>
										<?php if ($item['name']=='shipto_address_type_name') continue; ?>

										<?php if (!empty($item['value'])) : ?>
											<div class="row">
												<div class="col-6"><?php echo $item['title'] ?> :</div>
												<div class="col-6"><?php echo $item['value'] ?></div>
											</div>
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php if (!isset($this->cart->lists['current_id'])) {
							$this->cart->lists['current_id'] = 0;
						} ?>
						<a class="details" href="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=user&task=editaddresscart&addrtype=ST&virtuemart_user_id[]=' . $this->cart->lists['current_id'], $this->useXHTML, $this->useSSL) ?>" rel="nofollow">
							<?php echo vmText::_ ('COM_VIRTUEMART_USER_FORM_EDIT_SHIPTO_LBL'); ?>
						</a>
					</fieldset>
				</div>

				<div class="vm-checkout-shipping mb-4">
					<h2 class="h5 fw-normal pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_CART_EDIT_SHIPPING'); ?></h2>
					<?php
					if (!empty($this->layoutName) and $this->layoutName == $this->cart->layout) {
						if (VmConfig::get('oncheckout_opc', 0)) {
							//$previouslayout = $this->setLayout('select');
							echo $this->loadTemplate('shipment');
							//$this->setLayout($previouslayout);
						}
					}
					?>
				</div>
				<div class="vm-checkout-payment">
					<h2 class="h5 fw-normal pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_CART_EDIT_PAYMENT'); ?></h2>
					<?php
					if (!empty($this->layoutName) && $this->layoutName == $this->cart->layout) {
						if (VmConfig::get('oncheckout_opc', 0)) {
							//$previouslayout = $this->setLayout('select');
							echo $this->loadTemplate('payment');
							//$this->setLayout($previouslayout);
						}
					}
					?>
				</div>
			</div>

			<div class="col-12 col-lg-6 py-3 align-self-start sticky-top">
				<?php echo $this->loadTemplate ('cartpricelist'); ?>
				<?php echo $this->loadTemplate ('cartfields'); ?>

				<div class="checkout-button-top">
					<?php echo str_replace('vm-button-correct','vm-button-correct btn btn-lg btn-primary w-100',$this->checkout_link_html); ?>
				</div>
			</div>

			<?php if (!empty($this->checkoutAdvertise)) : ?>
				<div class="mt-3" id="checkout-advertise-box">
					<?php foreach ($this->checkoutAdvertise as $checkoutAdvertise) : ?>
						<div class="checkout-advertise">
							<?php echo $checkoutAdvertise; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php // Continue and Checkout Button END ?>
			<input type='hidden' name='virtuemart_vendor_id' value='<?php echo $this->cart->vendorId; ?>'/>
			<input type='hidden' name='order_language' value='<?php echo $this->order_language; ?>'/>
			<input type='hidden' name='task' value='updatecart'/>
			<input type='hidden' name='option' value='com_virtuemart'/>
			<input type='hidden' name='view' value='cart'/>
		</form>

		<?php
			vmJsApi::addJScript('vm-checkoutFormSubmit',"
				Virtuemart.bCheckoutButton = function(e) {
					e.preventDefault();
					jQuery(this).vm2front('startVmLoading');
					jQuery(this).attr('disabled', 'true');
					jQuery(this).removeClass( 'vm-button-correct' );
					jQuery(this).addClass( 'vm-button' );
					jQuery(this).fadeIn( 400 );
					var name = jQuery(this).attr('name');
					var div = '<input name=\"'+name+'\" value=\"1\" type=\"hidden\">';
				    if (name=='confirm') {
				        jQuery('#checkoutForm').attr('action','".$this->orderDoneLink."');
				    }

					jQuery('#checkoutForm').append(div);
					//Virtuemart.updForm();

					jQuery('#checkoutForm').submit();
				}
				jQuery(document).ready(function($) {
					jQuery(this).vm2front('stopVmLoading');
					var el = jQuery('#checkoutFormSubmit');
					el.unbind('click dblclick');
					el.on('click dblclick',Virtuemart.bCheckoutButton);
				});
			");

/*			if (!VmConfig::get('oncheckout_ajax',false)) {
				vmJsApi::addJScript('vm-STisBT',"
					jQuery(document).ready(function($) {

						if ( $('#STsameAsBTjs').is(':checked') ) {
							$('#output-shipto-display').hide();
						} else {
							$('#output-shipto-display').show();
						}
						$('#STsameAsBTjs').click(function(event) {
							if($(this).is(':checked')){
								$('#STsameAsBT').val('1') ;
								$('#output-shipto-display').hide();
							} else {
								$('#STsameAsBT').val('0') ;
								$('#output-shipto-display').show();
							}
							var form = jQuery('#checkoutFormSubmit');
							form.submit();
						});
					});
				");
			}*/

			$this->addCheckRequiredJs();
			vmJsApi::addJScript( 'vmprices',false,false);
		?>

		<div style="display:none;" id="cart-js">
			<?php echo vmJsApi::writeJS(); ?>
		</div>
	</div>
<?php else : // The cart is empty ?>
	<div class="vm-continue-shopping text-center well p-4">
		<h1 class="vm-page-title pb-2 mb-4 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_EMPTY_CART'); ?></h1>

		<?php if (!empty($this->continue_link_html)) : ?>
			<a class="continue_link btn btn-sm btn-primary" href="<?php echo $this->continue_link; ?>"><?php echo vmText::_ ('COM_VIRTUEMART_CONTINUE_SHOPPING'); ?></a>
		<?php endif; ?>
	</div>
<?php endif; ?>