<?php

/**
 *
 * Layout for the edit coupon
 *
 * @package	VirtueMart
 * @subpackage Cart
 * @author Oscar van Eijk
 *
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 2458 2010-06-30 18:23:28Z milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->layoutName!=$this->cart->layout) : ?>
	<form method="post" id="userForm" name="enterCouponCode" action="<?php echo JRoute::_('index.php'); ?>">
<?php endif; ?>

<label class="form-label" for="coupon_code"><?php echo $this->coupon_text ?></label>

<div class="vm-coupon-container d-flex">
	<input class="coupon form-control" id="coupon_code" type="text" name="coupon_code" size="20" maxlength="50" value="" />
	<button class="btn btn-secondary w-25 ms-3" type="submit" name="setcoupon"><?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?></button>
</div>

<?php if ($this->layoutName!=$this->cart->layout) : ?>
	    <input type="hidden" name="option" value="com_virtuemart" />
	    <input type="hidden" name="view" value="cart" />
	    <input type="hidden" name="task" value="setcoupon" />
	    <input type="hidden" name="controller" value="cart" />
	</form>
<?php endif; ?>