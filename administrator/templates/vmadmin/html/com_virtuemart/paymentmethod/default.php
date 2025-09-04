<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage Paymentmethod
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2022 The VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10643 2022-04-25 10:46:29Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart/';
JLoader::register('vmuikitAdminUIHelper', $adminTemplate . 'helpers/vmuikit_adminuihelper.php');
vmuikitAdminUIHelper::startAdminArea($this);

?>

	<form action="index.php" method="post" name="adminForm" id="adminForm">
		<div id="filterbox" class="filter-bar">
		<?php
		$extras = array();
		if ($this->showVendors()) {
			$extras[] = $this->lists['vendors'];
		}
		
		echo adminSublayouts::renderAdminVmSubLayout('filterbar',
			array(
				'search' => array(
					'label' => 'COM_VIRTUEMART_NAME',
					'name' => 'search',
					'value' => vRequest::getVar('search')
				),
				'extras' => $extras,
				'resultsCounter' => $this->pagination->getResultsCounter(),
				'limitBox' => $this->pagination->getLimitBox()
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
						<?php echo $this->sort('l.payment_name', 'COM_VIRTUEMART_PAYMENT_LIST_NAME'); ?>
					</th>
					<th>
						<?php echo vmText::_('COM_VIRTUEMART_PAYMENT_LIST_DESCRIPTION_LBL'); ?>
					</th>
					<?php if ($this->showVendors()) { ?>
						<th>
						<?php echo $this->sort('i.virtuemart_vendor_id', 'COM_VIRTUEMART_VENDOR'); ?>
						</th><?php } ?>

					<th>
						<?php echo vmText::_('COM_VIRTUEMART_PAYMENT_SHOPPERGROUPS'); ?>
					</th>
					<th>
						<?php echo $this->sort('i.payment_element', 'COM_VIRTUEMART_PAYMENT_ELEMENT'); ?>
					</th>
					<th class="uk-visible@m">
						<?php echo $this->sort('i.ordering', 'COM_VIRTUEMART_LIST_ORDER'); ?>
					</th>
					<th class="uk-table-shrink">
						<?php echo $this->sort('i.published', 'COM_VIRTUEMART_PUBLISHED'); ?>
					</th>
					<?php if ($this->showVendors) { ?>
						<th class="uk-table-shrink">
							<?php echo vmText::_('COM_VIRTUEMART_SHARED'); ?>
						</th>
					<?php } ?>
					<th class="uk-table-shrink">
						<?php echo $this->sort('i.virtuemart_paymentmethod_id', 'COM_VIRTUEMART_ID') ?>
					</th>
				</tr>
				</thead>
				<?php
				$k = 0;

				for ($i = 0, $n = count($this->payments); $i < $n; $i++) {

					$row = $this->payments[$i];
					$checked = JHtml::_('grid.id', $i, $row->virtuemart_paymentmethod_id);
					$published = $this->gridPublished($row, $i);
					if ($this->showVendors) {
						$shared = $this->toggle($row->shared, $i, 'toggle.shared');
					}
					$editlink = JROUTE::_('index.php?option=com_virtuemart&view=paymentmethod&task=edit&cid[]=' . $row->virtuemart_paymentmethod_id);
					if (empty($row->payment_name)) {
						$row->payment_name = vmText::sprintf('COM_VM_TRANSLATION_MISSING', 'virtuemart_paymentmethod_id', $row->virtuemart_paymentmethod_id);
					}
					?>
					<tr class="<?php echo "row" . $k; ?>">
						<td>
							<?php echo $checked; ?>
						</td>
						<td>
							<div class="uk-label uk-label-vm uk-width-1-1">
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PAYMENT_LIST_NAME') ?>"
										uk-icon="icon: pencil"></span>
								<a href="<?php echo $editlink; ?>"><?php echo $row->payment_name; ?></a>
							</div>
						</td>
						<td>
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PAYMENT_LIST_DESCRIPTION_LBL') ?>"
									uk-icon="icon: commenting"></span>
							<?php echo $row->payment_desc; ?>
						</td>
						<?php if ($this->showVendors()) { ?>
							<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_VENDOR') ?>"
										uk-icon="icon: shop"></span>
								<?php echo vmText::_($row->virtuemart_vendor_id); ?>
							</td>
						<?php } ?>
						<td>
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PAYMENT_SHOPPERGROUPS') ?>"
									uk-icon="icon: users"></span>
							<?php echo $row->paymShoppersList; ?>
						</td>
						<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PAYMENT_ELEMENT') ?>"
										uk-icon="icon: question"></span>
							<?php echo $row->payment_element; ?>
						</td>
						<td class="uk-visible@m">
							<?php echo $row->ordering; ?>
						</td>
						<td class="uk-text-center@m">
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED') ?>"
									uk-icon="icon: eye"></span>
							<?php echo $published; ?>
						</td>
						<?php if ($this->showVendors) { ?>
							<td class="uk-text-center@m">
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHARED') ?>"
										uk-icon="icon: question"></span>
								<?php echo $shared; ?>
							</td>
						<?php } ?>
						<td class="uk-text-center@m">
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ID') ?>"
									uk-icon="icon: hashtag"></span>
							<?php echo $row->virtuemart_paymentmethod_id; ?>
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