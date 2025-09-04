<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage Shipment
 * @author RickG
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10992 2024-04-08 20:43:50Z  $
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
			<table class="uk-table  uk-table-small uk-table-striped uk-table-responsive">
				<thead>
				<tr>
					<th>
						<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)"/>
					</th>
					<th>
						<?php echo $this->sort('l.shipment_name', 'COM_VIRTUEMART_SHIPMENT_NAME_LBL'); ?>
					</th>
					<th>
						<?php echo vmText::_('COM_VIRTUEMART_SHIPMENT_LIST_DESCRIPTION_LBL'); ?>
					</th>
					<th >
						<?php echo vmText::_('COM_VIRTUEMART_SHIPPING_SHOPPERGROUPS'); ?>
					</th>
					<th>
						<?php echo $this->sort('i.shipment_element', 'COM_VIRTUEMART_SHIPMENTMETHOD'); ?>
					</th>
					<th class="uk-visible@m">
						<?php echo $this->sort('i.ordering', 'COM_VIRTUEMART_LIST_ORDER'); ?>
					</th>
					<th class="uk-table-shrink">
						<?php echo $this->sort('i.published', 'COM_VIRTUEMART_PUBLISHED'); ?>
					</th>
					<?php if ($this->showVendors()) { ?>
						<th class="uk-table-shrink">
						<?php echo vmText::_('COM_VIRTUEMART_SHARED') ?>
						</th><?php } ?>
					<th class="uk-table-shrink">
						<?php echo $this->sort('i.virtuemart_shipmentmethod_id', 'COM_VIRTUEMART_ID') ?>
					</th>
				</tr>
				</thead>
				<?php
				$k = 0;
				$set_automatic_shipment = VmConfig::get('set_automatic_shipment', false);
				for ($i = 0, $n = count($this->shipments); $i < $n; $i++) {
					$row = $this->shipments[$i];
					$published = $this->gridPublished($row, $i);
					//$row->published = 1;
					$checked = JHtml::_('grid.id', $i, $row->virtuemart_shipmentmethod_id);
					if ($this->showVendors) {
						$shared = $this->toggle($row->shared, $i, 'toggle.shared');
					}
					$editlink = JROUTE::_('index.php?option=com_virtuemart&view=shipmentmethod&task=edit&cid[]=' . $row->virtuemart_shipmentmethod_id);
					if (empty($row->shipment_name)) {
						$row->shipment_name = vmText::sprintf('COM_VM_TRANSLATION_MISSING', 'virtuemart_shipment_id', $row->virtuemart_shipmentmethod_id);
					}

//			quorvia display shipment method color style
					$colorStyle = '';
					if (!empty($row->display_color)) {
						$colorStyle = 'style="background-color:' . $row->display_color . '"';
					}
					?>

					<tr class="row<?php echo $k; ?>">
						<td >
							<?php echo $checked; ?>
						</td>
						<td >
							<div class="uk-label uk-label-vm uk-width-1-1" <?php echo $colorStyle ?>>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHIPMENT_NAME_LBL') ?>"
										uk-icon="icon: pencil"></span>

							<?php echo JHtml::_('link', $editlink, vmText::_($row->shipment_name)); ?>
							<?php if ($set_automatic_shipment == $row->virtuemart_shipmentmethod_id) {
								?>
								<span class="uk-hidden@m uk-margin-small-left md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('') ?>"
										uk-icon="icon: heart"></span>
								<?php
							}
							?>
							</div>
						</td>
						<td >
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHIPMENT_LIST_DESCRIPTION_LBL') ?>"
										uk-icon="icon: commenting"></span>
							<?php echo $row->shipment_desc; ?>
						</td>
						<td>
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHIPPING_SHOPPERGROUPS') ?>"
										uk-icon="icon: users"></span>
							<?php echo $row->shipmentShoppersList; ?>
						</td>
						<td >
								<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
										uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHIPMENTMETHOD') ?>"
										uk-icon="icon: question"></span>
							<?php echo $row->shipment_element; //JHtml::_('link', $editlink, vmText::_($row->shipment_element)); ?>
						</td>
						<td class="uk-visible@m">
							<?php echo vmText::_($row->ordering); ?>
						</td>
						<td>
							<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
									uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED') ?>"
									uk-icon="icon: eye"></span>
							<?php echo $published; ?>
						</td>
						<?php
						if ($this->showVendors) {
							?>
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
							<?php echo $row->virtuemart_shipmentmethod_id; ?>
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