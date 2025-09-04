<?php

/**
*
* Order history view
*
* @package	VirtueMart
* @subpackage Orders
* @author Oscar van Eijk
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: details_history.php 10649 2022-05-05 14:29:44Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>
<div class="table-responsive">
	<table class="table">
		<tr>
			<th><?php echo vmText::_('COM_VIRTUEMART_DATE') ?></th>
			<th><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_STATUS') ?></th>
			<th><?php echo vmText::_('COM_VIRTUEMART_ORDER_COMMENT') ?></th>
		</tr>
		<?php foreach($this->orderdetails['history'] as $_hist) : ?>
			<?php
				if (!$_hist->customer_notified) {
					continue;
				}
			?>
			<tr valign="top">
				<td>
					<?php echo vmJsApi::date($_hist->created_on,'LC2',true); ?>
				</td>
				<td>
					<?php echo $this->orderstatuses[$_hist->order_status_code]; ?>
				</td>
				<td>
					<?php echo $_hist->comments; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>