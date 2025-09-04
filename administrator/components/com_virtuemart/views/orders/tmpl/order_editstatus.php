<?php
/**
 * Edit the formstatus
 *
 * @package	VirtueMart
 * @subpackage Orders
 * @author Oscar van Eijk
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: order_editstatus.php 10763 2022-12-01 17:56:33Z Milbo $
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

vmJsApi::addJScript( 'orderstatus', "

		function cancelOrderStatFormEdit(e) {
			jQuery('#orderStatForm').each(function(){
				this.reset();
			});
//quorvia NOV 2022
			jQuery('#order_edit_status')
				.find('option:selected').prop('selected', true)
				.end().trigger('liszt:updated');
//			jQuery('div#updateOrderStatus').hide();
			e.preventDefault();
		}

		");
?>



<form action="index.php" method="post" name="orderStatForm" id="orderStatForm">
	<fieldset>
	<table class="admintable table" >
		<tr>
			<td class="key"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_STATUS') ?></td>
			<td><?php
				//quorvia list to reflect the order list method of display march 2022
//						echo $this->orderStatSelect;
				echo JHtml::_ ('select.genericlist', $this->orderEditstatuses, 'order_status', 'class="orderstatus_select"', 'order_status_code', 'order_status_name', $this->orderbt->order_status, 'order_edit_status', TRUE);
?>
			</td>
			<td class="key">
				<a href="#" class="orderStatFormSubmit btn">


						<?php echo vmText::_('COM_VIRTUEMART_UPDATE_STATUS'); ?>
				</a>
				<br>
				<br>
				<a href="#" title="<?php echo vmText::_('COM_VIRTUEMART_CANCEL'); ?>" onClick="javascript:cancelOrderStatFormEdit(event);" class="show_element[updateOrderStatus] btn">

						<?php echo vmText::_('COM_VIRTUEMART_CANCEL'); ?>
				</a>
			</td>
		<tr>
			<td class="key"><?php echo vmText::_('COM_VIRTUEMART_COMMENT') ?></td>
			<td><textarea rows="4" cols="30" name="comments"></textarea>
			</td>

			<td class="key">
				<?php echo VmHTML::checkbox('customer_notified', false); ?>&nbsp;<?php echo vmText::_('COM_VIRTUEMART_ORDER_LIST_NOTIFY') ?>
			<br>
				<?php echo VmHTML::checkbox('include_comment', true); ?>&nbsp;<?php echo vmText::_('COM_VIRTUEMART_ORDER_HISTORY_INCLUDE_COMMENT') ?>
			<br>
				<?php echo VmHTML::checkbox('orders['.$this->orderID.'][update_lines]', true); ?>&nbsp;<?php echo vmText::_('COM_VIRTUEMART_ORDER_UPDATE_LINESTATUS') ?>
			</td>
		</tr>
	</table>
	</fieldset>

	<!-- Hidden Fields -->
	<input type="hidden" name="task" value="updatestatus" />
	<input type="hidden" name="last_task" value="updatestatus" />
	<input type="hidden" name="option" value="com_virtuemart" />
	<input type="hidden" name="view" value="orders" />
	<input type="hidden" name="coupon_code" value="<?php echo $this->orderbt->coupon_code; ?>" />
	<input type="hidden" name="current_order_status" value="<?php echo $this->currentOrderStat; ?>" />
	<input type="hidden" name="virtuemart_order_id" value="<?php echo $this->orderID; ?>" />
	<?php echo JHtml::_( 'form.token' ); ?>
</form>
