<?php
/**
 *
 *
 * @package    VirtueMart
 * @subpackage Manufacturer Category
 * @author Patrick Kohl
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

	<form action="index.php?option=com_virtuemart&view=manufacturercategories" method="post" name="adminForm"
			id="adminForm">
		<div id="editcell">
			<table class="uk-table uk-table-small uk-table-striped uk-table-responsive">
				<thead>
				<tr>
					<th>
						<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)"/>
					</th>
					<th>
						<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_CATEGORY_NAME'); ?>
					</th>
					<th>
						<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_CATEGORY_DESCRIPTION'); ?>
					</th>
					<th>
						<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_CATEGORY_LIST'); ?>
					</th>
					<th class="uk-table-shrink uk-text-center@m">
						<?php echo vmText::_('COM_VIRTUEMART_PUBLISHED'); ?>
					</th>
					<th class="uk-table-shrink uk-text-center@m">
						<?php echo $this->sort('virtuemart_manufacturercategories_id', 'COM_VIRTUEMART_ID') ?>
					</th>
				</tr>
				</thead>
				<?php
				$k = 0;
				for ($i = 0, $n = count($this->manufacturerCategories); $i < $n; $i++) {
					$row = $this->manufacturerCategories[$i];

					$checked = JHtml::_('grid.id', $i, $row->virtuemart_manufacturercategories_id);
					$published = $this->gridPublished($row, $i);

					$editlink = JROUTE::_('index.php?option=com_virtuemart&view=manufacturercategories&task=edit&virtuemart_manufacturercategories_id=' . $row->virtuemart_manufacturercategories_id);
					$manufacturersList = JROUTE::_('index.php?option=com_virtuemart&view=manufacturer&virtuemart_manufacturercategories_id=' . $row->virtuemart_manufacturercategories_id);

					?>
					<tr class="row<?php echo $k; ?>">
						<td>
							<?php echo $checked; ?>
						</td>
						<td>
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_CATEGORY_NAME') ?>"
							uk-icon="icon: pencil"></span>
							<a href="<?php echo $editlink; ?>"><?php echo $row->mf_category_name; ?></a>

						</td>
						<td>
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_CATEGORY_DESCRIPTION') ?>"
							uk-icon="icon: commenting"></span>
							<?php echo vmText::_($row->mf_category_desc); ?>
						</td>
						<td>
					<span class="uk-hidden@m uk-margin-small-right md-color-grey-500"
							uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_CATEGORY_LIST') ?>"
							uk-icon="icon: link"></span>
							<a title="<?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_SHOW'); ?>"
									href="<?php echo $manufacturersList; ?>"><?php echo vmText::_('COM_VIRTUEMART_SHOW'); ?></a>
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
							<?php echo $row->virtuemart_manufacturercategories_id; ?>
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