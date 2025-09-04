<?php
/**
 * Administrator printlinks
 *
 * @package VirtueMart
 * @subpackage Sublayouts
 * @author Max Milbers
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * @version $Id: print_links.php 10649 2022-05-05 14:29:44Z Milbo $
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die ();


/** @var TYPE_NAME $viewData */
$order = $viewData['order'];
$iconRatio = isset($viewData['iconRatio']) ? $viewData['iconRatio'] : 0.75;
$iconClass = isset($viewData['iconClass']) ? $viewData['iconClass'] : 'uk-icon-button';


$linkType = isset($viewData['linkType']) ? $viewData['linkType'] : array('print', 'deliverynote', 'invoices');
$baseUrl = 'index.php?option=com_virtuemart&view=orders&task=callInvoiceView&tmpl=component&virtuemart_order_id=' . $order->virtuemart_order_id;
$print_url = $baseUrl . '&layout=invoice';
$pdfDummi = '&d=' . rand(0, 100);

if (in_array('print', $linkType)) {
	?>
	<a href="javascript:void window.open('<?php echo $print_url ?>', 'win2', 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no');"
			class="<?php echo $iconClass ?> md-color-blue-800 uk-margin-xsmall-right "
	>
		<span uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PRINT') . ' ' . $order->order_number ?>">
			<span uk-icon="icon: printer; ratio: <?php echo $iconRatio ?>"></span>
		</span>
	</a>

	<?php
}

if (in_array('deliverynote', $linkType)) {
	if (!$order->invoiceNumbers) {
		$deliverynote_url = $baseUrl . '&layout=deliverynote&format=pdf&create_invoice=' . $order->order_create_invoice_pass . $pdfDummi;
		?>
		<a href="<?php echo $deliverynote_url ?>"
				class="<?php echo $iconClass ?>  md-color-grey-400  uk-margin-xsmall-right">
		<span uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_DELIVERYNOTE_CREATE') ?>">
			<span uk-icon="icon: shipment2; ratio: <?php echo $iconRatio ?>"></span>
		</span>
		</a>
		<?php
	} else {
		/*
		 * TODO: InvoiceNumberReserved is used by some payments like Klarna
		 */
		$invoiceNumber = $order->invoiceNumbers [0];
		if (!shopFunctionsF::InvoiceNumberReserved($invoiceNumber)) {
			$deliverynote_url = $baseUrl . '&layout=deliverynote&format=pdf&virtuemart_order_id=' . $order->virtuemart_order_id . $pdfDummi;
			?>
			<a href="<?php echo $deliverynote_url ?>"
					class="<?php echo $iconClass ?> md-color-orange-800  uk-margin-xsmall-right ">
		<span uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_DELIVERYNOTE') . ' ' . $invoiceNumber ?>">
			<span uk-icon="icon: shipment2; ratio: <?php echo $iconRatio ?>"></span>
		</span>
			</a>

			<?php
		}
	}
}

if (in_array('invoices', $linkType)) {
	$invoice_links_array = array();
	$deliverynote_link = '';

	if (!$order->invoiceNumbers) {
		$invoice_url = $baseUrl . '&layout=invoice&format=pdf&create_invoice=' . $order->order_create_invoice_pass . $pdfDummi;
		?>
		<a href="<?php echo $invoice_url ?>"
				class="<?php echo $iconClass ?> md-color-grey-400  uk-margin-xsmall-right">
		<span uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_INVOICE_CREATE') ?>">
			<span uk-icon="icon: pdf; ratio: <?php echo $iconRatio ?>"></span>
		</span>
		</a>

		<?php


	} else {
		foreach ($order->invoiceNumbers as $invoiceNumber) {
			if (!shopFunctions::InvoiceNumberReserved($invoiceNumber)) {
				$invoice_url = $baseUrl . '&layout=invoice&format=pdf' . $pdfDummi . '&invoiceNumber=' . $invoiceNumber;
				?>
				<a href="<?php echo $invoice_url ?>"
						class="<?php echo $iconClass ?> md-color-green-800  uk-margin-xsmall-right ">
		<span uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_INVOICE') . ' ' . $invoiceNumber ?>">
			<span uk-icon="icon: pdf; ratio: <?php echo $iconRatio ?>"></span>
		</span>
				</a>
				<?php
			}
		}
	}
}
