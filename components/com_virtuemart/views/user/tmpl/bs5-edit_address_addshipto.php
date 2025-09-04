<?php

/**
 *
 * Enter address data for the cart, when anonymous users checkout
 *
 * @package	VirtueMart
 * @subpackage User
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: edit_address_addshipto.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
?>

<fieldset class="vm-add-edit-address mb-4">
	<legend class="pb-2 mb-3 border-bottom">
		<?php echo vmText::_('COM_VIRTUEMART_USER_FORM_SHIPTO_LBL');  ?>
	</legend>

	<?php echo str_replace('ul','ul class="list-unstyled p-3 bg-light"',$this->lists['shipTo']); ?>
</fieldset>

