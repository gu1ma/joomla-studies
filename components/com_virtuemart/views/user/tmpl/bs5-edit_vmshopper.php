<?php

/**
 *
 * Modify user form view, User info
 *
 * @package	VirtueMart
 * @subpackage User
 * @author Oscar van Eijk
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: edit_vmshopper.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->userDetails->user_is_vendor or $this->allowRegisterVendor or !empty($this->userDetails->virtuemart_user_id)) : ?>
	<?php if (Vmconfig::get('multix','none')!=='none') : ?>
		<div class="mb-3">
			<?php if ($this->userDetails->user_is_vendor or $this->allowRegisterVendor) : ?>
				<label class="form-label" for="virtuemart_vendor_id">
					<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FORM_VENDOR') ?>:
				</label>

				<?php if ($this->userDetails->user_is_vendor) :	?>
					<?php echo $this->lists['vendors']; ?>
				<?php elseif ($this->allowRegisterVendor) : ?>
					<?php echo VmHtml::checkbox ('user_is_vendor', $this->userDetails->user_is_vendor, 1, 0, '', 'user_is_vendor'); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if (!empty($this->userDetails->virtuemart_user_id)) : ?>
		<div class="mb-3">
			<label class="form-label" for="customer_number">
				<?php echo vmText::_('COM_VIRTUEMART_USER_FORM_CUSTOMER_NUMBER') ?>:
			</label>

			<?php if (vmAccess::manager('user.edit')) : ?>
				<input type="text" class="form-control" name="customer_number" id="customer_number" size="40" value="<?php echo  $this->lists['custnumber']; ?>" />
			<?php else : ?>
				<?php echo $this->lists['custnumber']; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($this->lists['shoppergroups'] and !empty($this->userDetails->virtuemart_user_id)) : ?>
		<div class="mb-3">
			<label class="form-label" for="virtuemart_shoppergroup_id">
				<?php echo vmText::_('COM_VIRTUEMART_SHOPPER_FORM_GROUP') ?>:
			</label>

			<?php echo str_replace('vm-chzn-select', 'vm-chzn-select form-control', $this->lists['shoppergroups']); ?>
		</div>
	<?php endif; ?>
<?php endif; ?>