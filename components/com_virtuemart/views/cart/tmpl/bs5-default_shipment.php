<?php

/**
 *
 * Template for the shipment selection
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
 * @version $Id: cart.php 2400 2010-05-11 19:30:47Z milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->layoutName!=$this->cart->layout) : ?>
	<form method="post" id="shipmentForm" name="chooseShipmentRate" action="<?php echo JRoute::_('index.php'); ?>" class="form-validate">
<?php endif; ?>

	<?php if ($this->found_shipment_method ) : ?>
		<fieldset class="vm-shipment-select">
			<ul class="list-group">
		<?php // if only one Shipment , should be checked by default
		foreach ($this->shipments_shipment_rates as $shipment_shipment_rates) {
			if (is_array($shipment_shipment_rates)) {
				foreach ($shipment_shipment_rates as $shipment_shipment_rate) {
					$selected = strpos($shipment_shipment_rate, 'checked') != false ? 'list-group-item-primary' : '';
					echo '<li class="vm-shipment-plugin-single list-group-item d-flex py-3 ' . $selected . '">' . str_replace(array('input', 'label'),array('input class="form-check-input me-1"','label class="form-check-label"'),$shipment_shipment_rate) . '</li>';
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