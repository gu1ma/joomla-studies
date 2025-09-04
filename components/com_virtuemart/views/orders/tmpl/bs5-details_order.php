<?php

/**
 *
 * Order detail view
 *
 * @package	VirtueMart
 * @subpackage Orders
 * @author Oscar van Eijk, Valerie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: details_order.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>

<div class="table-responsive mb-5">
	<table class="table table-order-details td-50">
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_NUMBER') ?> :
			</td>
			<td>
				<?php echo $this->orderdetails['details']['BT']->order_number; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_DATE') ?> :
			</td>
			<td>
				<?php echo vmJsApi::date($this->orderdetails['details']['BT']->order_created, 'LC4', true); ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_STATUS') ?> :
			</td>
			<td>
				<?php echo $this->orderstatuses[$this->orderdetails['details']['BT']->order_status]; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_LAST_UPDATED') ?> :
			</td>
			<td>
				<?php echo vmJsApi::date($this->orderdetails['details']['BT']->order_modified, 'LC4', true); ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SHIPMENT_LBL') ?> :
			</td>
			<td>
				<?php echo $this->shipment_name; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PAYMENT_LBL') ?> :
			</td>
			<td>
				<?php echo $this->payment_name; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_CUSTOMER_NOTE') ?> :
			</td>
			<td valign="top">
				<?php echo $this->orderdetails['details']['BT']->customer_note; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL') ?> :
			</td>
			<td>
				<?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_total, $this->user_currency_id); ?>
			</td>
		</tr>
	</table>

	<table class="table table-order-billing td-50">
		<tr>
			<th colspan="2"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_BILL_TO_LBL') ?></th>
		</tr>
		<?php
			foreach ($this->userfields['fields'] as $field) {
				if (!empty($field['value'])) {
					echo '<tr><td class="key">' . $field['title'] . ' :</td><td>' . $field['value'] . '</td></tr>';
				}
			}
		?>
	</table>

	<?php if (!empty($this->orderdetails['details']['has_ST'])) : ?>
		<table class="table table-order-shipping td-50">
			<tr>
				<th colspan="2"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SHIP_TO_LBL') ?></th>
			</tr>
			<?php
				foreach ($this->shipmentfields['fields'] as $field) {
					if (!empty($field['value'])) {
						echo '<tr><td class="key">' . $field['title'] . ' :</td><td>' . $field['value'] . '</td></tr>';
					}
				}
			?>
		</table>
	<?php else : ?>
		<table class="table table-order-shipping td-50">
			<tr>
				<th><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SHIP_TO_LBL') ?></th>
			</tr>
			<tr>
				<td>
					<?php echo vmText::_('COM_VM_ST_SAME_AS_BT'); ?>
				</td>
			</tr>
		</table>
	<?php endif; ?>
</div>