<?php

/**
*
* Order detail view
*
* @package	VirtueMart
* @subpackage Orders
* @author Oscar van Eijk, Valerie Isaksen
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: details.php 10649 2022-05-05 14:29:44Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

vmJsApi::css('vmpanels');

if (empty($this->orderdetails)) {
	echo '<div class="vm-wrap">';
	echo shopFunctionsF::getLoginForm(false,$this->trackingByOrderPass);
	echo '</div>';
	return true;
}

?>

<?php if ($this->print) : ?>
<div>
	<div class="row mb-4">
	 	<div class="vm-orders-vendor-image col-6">
			<img src="<?php  echo Uri::root() . $this->vendor->images[0]->file_url ?>">
		</div>
		<div class="col-6">
			<?php echo $this->vendor->vendor_store_name; ?><br>
			<?php echo $this->vendor->vendor_name; ?><br>
			<?php echo $this->vendor->vendor_phone; ?>
		</div>
	</div>

	<h1 class="vm-section-title mb-3"><?php echo vmText::_('COM_VIRTUEMART_ACC_ORDER_INFO'); ?></h1>

	<?php echo $this->loadTemplate('order'); ?>
	<?php echo $this->loadTemplate('items'); ?>

	<?php echo VirtuemartViewInvoice::replaceVendorFields($this->vendor->vendor_letter_footer_html, $this->vendor); ?>
</div>
<script>
javascript:print();
</script>
<?php else : ?>
	<div class="vm-wrap">
		<div class="vm-orders-information">
			<div class='text-end mb-3'>
				<a class="btn btn-sm btn-primary" href="<?php echo $this->order_list_link ?>" rel="nofollow">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-task" viewBox="0 0 16 16">
						<path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5zM3 3H2v1h1z"/>
						<path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M5.5 7a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1zm0 4a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1z"/>
						<path fill-rule="evenodd" d="M1.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5zM2 7h1v1H2zm0 3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm1 .5H2v1h1z"/>
					</svg>
					<?php echo vmText::_('COM_VIRTUEMART_ORDERS_VIEW_DEFAULT_TITLE'); ?>
				</a>
			</div>

			<h1 class="vm-page-title d-flex pb-2 mb-4 border-bottom">
				<?php echo vmText::_('COM_VIRTUEMART_ACC_ORDER_INFO'); ?>
				<span class="ms-auto">
					<?php $details_link = "javascript:void window.open('$this->details_url', 'win2', 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no');"; ?>
					<a href="<?php echo $details_link; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
							<path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
							<path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
						</svg>
					</a>

					<?php
						$this->orderdetails['details']['BT']->invoiceNumber = VmModel::getModel('orders')->getInvoiceNumber($this->orderdetails['details']['BT']->virtuemart_order_id);
						echo strip_tags(shopFunctionsF::getInvoiceDownloadButton($this->orderdetails['details']['BT']),'<a>');
					?>
				</span>
			</h1>

			<div class="spaceStyle vm-orders-order">
				<?php echo $this->loadTemplate('order'); ?>
			</div>

			<div class="spaceStyle vm-orders-items">
				<?php
					$tabarray = array();

					$tabarray['items'] = 'COM_VIRTUEMART_ORDER_ITEM';
					$tabarray['history'] = 'COM_VIRTUEMART_ORDER_HISTORY';

					shopFunctionsF::buildTabs ( $this, $tabarray);
				?>
			</div>
		</div>
	</div>
<?php endif; ?>