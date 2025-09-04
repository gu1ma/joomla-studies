<?php

/**
 *
 * Layout for the payment selection
 *
 * @package	VirtueMart
 * @subpackage Cart
 * @author Max Milbers
 *
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: select_payment.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->layoutName!=$this->cart->layout) : ?>
	<form method="post" id="paymentForm" name="choosePaymentRate" action="<?php echo JRoute::_('index.php'); ?>" class="form-validate">
<?php endif; ?>

	<?php if ($this->found_shipment_method ) : ?>
		<fieldset class="vm-payment-shipment-select">
			<ul class="list-group">
				<?php
				foreach ($this->paymentplugins_payments as $paymentplugin_payments) {
					if (is_array($paymentplugin_payments)) {
						foreach ($paymentplugin_payments as $paymentplugin_payment) {
							$selected = strpos($paymentplugin_payment, 'checked') != false ? 'list-group-item-primary' : '';
							echo '<li class="vm-payment-plugin-single list-group-item d-flex py-3 ' . $selected . '">' . str_replace(array('input', 'label'),array('input class="form-check-input me-1"','label class="form-check-label"'),$paymentplugin_payment) . '</li>';
						}
					}
				}
			?>
			</ul>
		</fieldset>
	<?php else : ?>
		<?php echo $this->shipment_not_found_text; ?>
	<?php endif; ?>

<?php if ($this->layoutName!=$this->cart->layout) :  ?>
		<input type="hidden" name="option" value="com_virtuemart" />
		<input type="hidden" name="view" value="cart" />
		<input type="hidden" name="task" value="updatecart" />
		<input type="hidden" name="controller" value="cart" />
	</form>
<?php endif; ?>