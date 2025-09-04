<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage ShopperGroup
* @author Markus �hler
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: default.php 10916 2023-09-27 14:01:59Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart/';
JLoader::register('vmuikitAdminUIHelper', $adminTemplate . 'helpers/vmuikit_adminuihelper.php');
vmuikitAdminUIHelper::startAdminArea($this);

?>

<form action="index.php?option=com_virtuemart&view=shoppergroup" method="post" name="adminForm" id="adminForm">
<?php if ($this->task=='massxref_sgrps' or $this->task=='massxref_sgrps_exe') : ?>
<div id="header">
<div id="massxref_task">
	<table class="">
		<tr>
			<td >
				<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_XREF_TASK') ?>
			</td>
			<td>
				<?php
				$options = array(
				'replace' => vmText::_('COM_VIRTUEMART_PRODUCT_XREF_TASK_REPLACE'),
				'add' => vmText::_('COM_VIRTUEMART_PRODUCT_XREF_TASK_ADD'),
				'remove' => vmText::_('COM_VIRTUEMART_PRODUCT_XREF_TASK_REMOVE')
				);
				echo VmHTML::selectList('massxref_task', 'add', $options);
				?>
			</td>
		</tr>
	</table>
</div>
</div>
<?php endif; ?>
  <div id="editcell">
	  <table class="uk-table uk-table-small uk-table-striped uk-table-responsive">
		<thead>
		  <tr>
			<th>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
			</th>
			<th >
				<?php echo vmText::_('COM_VIRTUEMART_SHOPPERGROUP_NAME'); ?>
			</th>
			<th>
				<?php echo vmText::_('COM_VIRTUEMART_SHOPPERGROUP_DESCRIPTION'); ?>
			</th>
			  <th class="uk-table-shrink">
				<?php echo vmText::_('COM_VIRTUEMART_DEFAULT'); ?>
			</th>
			  <th class="uk-table-shrink">
				<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED'); ?>
			</th>
			<?php if((Vmconfig::get('multix','none')!='none') && $this->showVendors){ ?>
				<th class="uk-table-shrink">
				<?php echo vmText::_('COM_VIRTUEMART_VENDOR'); ?>
			</th>
			<?php } ?>
			  <th class="uk-table-shrink">
				<?php echo vmText::_('COM_VIRTUEMART_ADDITIONAL'); ?>
			</th>
			  <th class="uk-table-shrink">
				<?php echo $this->sort('virtuemart_shoppergroup_id', 'COM_VIRTUEMART_ID')  ?>
			</th>
		  </tr>
		</thead><?php

		$k = 0;
		for ($i = 0, $n = count( $this->shoppergroups ); $i < $n; $i++) {
			$row = $this->shoppergroups[$i];
			$published = $this->gridPublished( $row, $i );

			$checked = '';
			if ($row->default == 0) {
				$checked = JHtml::_('grid.id', $i, $row->virtuemart_shoppergroup_id,null,'virtuemart_shoppergroup_id');
			}

			$editlink = JROUTE::_('index.php?option=com_virtuemart&view=shoppergroup&task=edit&virtuemart_shoppergroup_id=' . $row->virtuemart_shoppergroup_id);

			?>

		  <tr class="row<?php echo $k ; ?>">
			<td>
				<?php echo $checked; ?>
			</td>
			<td >
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHOPPERGROUP_NAME') ?>"
							uk-icon="icon: pencil"></span>
			  <a href="<?php echo $editlink; ?>"><?php echo vmText::_($row->shopper_group_name); ?></a>
			</td>
			<td >
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_SHOPPERGROUP_DESCRIPTION') ?>"
							uk-icon="icon: commenting"></span>
				<?php echo vmText::_($row->shopper_group_desc); ?>
			</td>
			<td class="uk-text-center@m">
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_DEFAULT') ?>"
							uk-icon="icon: star"></span>
				<?php
				if ($row->default != 0) {
					echo JHtml::_('image','admin/featured.png', vmText::_('COM_VIRTUEMART_SHOPPERGROUP_DEFAULT'), NULL, true);
				}
				?>
			</td>
			<td class="uk-text-center@m">
				<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
						uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED') ?>"
						uk-icon="icon: eye"></span>
				<?php echo $published; ?>
			</td>
			<?php if((Vmconfig::get('multix','none')!='none') && $this->showVendors){ ?>
			<td >
				<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
						uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_VENDOR') ?>"
						uk-icon="icon: shop"></span>
				<?php echo $row->virtuemart_vendor_id; ?>
			</td>
			<?php } ?>
			<td class="uk-text-center@m">
				<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
						uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ADDITIONAL') ?>"
						uk-icon="icon: plus"></span>
				<?php 
				if ($row->sgrp_additional == 1) {
					echo JHtml::_('image','admin/icon-16-add.png', vmText::_('COM_VIRTUEMART_SHOPPERGROUP_ADDITIONAL'), NULL, true);
				}
				?>
			</td>
			<td >
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_ID') ?>"
							uk-icon="icon: hashtag"></span>
				<?php echo $row->virtuemart_shoppergroup_id; ?>
			</td>
		  </tr><?php
			$k = 1 - $k;
		} ?>
		<tfoot>
		  <tr>
			<td colspan="10">
				<?php echo $this->sgrppagination->getListFooter(); ?>
			</td>
		  </tr>
		</tfoot>
	  </table>
  </div>

	<?php echo $this->addStandardHiddenToForm($this->_name,$this->task); ?>
</form><?php
vmuikitAdminUIHelper::endAdminArea(); ?>