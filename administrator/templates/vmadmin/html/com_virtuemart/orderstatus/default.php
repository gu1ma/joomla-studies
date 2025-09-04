<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage OrderStatus
 * @author Oscar van Eijk
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10750 2022-11-29 19:57:28Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart/';
JLoader::register('vmuikitAdminUIHelper', $adminTemplate . 'helpers/vmuikit_adminuihelper.php');
vmuikitAdminUIHelper::startAdminArea($this);
vmLanguage::loadJLang('com_virtuemart_config');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<div id="editcell">
		<table class="uk-table uk-table-small uk-table-striped uk-table-responsive">
			<thead>
			<tr>
				<th>
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)"/>
				</th>
				<th>
					<?php echo $this->sort('order_status_name') ?>
				</th>
				<th>
					<?php echo $this->sort('order_status_code') ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_VENDOR'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_SHOPPER'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_LAYOUT'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_ATTACHMENT'); ?>
				</th>

				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_CREATE_INVOICE'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_ALLOW_EDIT'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_STOCK_HANDLE'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_DO_REFUND'); ?>
				</th>
				<th>
					<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_DELIVERY_DATE'); ?>
				</th>
				<th class="uk-visible@m">
					<?php echo $this->sort('ordering') ?>
					<?php echo $this->saveOrder(); ?>
				</th>
				<th class="uk-table-shrink">
					<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED'); ?>
				</th>
				<th class="uk-table-shrink">
					<?php echo $this->sort('virtuemart_orderstate_id', 'COM_VIRTUEMART_ID') ?>
				</th>
			</tr>
			</thead>
			<?php
			$k = 0;

			for ($i = 0, $n = count($this->orderStatusList); $i < $n; $i++) {
				$row = $this->orderStatusList[$i];
				$published = $this->gridPublished($row, $i);

				$checked = JHtml::_('grid.id', $i, $row->virtuemart_orderstate_id);

				$coreStatus = (in_array($row->order_status_code, $this->lists['vmCoreStatusCode']));

				$checked = ($coreStatus) ?
					'<span   uk-tooltip="' . vmText::_('COM_VIRTUEMART_ORDER_STATUS_CODE_CORE') . '" class="icon-checkedout" ></span>' :
					JHtml::_('grid.id', $i, $row->virtuemart_orderstate_id);

				$editlink = JROUTE::_('index.php?option=com_virtuemart&view=orderstatus&task=edit&cid[]=' . $row->virtuemart_orderstate_id);
				$deletelink = JROUTE::_('index.php?option=com_virtuemart&view=orderstatus&task=remove&cid[]=' . $row->virtuemart_orderstate_id);
				$ordering = $row->ordering;
				$colorStyle = '';
				if ($row->order_status_color) {
					$colorStyle = 'style="background-color:' . $row->order_status_color . '"';
				}
				?>
				<tr class="row<?php echo $k; ?>">
					<td>
						<?php echo $checked; ?>
					</td>
					<td>
						<div class="uk-label uk-label-vm uk-width-1-1" <?php echo $colorStyle ?>>
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_NAME') ?>"
									uk-icon="icon: pencil"></span>
							<?php
							$lang = vmLanguage::getLanguage();
							if ($lang->hasKey($row->order_status_name)) {
								echo '<a href="' . $editlink . '">' . vmText::_($row->order_status_name) . '</a> (' . $row->order_status_name . ')';
							} else {
								echo '<a href="' . $editlink . '">' . $row->order_status_name . '</a> ';
							}
							?>
						</div>
					</td>
					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_CODE') ?>"
								uk-icon="icon: info"></span>
						<?php echo $row->order_status_code; ?>
					</td>

					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_VENDOR') ?>"
								uk-icon="icon: shop"></span>
						<?php
						if (in_array($row->order_status_code, VmConfig::get('email_os_v', array('U', 'C', 'R', 'X')))) {
							?>
							<span uk-icon="icon: mail"></span>
							<?php
						}
						?>
					</td>

					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_SHOPPER') ?>"
								uk-icon="icon: user"></span>
						<?php
						if (in_array($row->order_status_code, VmConfig::get('email_os_s', array('U', 'C', 'S', 'R', 'X')))) {
							?>
							<span uk-icon="icon: mail"></span>
							<?php
						}
						?>
					</td>
					<td >
						<?php echo $row->order_status_email_layout; ?>
					</td>


					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_EMAIL_ATTACHMENT') ?>"
								uk-icon="icon: file"></span>
						<?php
						if (in_array($row->order_status_code, VmConfig::get('attach_os', array('')))) {
							?>
							<span class="md-color-green-800" uk-icon="icon: file"></span>
							<?php
						}
						?>
					</td>

					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_CREATE_INVOICE') ?>"
								uk-icon="icon: file-pdf"></span>
						<?php
						if (in_array($row->order_status_code, VmConfig::get('inv_os', array('C')))) {
							if (in_array($row->order_status_code, VmConfig::get('refund_os', array('R')))) {
								?>
								<span class="md-color-red-800" uk-icon="icon: file-pdf"></span>
								<?php
							} else {
								?>
								<span class="md-color-green-800" uk-icon="icon: file-pdf"></span>
								<?php
							}
						}
						?>
					</td>

					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_ALLOW_EDIT') ?>"
								uk-icon="icon: pencil"></span>
						<?php
						if (in_array($row->order_status_code, VmConfig::get('order_allowedit_os', array('P', 'U')))) {
							?>
							<span class="md-color-green-800" uk-icon="icon: pencil"></span>
							<?php
						} else {
							?>
							<span class="" uk-icon="icon: lock"></span>
							<?php
						}
						?>
					</td>

					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_STOCK_HANDLE') ?>"
								uk-icon="icon: inventory"></span>
						<?php echo vmText::_($this->stockHandelList[$row->order_stock_handle]); ?>
					</td>
					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_DO_REFUND') ?>"
								uk-icon="icon: pencil"></span>
						<?php
						if (in_array($row->order_status_code, VmConfig::get('refund_os', array('R')))) {
							?>
							<span class="md-color-red-800" uk-icon="icon: reply"></span>
							<?php
						}
						?>
					</td>
					<td>
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ORDER_STATUS_DELIVERY_DATE') ?>"
								uk-icon="icon: calendar"></span>
						<?php
						$del_date_type = VmConfig::get('del_date_type', array('m'));
						if ($del_date_type == 'm') {
							$del_date_type = VmConfig::get('inv_os', array('C'));
						}
						if (!is_array($del_date_type)) {
							$del_date_type = array($del_date_type);
						}
						if (in_array($row->order_status_code, $del_date_type)) {
							?>
							<span class="md-color-green-800" uk-icon="icon: calendar"></span>
							<?php
						}
						?>
					</td>
					<td class="order uk-visible@m">
						<span><?php echo $this->pagination->vmOrderUpIcon($i, $row->ordering, 'orderUp', vmText::_('COM_VIRTUEMART_MOVE_UP')); ?></span>
						<span><?php echo $this->pagination->vmOrderDownIcon($i, $row->ordering, $n, true, 'orderDown', vmText::_('COM_VIRTUEMART_MOVE_DOWN')); ?></span>
						<input class="ordering" type="text" name="order[<?php echo $row->virtuemart_orderstate_id ?>]" id="order[<?php echo $i ?>]"
								size="5" value="<?php echo $row->ordering; ?>" />
					</td>
					<td class="uk-text-center@m">
						<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED') ?>"
								uk-icon="icon: eye"></span>
						<?php echo $published; ?>
					</td>
					<td class="uk-text-center@m">
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ID') ?>"
									uk-icon="icon: hashtag"></span>
						<?php echo $row->virtuemart_orderstate_id; ?>
					</td>
				</tr>
				<?php
				$k = 1 - $k;
			}
			?>
		</table>
	</div>

	<?php echo $this->addStandardHiddenToForm(); ?>
</form>

<?php vmuikitAdminUIHelper::endAdminArea(); ?>
