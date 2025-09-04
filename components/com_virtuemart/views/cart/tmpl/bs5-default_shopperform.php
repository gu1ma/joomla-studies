<?php

/**
 *
 * Layout for the shopper form to change the current shopper
 *
 * @package	VirtueMart
 * @subpackage Cart
 * @author Maik K�nnemann
 *
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2020 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 2458 2013-07-16 18:23:28Z kkmediaproduction $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

vmJsApi::chosenDropDowns();

?>
<div class="px-3 py-3 bg-light mb-3">
	<div class="row">
		<div class="col-lg-6">
			<h2 class="h5 fw-normal pb-2 mb-3 border-bottom"><?php echo vmText::_ ('COM_VIRTUEMART_CART_CHANGE_SHOPPER'); ?></h2>

			<form action="<?php echo Route::_ ('index.php?option=com_virtuemart&view=cart'); ?>" method="post">
				<div class="mb-3">
		 			<div class="input-group">
						<input class="form-control" type="text" name="usersearch" size="20" maxlength="50">
						<button class="btn btn-primary" type="submit" name="searchShopper">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
								<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"></path>
							</svg>
						</button>
					</div>
				</div>
				<input type="hidden" name="view" value="cart"/>
				<input type="hidden" name="task" value="searchShopper"/>
				<?php echo JHtml::_( 'form.token' ); ?>
			</form>
			<form action="<?php echo Route::_ ('index.php?option=com_virtuemart&view=cart'); ?>" method="post">
				<div class="mb-3">
					<div class="input-group">
						<?php
							$currentUser = $this->cart->user->virtuemart_user_id;
							echo HTMLHelper::_('select.genericlist', $this->userList, 'userID', 'class="vm-chzn-select form-select"', 'id', 'displayedName', $currentUser,'userIDcart');
						?>
						<button class="btn btn-primary" type="submit" name="changeShopper"><?php echo vmText::_('COM_VIRTUEMART_GO'); ?></button>
					</div>
				</div>

				<?php echo JHtml::_( 'form.token' ); ?>
				<input type="hidden" name="view" value="cart"/>
				<input type="hidden" name="task" value="changeShopper"/>
			</form>

			<?php if ($this->adminID && $currentUser != $this->adminID) { ?>
				<div class="alert alert-info p-2 m-0">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
						<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
						<path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
					</svg>
					<?php echo vmText::_('COM_VIRTUEMART_CART_ACTIVE_ADMIN') .' '. Factory::getUser($this->adminID)->name; ?>
				</div>
			<?php } ?>
		</div>
		<div class="col-lg-6">
			 <h2 class="h5 fw-normal pb-2 mb-3 border-bottom"><?php echo vmText::_ ('COM_VIRTUEMART_CART_CHANGE_SHOPPERGROUP'); ?></h2>

			<form action="<?php echo Route::_ ('index.php?option=com_virtuemart&view=cart'); ?>" method="post">
				<div class="mb-3">
					<?php
						if ($this->shopperGroupList) {
							echo str_replace(array('vm-chzn-select','style="width: 220px;"'), array('vm-chzn-select form-select','size="3"'), $this->shopperGroupList);
						}
					?>
				</div>

				<div class="text-end">
					<button class="btn btn-sm btn-primary" type="submit" name="changeShopperGroup"><?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?></button>
					<?php if (Factory::getSession()->get('tempShopperGroups', FALSE, 'vm')) : ?>
						<button class="btn btn-sm btn-link ms-1" type="reset" onclick="window.location.href='<?php echo Route::_ ('index.php?option=com_virtuemart&view=cart&task=resetShopperGroup'); ?>'"><?php echo vmText::_('COM_VIRTUEMART_RESET'); ?></button>
					<?php endif; ?>
				</div>


				<input type="hidden" name="view" value="cart"/>
				<input type="hidden" name="task" value="changeShopperGroup"/>
				<?php echo HTMLHelper::_( 'form.token' ); ?>
			</form>
		</div>
	</div>
</div>