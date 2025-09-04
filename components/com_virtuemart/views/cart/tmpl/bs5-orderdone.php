<?php

/**
*
* Template for the shopping cart
*
* @package	VirtueMart
* @subpackage Cart
* @author Max Milbers
*
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

defined('_JEXEC') or die('');

use Joomla\CMS\Factory;
?>

<div class="vm-wrap vm-order-done">
	<?php if (vRequest::getBool('display_title',true)) : ?>
		<h1 class="h3"><?php echo vmText::_('COM_VIRTUEMART_CART_ORDERDONE_THANK_YOU'); ?></h1>
	<?php endif; ?>

	<?php echo str_replace('vm-button-correct', 'btn btn-primary', $this->html); ?>

	<?php
		if (vRequest::getBool('display_loginform',true)) {
			$cuser = Factory::getUser();
			if (!$cuser->guest) echo shopFunctionsF::getLoginForm();
		}
	?>
</div>