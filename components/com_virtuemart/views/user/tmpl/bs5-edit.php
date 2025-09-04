<?php

/**
*
* Modify user form view
*
* @package	VirtueMart
* @subpackage User
* @author Oscar van Eijk, Max Milbers, Stan
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2020 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: edit.php 10795 2023-02-27 14:52:57Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

vmJsApi::css('vmpanels'); // VM_THEMEURL

$url = vmURI::getCurrentUrlBy('request');
$cancelUrl = Route::_($url.'&task=cancel');
?>

<h1 class="vm-page-title mb-4 text-center"><?php echo $this->page_title ?></h1>

<?php echo shopFunctionsF::getLoginForm(false,false); ?>

<?php if ($this->userDetails->virtuemart_user_id==0) : ?>
	<h2 class="vm-section-title pb-2 mb-4 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_YOUR_ACCOUNT_REG'); ?></h2>
<?php endif; ?>

<form class="form-validate" method="post" id="adminForm" name="userForm" action="<?php echo Route::_($url) ?>">
	<?php // Loading Templates in Tabs
		if ($this->userDetails->virtuemart_user_id!=0) {
			$tabarray = array();

			$tabarray['shopper'] = 'COM_VIRTUEMART_SHOPPER_FORM_LBL';
		?>

		<?php if (!empty($this->manage_link) || !empty($this->add_product_link)) : ?>
			<div class="col-12 mb-4">
				<div class="vm-user-manage-buttons btn-group w-100">
				 <?php
					if (!empty($this->manage_link)) {
						echo str_replace('btn-primary', 'vm-user-manage btn-primary btn-sm mb-2 mb-md-0', strip_tags($this->manage_link,'<a>'));
					}

					if (!empty($this->add_product_link)) {
						echo str_replace('btn-primary', 'vm-user-add-product btn-primary btn-sm ms-md-1', strip_tags($this->add_product_link,'<a>'));
					}
				 ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
			if ($this->userDetails->user_is_vendor) {
				$tabarray['vendor'] = 'COM_VIRTUEMART_VENDOR';
			}


			if (!empty($this->shipto)) {
				$tabarray['shipto'] = 'COM_VIRTUEMART_USER_FORM_ADD_SHIPTO_LBL';
			}

			if (($_ordcnt = count($this->orderlist)) > 0) {
				$tabarray['orderlist'] = 'COM_VIRTUEMART_YOUR_ORDERS';
			}

			shopFunctionsF::buildTabs ( $this, $tabarray);
		} else {
			echo $this->loadTemplate ( 'shopper' );
		}
	?>

	<?php //stAn - with hidden config of reg_captcha_logged=1 we can trigger captcha for logged in if needed as well, or add user input filter plugin for antispam ?>
	<div class="d-flex justify-content-center mb-3"><?php echo $this->captcha; ?></div>

	<div class="vm-btn-contaner pt-3 text-center border-top">
		<button class="btn btn-primary me-2" type="submit" onclick="javascript:return myValidator(userForm, true);" ><?php echo $this->button_lbl ?></button>
		<button class="btn btn-secondary" type="reset" onclick="window.location.href='<?php echo $cancelUrl ?>'" ><?php echo vmText::_('COM_VIRTUEMART_CANCEL'); ?></button>
	</div>

	<input type="hidden" name="option" value="com_virtuemart" />
	<input type="hidden" name="controller" value="user" />
	<?php echo HTMLHelper::_( 'form.token' ); ?>
</form>