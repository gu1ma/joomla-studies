<?php

/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author KOHL Patrick
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
* @version $Id: default.php 2810 2011-03-02 19:08:24Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined ( '_JEXEC' ) or die ( 'Restricted access' );
/* thank you for the Recommend  mail  */
if(VmConfig::get('usefancy',1)){
	$onclick = 'parent.jQuery.fancybox.close();';
} else {
	$onclick = 'parent.jQuery.facebox.close();';
}
?>
<div class="productdetails-view">
	<?php echo vmText::_('COM_VIRTUEMART_RECOMMEND_THANK_YOU'); ?>
	<button class="btn btn-primary" onclick="<?php echo $onclick ?>" type="button">
		<?php echo vmText::_('COM_VIRTUEMART_CLOSE'); ?>
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
			<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
		</svg>
	</button>
</div>
