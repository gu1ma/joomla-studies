<?php

/** @var TYPE_NAME $viewData */
//vmdebug('my data for the PUI mail ',$viewData);
//vmdebug('my PUI mail ');
//echo 'my data for the PUI mail '.vmEcho::varPrintR($viewData); ?>

<table width="100%" border="0" cellpadding="5" cellspacing="0" class="html-email" style="border-collapse: collapse; font-family: Arial, Helvetica, sans-serif; font-size: 12px; margin: 0 auto;">
	<?php if (!empty($viewData['vendor']) and $viewData['vendor']->vendor_letter_header>0) { ?>
		<tr>
			<?php if ($viewData['vendor']->vendor_letter_header_image>0) { ?>
			<td class="vmdoc-header-image" width="50%"><img src="<?php echo JURI::root () . $viewData['vendor']->images[0]->file_url ?>" style="width: <?php echo $viewData['vendor']->vendor_letter_header_imagesize; ?>mm;" /></td>
			<td colspan=1 width="50%" class="vmdoc-header-vendor">
				<?php } else { // no image ?>
			<td colspan=2 width="100%" class="vmdoc-header-vendor">
				<?php } ?>
				<div id="vmdoc-header" class="vmdoc-header" style="font-size: <?php echo $viewData['vendor']->vendor_letter_header_font_size; ?>pt;">
					<?php echo VirtuemartViewInvoice::replaceVendorFields ($viewData['vendor']->vendor_letter_header_html, $viewData['vendor']); ?>
				</div>
			</td>
		</tr>
		<?php if (!empty($viewData['vendor']) and $viewData['vendor']->vendor_letter_header_line == 1) { ?>
			<tr><td colspan=2 width="100%" class="vmdoc-header-separator"></td></tr>
		<?php } // END if header_line ?>

	<?php } // END if header ?>
	<?php if (!empty($viewData['order'])) { ?>
	<tr>
		<td colspan="2">
			<strong><?php echo vmText::sprintf ('COM_VIRTUEMART_MAIL_SHOPPER_NAME', $viewData['order']['details']['BT']->first_name . ' ' . $viewData['order']['details']['BT']->last_name); ?></strong><br/>
		</td>
	</tr>
	<?php }  ?>
	<tr><td colspan="2" style="padding: 5px"></td></tr>

	<tr>
		<td >
			<strong><?php echo vmText::_ ('VMPAYMENT_PAYPAL_USE_REFERENCE'); ?></strong>
		</td>
        <td>
            <strong><?php echo $viewData['payment_reference']; ?></strong>
        </td>
    </tr>


    <tr>
        <td colspan="2">
            <strong><?php echo vmText::_ ('VMPAYMENT_PAYPAL_USE_BANK_DETAILS'); ?></strong><br/>
        </td>
	</tr>
    <tr>
        <td>
            <strong><?php echo vmText::_ ('VMPAYMENT_PAYPAL_USE_BANK_ACCOUNTHOLDER'); ?></strong><br/>
        </td>
        <td>
            <strong><?php echo $viewData['deposit_bank_details']->account_holder_name; ?></strong>
        </td>
    </tr>

    <tr>
        <td>
            <strong><?php echo vmText::_ ('VMPAYMENT_PAYPAL_USE_BANK_NAME'); ?></strong><br/>
        </td>
        <td>
            <strong><?php echo $viewData['deposit_bank_details']->bank_name; ?></strong>
        </td>
    </tr>
    <tr>
        <td>
            <strong><?php echo vmText::_ ('VMPAYMENT_PAYPAL_USE_BANK_IBAN'); ?></strong><br/>
        </td>
        <td>
            <strong><?php echo $viewData['deposit_bank_details']->iban; ?></strong>
        </td>
    </tr>

    <tr>
        <td>
            <strong><?php echo vmText::_ ('VMPAYMENT_PAYPAL_USE_BANK_BIC'); ?></strong><br/>
        </td>
        <td>
            <strong><?php echo $viewData['deposit_bank_details']->bic; ?></strong>
        </td>
    </tr>

</table>