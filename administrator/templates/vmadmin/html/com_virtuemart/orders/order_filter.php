<?php
/**
*
* Display form order filter
*
* @package    VirtueMart
* @subpackage Orders
* @author Oscar van Eijk, Max Milbers, Valérie Isaksen
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: order_filter.php 10649 2022-05-05 14:29:44Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
$iconRatio=1;
?>


<div id="filterbox" class="filter-bar">
	<?php
	$extras = array();

	$extras[] = adminSublayouts::renderAdminVmSubLayout('print_links',
		array(
			'order' => $this->orderbt,
			'iconRatio' =>$iconRatio,
			'iconClass' => 'uk-icon-button',
		)

	);


	if ($this->unequal) {
		$labelPaid = 'COM_VIRTUEMART_ORDER_IS_UNPAID';
		$colorPaidTool = 'md-color-grey-400';
		$textPaidAction = 'COM_VIRTUEMART_ORDER_SET_PAID';
		$valPaid = '1';
	} else {
		$labelPaid = 'COM_VIRTUEMART_ORDER_IS_PAID';
		$colorPaidTool = 'md-color-green-600';
		$textPaidAction = 'COM_VIRTUEMART_ORDER_SET_UNPAID';
		$valPaid = '0';

	}
	$linkPaid = 'index.php?option=com_virtuemart&view=orders&task=toggle.paid.' . $valPaid . '&cidName=virtuemart_order_id&virtuemart_order_id[]=' . $this->orderID . '&rtask=edit&' . JSession::getFormToken() . '=1';

	$extras[]=
		'<a href="' . JRoute::_($linkPaid, FALSE) . '"
			class="uk-icon-button   uk-button-default ' . $colorPaidTool . '  uk-margin-xsmall-right">
		<span uk-tooltip="'. vmText::_($textPaidAction) .'">
			<span uk-icon="icon: paid2; ratio: '.$iconRatio .'"></span>
		</span>
		</a>';




	if (empty($this->orderbt->invoice_locked)) {
		$valLocked = '1';
		$labelLocked = 'COM_VM_ORDER_INVOICE_IS_UNLOCKED';
		$colorLockedTool = 'md-color-green-600  ';
		$textLockedAction = 'COM_VM_ORDER_INVOICE_SET_LOCKED';
		$colorLockedAction = 'md-bg-red-600 md-color-white';
		$iconLocked = 'unlock';
	} else {
		$valLocked = '0';
		$labelLocked = 'COM_VM_ORDER_INVOICE_IS_LOCKED';
		$colorLockedTool = 'md-color-red-600';
		$textLockedAction = 'COM_VM_ORDER_INVOICE_SET_UNLOCKED';
		$colorLockedAction = 'md-bg-green-600 md-color-white';
		$iconLocked = 'lock';
	}
	$linkLocked = 'index.php?option=com_virtuemart&view=orders&task=toggle.invoice_locked.' . $valLocked . '&cidName=virtuemart_order_id&virtuemart_order_id[]=' . $this->orderID . '&rtask=edit&' . JSession::getFormToken() . '=1';


	$extras[]=
		'<a href="' . JRoute::_($linkLocked, FALSE) . '"
				class="uk-icon-button uk-icon-button-small uk-button-default ' . $colorLockedTool . ' ">
		<span uk-tooltip="'. vmText::_($textLockedAction) .'">
			<span uk-icon="icon:'.$iconLocked .'; ratio: '.$iconRatio .'"></span>
		</span>
		</a>';


		$extras[]='
				<div class="uk-margin-xlarge-left">
						<a  href="#"  class="updateOrder uk-button uk-button-small uk-button-primary">
							<span class="uk-margin-small-right"
									uk-icon="icon: check"></span>'. vmText::_('COM_VIRTUEMART_ORDER_SAVE_USER_INFO').'
				</a>
				<a href="#" onClick="javascript:Virtuemart.resetOrderHead(event);"
						class="uk-button uk-button-small uk-button-default md-bg-white"
				>
							<span class="uk-margin-small-right"
									uk-icon="icon: close"></span>
					'. vmText::_('COM_VIRTUEMART_ORDER_RESET').'
				</a>
			</div>';



	echo adminSublayouts::renderAdminVmSubLayout('filterbar',
		array(
			'search' => array(
				'label' => 'COM_VIRTUEMART_ORDER_PRINT_NAME',
				'name' => 'search',
				'value' => vRequest::getVar('search')
			),
			'extras' => $extras,
		));


	?>

</div>