<?php
/**
 *
 * Popup form to edit the formstatus
 *
 * @package	VirtueMart
 * @subpackage Orders
 * @author Oscar van Eijk, Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: order_editstatus.php 10972 2024-01-26 17:33:10Z  $
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

vmJsApi::addJScript( 'orderstatus', "

		function cancelOrderStatFormEdit(e) {
			jQuery('#orderStatForm').each(function(){
				this.reset();
			});
//quorvia jan 2024
			jQuery('#order_edit_status')
				.find('option:selected').prop('selected', true)
				.end().trigger('liszt:updated');
//			jQuery('div#updateOrderStatus').hide();
			e.preventDefault();
		}

		");
?>

<form action="index.php" method="post" name="orderStatForm" id="orderStatForm">
	<div class="uk-card   uk-card-small uk-card-vm">
		<div class="uk-card-header">

			<div class="uk-card-title"><span class="md-color-cyan-600 uk-margin-small-right"
						uk-icon="icon: comment; ratio: 1.2"></span><?php echo vmText::_('COM_VIRTUEMART_ORDER_UPDATE_STATUS') ?>
			</div>
		</div>
		<div class="uk-card-body">

			<table class="uk-table uk-table-small" >

				<tr>
					<td class="key"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_STATUS') ?></td>
					<td>
						<?php
						//quorvia list to reflect the order list method of display march 2024
						//						echo $this->orderStatSelect;
						echo JHtml::_ ('select.genericlist', $this->orderEditstatuses, 'order_status', 'class="orderstatus_select"', 'order_status_code', 'order_status_name', $this->orderbt->order_status, 'order_edit_status', TRUE);
						?>
					</td>
				</tr>
				<tr>
					<td class="key"><?php echo vmText::_('COM_VIRTUEMART_COMMENT') ?></td>
					<td>
						<textarea rows="6" cols="35" name="comments"></textarea>
					</td>
				</tr>
				<tr>
					<td class="key"><?php echo vmText::_('COM_VIRTUEMART_ORDER_LIST_NOTIFY') ?></td>
<!--					quorvia 2024 set to false-->
					<td><?php echo VmHTML::checkbox('customer_notified', false); ?>
					</td>
				</tr>
				<tr>
					<td class="key"><?php echo vmText::_('COM_VIRTUEMART_ORDER_HISTORY_INCLUDE_COMMENT') ?></td>
					<td><br />
						<?php echo VmHTML::checkbox('include_comment', true); ?>
					</td>
				</tr>
				<tr>
					<td class="key"><?php echo vmText::_('COM_VIRTUEMART_ORDER_UPDATE_LINESTATUS') ?></td>
					<td><br />
						<?php echo VmHTML::checkbox('orders['.$this->orderID.'][update_lines]', true); ?>
					</td>
				</tr>

			</table>

		</div>
		<div class="uk-card-footer uk-text-center">


			<div class="uk-inline">
				<a href="#" class="orderStatFormSubmit uk-button uk-button-small uk-button-primary" ><?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?></a>
				<a href="#updateOrderStatusDropdown" class="uk-button uk-button-small uk-button-default" uk-toggle><?php echo vmText::_('COM_VIRTUEMART_CANCEL'); ?></a>
			</div>

		</div>
	</div>



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
