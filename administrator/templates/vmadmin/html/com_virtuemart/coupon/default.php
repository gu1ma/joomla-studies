<?php
/**
 *
 *
 * @package    VirtueMart
 * @subpackage Coupon
 * @author RickG
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart/';
JLoader::register('vmuikitAdminUIHelper', $adminTemplate . 'helpers/vmuikit_adminuihelper.php');
vmuikitAdminUIHelper::startAdminArea($this);

?>

	<form action="index.php?option=com_virtuemart&view=coupon" method="post" name="adminForm" id="adminForm">
		<div id="filterbox" class="filter-bar">
			<?php
			$extras = array();
			if ($this->showVendors()) {
				$extras[] = $this->vendorList;
			}
			$extras[] = '
<div id="coupon_usage_cont" >
	<a  href="index.php?option=com_virtuemart&view=coupon&cid[]=6&layout=couponsdata" class="uk-button uk-button-small uk-button-default"><span uk-icon="forward" class="uk-margin-small-right"></span>' .
				vmText::_('Coupon Usage / Analytics') .
				'</a>
</div>
';
			echo adminSublayouts::renderAdminVmSubLayout('filterbar',
				array(
					'search' => array(
						'label' => 'COM_VIRTUEMART_COUPON',
						'name' => 'filter_coupon',
						'value' => vRequest::getVar('filter_coupon')
					),
					'extras' => $extras,
					'resultsCounter' => $this->pagination->getResultsCounter()
				));


			?>

		</div>
		<div id="editcell">
			<table class="uk-table uk-table-small uk-table-striped uk-table-responsive">
				<thead>
				<tr>
					<th class="uk-table-shrink">
						<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)"/>
					</th>
					<th>
						<?php echo $this->sort('coupon_code', 'COM_VIRTUEMART_COUPON_CODE'); ?>
					</th>
					<th>
						<?php echo $this->sort('percent_or_total', 'COM_VIRTUEMART_COUPON_PERCENT_TOTAL'); ?>
					</th>
					<th>
						<?php echo $this->sort('coupon_type', 'COM_VIRTUEMART_COUPON_TYPE'); ?>
					</th>
					<th>
						<?php echo $this->sort('coupon_value', 'COM_VIRTUEMART_VALUE'); ?>
					</th>
					<th>
						<?php echo vmText::_('COM_VIRTUEMART_COUPON_VALUE_VALID_AT'); ?>
					</th>
					<th>
						<?php echo $this->sort('coupon_used', 'COM_VIRTUEMART_COUPON_USED'); ?>
					</th>
					<th><?php echo $this->sort('coupon_start_date', 'COM_VIRTUEMART_START_DATE'); ?></th>
					<th><?php echo $this->sort('coupon_expiry_date', 'COM_VIRTUEMART_END_DATE'); ?></th>
					<th class="uk-table-shrink">
						<?php echo $this->sort('published', 'COM_VIRTUEMART_PUBLISHED') ?>
					</th>
					<th class="uk-table-shrink">
						<?php echo $this->sort('virtuemart_coupon_id', 'COM_VIRTUEMART_ID') ?>
					</th>
				</tr>
				</thead>
				<?php
				$k = 0;
				for ($i = 0, $n = count($this->coupons); $i < $n; $i++) {
					$row = $this->coupons[$i];

					$checked = JHtml::_('grid.id', $i, $row->virtuemart_coupon_id);
					$editlink = JROUTE::_('index.php?option=com_virtuemart&view=coupon&task=edit&cid[]=' . $row->virtuemart_coupon_id);
					$published = $this->gridPublished($row, $i);
					?>
					<tr class="row<?php echo $k; ?>">
						<td>
							<?php echo $checked; ?>
						</td>
						<td>
									<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_COUPON_CODE') ?>"
										uk-icon="icon: pencil"></span>
							<a href="<?php echo $editlink; ?>"><?php echo $row->coupon_code; ?></a>
						</td>
						<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_COUPON_PERCENT_TOTAL') ?>"
										uk-icon="icon: tag"></span>
							<?php echo vmText::_('COM_VIRTUEMART_COUPON_' . strtoupper($row->percent_or_total)); ?>
						</td>
						<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_COUPON_TYPE') ?>"
										uk-icon="icon: info"></span>
							<?php echo vmText::_('COM_VIRTUEMART_COUPON_TYPE_' . strtoupper($row->coupon_type)); ?>
						</td>
						<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_VALUE') ?>"
										uk-icon="icon: tag"></span>
							<?php echo vmText::_($row->coupon_value); ?>
							<?php if ($row->percent_or_total == 'percent') {
								echo '%';
							} else {
								echo $this->vendor_currency;
							} ?>
						</td>
						<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_COUPON_VALUE_VALID_AT') ?>"
										uk-icon="icon: minus"></span>
							<?php echo vmText::_($row->coupon_value_valid); ?><?php echo $this->vendor_currency; ?>
						</td>
						<td class="uk-text-center@m">
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_COUPON_USED') ?>"
										uk-icon="icon: check"></span>
							<?php
							if ($row->coupon_type == 'gift') {
								if ($row->coupon_used) {
									echo vmText::_('COM_VIRTUEMART_YES');
								} else {
									echo vmText::_('COM_VIRTUEMART_NO');
								}
							}
							?>
						</td>
						<td>
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_START_DATE') ?>"
									uk-icon="icon: clock"></span>
							<?php
							echo vmJsApi::date($row->coupon_start_date, 'LC4', true);
							?>
						</td>
						<td>
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_END_DATE') ?>"
									uk-icon="icon: future"></span>
							<?php
							echo vmJsApi::date($row->coupon_expiry_date, 'LC4', true);
							?>
						</td>
						<td class="uk-text-center@m">
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED') ?>"
									uk-icon="icon: eye"></span>
							<?php echo $published; ?>
						</td>
						<td>
											<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
													uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ID') ?>"
													uk-icon="icon: hashtag"></span>
							<?php echo vmText::_($row->virtuemart_coupon_id); ?>
						</td>
					</tr>
					<?php
					$k = 1 - $k;
				}
				?>
				<tfoot>
				<tr>
					<td colspan="10">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
			</table>
		</div>
		<?php echo $this->addStandardHiddenToForm(); ?>

	</form>


<?php vmuikitAdminUIHelper::endAdminArea(); ?>