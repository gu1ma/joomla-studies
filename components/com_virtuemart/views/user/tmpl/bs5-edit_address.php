<?php

/**
 *
 * Enter address data for the cart, when anonymous users checkout
 *
 * @package    VirtueMart
 * @subpackage User
 * @author Oscar van Eijk, Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: edit_address.php 10970 2024-01-26 17:22:13Z  $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

vmJsApi::css('vmpanels');

$this->cart = VirtueMartCart::getCart();

$url = 0;
if ($this->cart->_fromCart or $this->cart->getInCheckOut()) {
	$rview = 'cart';
} else {
	$rview = 'user';
}
?>

<?php function renderControlButtons($view,$rview) { ?>
	<div class="control-buttons text-center">
		<?php if (VmConfig::get ('oncheckout_show_register', 1) && $view->userDetails->JUser->id == 0 && !VmConfig::get ('oncheckout_only_registered', 0) && $view->address_type == 'BT' and $rview == 'cart') : ?>
			<p class="reg_text"><?php echo vmText::sprintf ('COM_VIRTUEMART_ONCHECKOUT_DEFAULT_TEXT_REGISTER', vmText::_ ('COM_VIRTUEMART_REGISTER_AND_CHECKOUT'), vmText::_ ('COM_VIRTUEMART_CHECKOUT_AS_GUEST')); ?></p>
		<?php endif; ?>

		<?php if (VmConfig::get ('oncheckout_show_register', 1) && $view->userDetails->JUser->id == 0 && $view->address_type == 'BT' and $rview == 'cart') : ?>
			<button class="btn btn-primary" type="submit" name="register" onclick="javascript:return myValidator(userForm,true);"><?php echo vmText::_ ('COM_VIRTUEMART_REGISTER_AND_CHECKOUT'); ?></button>
			<?php if (!VmConfig::get ('oncheckout_only_registered', 0)) : ?>
				<button class="btn btn-primary" type="submit" name="save" onclick="javascript:return myValidator(userForm, false);"><?php echo vmText::_ ('COM_VIRTUEMART_CHECKOUT_AS_GUEST'); ?></button>
			<?php endif; ?>
			<button class="btn btn-secondary" type="reset" onclick="window.location.href='<?php echo Route::_ ('index.php?option=com_virtuemart&view=' . $rview.'&task=cancel'); ?>'"><?php echo vmText::_ ('COM_VIRTUEMART_CANCEL'); ?></button>
		<?php else : ?>
			<button class="btn btn-primary" type="submit" onclick="javascript:return myValidator(userForm,true);"><?php echo vmText::_ ('COM_VIRTUEMART_SAVE'); ?></button>
			<button class="btn btn-secondary" type="reset" onclick="window.location.href='<?php echo Route::_ ('index.php?option=com_virtuemart&view=' . $rview.'&task=cancel'); ?>'"><?php echo vmText::_ ('COM_VIRTUEMART_CANCEL'); ?></button>
		<?php endif; ?>
	</div>
<?php } ?>

<h1 class="vm-page-title mb-4 text-center"><?php echo $this->page_title ?></h1>

<?php
$task = '';

if ($this->cart->getInCheckOut()){
	$task = '&task=checkout';
}

$url = 'index.php?option=com_virtuemart&view='.$rview.$task;
?>

<div class="vm-cart-header">
	<div class="payments-signin-button"></div>
</div>

<?php echo shopFunctionsF::getLoginForm (TRUE, FALSE, $url); ?>

<form method="post" id="userForm" name="userForm" class="form-validate" action="<?php echo Route::_('index.php?option=com_virtuemart&view=user',$this->useXHTML,$this->useSSL) ?>" >
	<fieldset>
		<h2 class="vm-section-title pb-2 mb-3 border-bottom"><?php echo $this->address_type == 'BT' ? vmText::_ ('COM_VIRTUEMART_USER_FORM_EDIT_BILLTO_LBL') : vmText::_ ('COM_VIRTUEMART_USER_FORM_ADD_SHIPTO_LBL'); ?></h2>

		<?php
			if ( !empty( $this->userFields['functions'])) {
				echo '<script language="javascript">' . "\n";
				echo join ("\n", $this->userFields['functions']);
				echo '</script>' . "\n";
			}

			echo $this->loadTemplate ('userfields');
		?>

		<?php if (VmConfig::get ('reg_captcha') && Factory::getUser()->guest == 1) : // captcha addition  ?>
			<fieldset id="recaptcha_wrapper" class="d-flex flex-column align-items-center mb-3">
				<?php if(!VmConfig::get ('oncheckout_only_registered')) { ?>
					<div class="userfields_info mb-2"><?php echo vmText::_ ('COM_VIRTUEMART_USER_FORM_CAPTCHA'); ?></div>
				<?php } ?>
				<?php echo $this->captcha; ?>
			</fieldset>
		<?php endif; // end of captcha addition ?>


		<?php
		renderControlButtons($this,$rview);

		if ($this->userDetails->virtuemart_user_id) {
			echo $this->loadTemplate ('addshipto');
		}
		?>

		<input type="hidden" name="option" value="com_virtuemart"/>
		<input type="hidden" name="view" value="user"/>
		<input type="hidden" name="controller" value="user"/>
		<input type="hidden" name="task" value="saveUser"/>
		<input type="hidden" name="layout" value="<?php echo $this->getBaseLayout (); ?>"/>
		<input type="hidden" name="address_type" value="<?php echo $this->address_type; ?>"/>

		<?php if (!empty($this->virtuemart_userinfo_id)) {
			echo '<input type="hidden" name="shipto_virtuemart_userinfo_id" value="' . (int)$this->virtuemart_userinfo_id . '" />';
		}

		echo HTMLHelper::_ ('form.token');
		?>
	</fieldset>
</form>