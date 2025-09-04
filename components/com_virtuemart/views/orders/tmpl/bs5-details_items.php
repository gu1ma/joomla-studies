<?php

/**
*
* Order items view
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
* @version $Id: details_items.php 10649 2022-05-05 14:29:44Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

if ($this->format == 'pdf'){
	$widthTable = '100%';
	$widthTitle = '27%';
} else {
	$widthTable = '';
	$widthTitle = '';
}

$colspan = 5;
?>
<div class="table-responsive">
	<table class="table" width="<?php echo $widthTable ?>">
		<tr>
			<th><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SKU'); ?></th>
			<th width="<?php echo $widthTitle ?>"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_NAME_TITLE'); ?></th>
			<th><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_STATUS'); ?></th>
			<th class="text-end"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRICE'); ?></th>
			<th class="text-end"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_QTY'); ?></th>
			<?php if ( VmConfig::get('show_tax')) : ?>
				<th class="text-end"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_TAX'); ?></th>
			<?php endif; ?>
			<th class="text-end"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SUBTOTAL_DISCOUNT_AMOUNT'); ?></th>
			<th class="text-end"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL'); ?></th>
		</tr>

		<?php foreach($this->orderdetails['items'] as $item) : ?>
			<?php
				$qtt = $item->product_quantity;
				$_link = Route::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $item->virtuemart_category_id . '&virtuemart_product_id=' . $item->virtuemart_product_id, FALSE);
			?>
			<tr>
				<td><?php echo $item->order_item_sku; ?></td>
				<td>
					<p><a href="<?php echo $_link; ?>"><?php echo $item->order_item_name; ?></a></p>
					<?php
						$product_attribute = VirtueMartModelCustomfields::CustomsFieldOrderDisplay($item,'FE');
						echo $product_attribute;
					?>
				</td>
				<td><?php echo $this->orderstatuses[$item->order_status]; ?></td>
				<td class="text-end text-nowrap">
					<?php
					$item->product_discountedPriceWithoutTax = (float) $item->product_discountedPriceWithoutTax;

					if (!empty($item->product_priceWithoutTax) && $item->product_discountedPriceWithoutTax != $item->product_priceWithoutTax) {
						echo '<span class="line-through">' . $this->currency->priceDisplay($item->product_item_price, $this->user_currency_id) . '</span><br />';
						echo '<span>' . $this->currency->priceDisplay($item->product_discountedPriceWithoutTax, $this->user_currency_id) . '</span><br />';
					} else {
						echo '<span>' . $this->currency->priceDisplay($item->product_item_price, $this->user_currency_id) . '</span><br />';
					}
					?>
				</td>
				<td class="text-end"><?php echo $qtt; ?></td>
				<?php if (VmConfig::get('show_tax')) : ?>
					<td class="text-end"><?php echo '<span class="priceColor2 text-nowrap">' . $this->currency->priceDisplay($item->product_tax ,$this->user_currency_id, $qtt) . '</span>'; ?></td>
				<?php endif; ?>
				<td class="text-end text-nowrap"><?php echo  $this->currency->priceDisplay( $item->product_subtotal_discount ,$this->user_currency_id);  //No quantity is already stored with it ?></td>
				<td class="text-end text-nowrap">
					<?php
					$item->product_basePriceWithTax = (float) $item->product_basePriceWithTax;
					$class = '';

					if (!empty($item->product_basePriceWithTax) && $item->product_basePriceWithTax != $item->product_final_price) {
						echo '<span class="line-through" >' . $this->currency->priceDisplay($item->product_basePriceWithTax,$this->user_currency_id,$qtt) . '</span><br />';
					} elseif (empty($item->product_basePriceWithTax) && $item->product_item_price != $item->product_final_price) {
						echo '<span class="line-through">' . $this->currency->priceDisplay($item->product_item_price,$this->user_currency_id,$qtt) . '</span><br />';
					}

					echo $this->currency->priceDisplay(  $item->product_subtotal_with_tax ,$this->user_currency_id); //No quantity or you must use product_final_price ?>
				</td>
			</tr>
		<?php endforeach; ?>

		<tr>
			<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_PRICES_TOTAL'); ?></td>
			<?php if ( VmConfig::get('show_tax')) : ?>
				<td class="text-end"><?php echo '<span class="priceColor2 text-nowrap">' . $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_tax,$this->user_currency_id) . '</span>'; ?></td>
			<?php endif; ?>
			<td class="text-end">
				<?php echo '<span class="priceColor2 text-nowrap">' . $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_discountAmount,$this->user_currency_id) . '</span>'; ?>
			</td>
			<td class="text-end text-nowrap">
				<?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_salesPrice,$this->user_currency_id) ?>
			</td>
		</tr>

		<?php if ($this->orderdetails['details']['BT']->coupon_discount <> 0.00) : ?>
			<?php $coupon_code=$this->orderdetails['details']['BT']->coupon_code ? ' ('.$this->orderdetails['details']['BT']->coupon_code.')' : ''; ?>
			<tr>
				<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo vmText::_('COM_VIRTUEMART_COUPON_DISCOUNT').$coupon_code ?></td>
				<?php if ( VmConfig::get('show_tax')) : ?>
					<td class="text-end">&nbsp;</td>
				<?php endif; ?>
				<td class="text-end">&nbsp;</td>
				<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->coupon_discount,$this->user_currency_id); ?></td>
			</tr>
		<?php endif; ?>

		<?php foreach ($this->orderdetails['calc_rules'] as $rule) : ?>
			<?php if ($rule->calc_kind== 'DBTaxRulesBill') : ?>
				<tr>
					<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo $rule->calc_rule_name ?></td>
					<?php if (VmConfig::get('show_tax')) : ?>
						<td class="text-end">&nbsp;</td>
					<?php endif; ?>
					<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($rule->calc_amount,$this->user_currency_id); ?></td>
					<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($rule->calc_amount,$this->user_currency_id); ?></td>
				</tr>
			<?php elseif ($rule->calc_kind == 'taxRulesBill') : ?>
				<tr>
					<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo $rule->calc_rule_name ?></td>
					<?php if (VmConfig::get('show_tax')) : ?>
						<td class="text-end"><?php echo $this->currency->priceDisplay($rule->calc_amount,$this->user_currency_id); ?></td>
					<?php endif; ?>
					<td class="text-end">&nbsp;</td>
					<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($rule->calc_amount,$this->user_currency_id); ?></td>
				</tr>
			<?php elseif ($rule->calc_kind == 'DATaxRulesBill') : ?>
				<tr>
					<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo $rule->calc_rule_name ?></td>
					<?php if (VmConfig::get('show_tax')) { ?>
					<td class="text-end">&nbsp;</td>
					 <?php } ?>
					<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($rule->calc_amount,$this->user_currency_id); ?></td>
					<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($rule->calc_amount,$this->user_currency_id); ?></td>
				</tr>
			<?php endif; ?>
		<?php endforeach; ?>

		<tr>
			<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SHIPPING') ?></td>
			<?php if (VmConfig::get('show_tax')) : ?>
				<td class="text-end"><?php echo "<span  class='priceColor2 text-nowrap'>".$this->currency->priceDisplay($this->orderdetails['details']['BT']->order_shipment_tax, $this->user_currency_id)."</span>" ?></td>
			<?php endif; ?>
			<td class="text-end">&nbsp;</td>
			<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_shipment+ $this->orderdetails['details']['BT']->order_shipment_tax, $this->user_currency_id); ?></td>
		</tr>

		<tr>
			<td class="text-end" colspan="<?php echo $colspan; ?>"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PAYMENT') ?></td>
			<?php if ( VmConfig::get('show_tax')) : ?>
				<td class="text-end"><?php echo "<span  class='priceColor2 text-nowrap'>".$this->currency->priceDisplay($this->orderdetails['details']['BT']->order_payment_tax, $this->user_currency_id)."</span>" ?></td>
			<?php endif; ?>
			<td class="text-end">&nbsp;</td>
			<td class="text-end text-nowrap"><?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_payment+ $this->orderdetails['details']['BT']->order_payment_tax, $this->user_currency_id); ?></td>
		</tr>

		<tr>
			<td class="text-end" colspan="<?php echo $colspan; ?>"><strong><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL') ?></strong></td>
			<?php if (VmConfig::get('show_tax')) : ?>
				<td class="text-end"><span  class='priceColor2 text-nowrap'><?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_billTaxAmount, $this->user_currency_id); ?></span></td>
			<?php endif; ?>
			<td class="text-end text-nowrap"><span  class='priceColor2'><?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_billDiscountAmount, $this->user_currency_id); ?></span></td>
			<td class="text-end text-nowrap"><strong><?php echo $this->currency->priceDisplay($this->orderdetails['details']['BT']->order_total, $this->user_currency_id); ?></strong></td>
		</tr>

		<tr>
			<td class="text-end" colspan="<?php echo $colspan; ?>"><strong><?php echo vmText::_('COM_VM_ORDER_BALANCE') ?></strong></td>

			<?php
				$this->orderbt = $this->orderdetails['details']['BT'];
				$tp = '';
				$detail = false;
				$colspan = VmConfig::get('show_tax') ? '3' : '2';

				if (empty($this->orderbt->paid) ) {
				  $t = vmText::_('COM_VM_ORDER_UNPAID');
				} else if($this->orderbt->paid == $this->orderbt->toPay){
				  $t =  vmText::_('COM_VM_ORDER_PAID');
				} else if($this->orderbt->paid < $this->orderbt->toPay){
				  $t =  vmText::sprintf('COM_VM_ORDER_PARTIAL_PAID',$this->orderbt->paid);
				  $detail=true;
				} else {
				  $t =  vmText::sprintf('COM_VM_ORDER_CREDIT_BALANCE',$this->orderbt->paid);
				  $detail=true;
				}

				if (empty($this->toRefund) and !$detail) {
				  echo '<td class="text-end" colspan="' . $colspan . '">'.$t.'</td>';
				  echo '</tr>';
				}

				if (!empty($this->toRefund)) {
					echo '<td class="text-end" colspan="' . $colspan . '">'.vmText::_('COM_VM_ORDER_PRODUCTS_TO_REFUND').'</td>';
					echo '</tr>';

					foreach ($this->toRefund as $index => $item) {
						$tmpl = "refund-tmpl-" . $index;

						echo '<tr id="'.$tmpl.'" class="order-item">';
						echo '<td>' . $item->order_item_sku . '</td>';
						echo '<td>' . $item->order_item_name . '</td>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td></td>';
						if (VmConfig::get('show_tax')) {
							echo '<td class="text-end text-nowrap">'.$this->currency->priceDisplay($item->product_tax).'</td>';
						}
						echo '<td></td>';
						echo '<td class="text-end text-nowrap">'.$this->currency->priceDisplay($item->product_subtotal_with_tax).'</td>';
						echo '</tr>';

						$this->orderbt->order_total -= $item->product_subtotal_with_tax;
					}
				}
			?>

			<?php
				if (!empty($this->toRefund) or $detail) {
					if ($this->orderbt->paid < $this->orderbt->toPay) {
						if (empty($this->orderbt->paid)){
						$t =  vmText::_('COM_VM_ORDER_UNPAID');
						} else {
						$t =  vmText::_('COM_VM_ORDER_PARTIAL_PAID');
						}

						$l = vmText::_('COM_VM_ORDER_OUTSTANDING_AMOUNT');
					} else {
						$t =  vmText::_('COM_VM_ORDER_PAID');
						$l = vmText::_('COM_VM_ORDER_BALANCE');
					}

					$tp = '';

					if ($this->orderbt->toPay != $this->orderbt->order_total) {
						$tp .= '<tr>';
						$tp .= '<td class="text-end fw-bold" colspan="5">'.vmText::_('COM_VM_ORDER_NEW_TOTAL').'</td>';
						$tp .= '<td class="text-end text-nowrap" colspan="' . $colspan . '">'.$this->currency->priceDisplay($this->orderbt->toPay).'</td>';
						$tp .= '</tr>';
					}

					$tp .= '<tr>';
					$tp .= '<td class="text-end fw-bold" colspan="5">'.$t.'</td>';
					$tp .= '<td class="text-end text-nowrap" colspan="' . $colspan . '">'.$this->currency->priceDisplay($this->orderbt->paid).'</td>';
					$tp .= '</tr>';

					$tp .= '<tr>';
					$tp .= '<td class="text-end fw-bold" colspan="5">'.$l.'</td>';
					$tp .= '<td class="text-end text-nowrap" colspan="' . $colspan . '">'.$this->currency->priceDisplay(abs($this->orderbt->order_total - $this->orderbt->paid) ).'</td>';
					$tp .= '</tr>';

					echo $tp;
				}
			?>
	</table>
</div>