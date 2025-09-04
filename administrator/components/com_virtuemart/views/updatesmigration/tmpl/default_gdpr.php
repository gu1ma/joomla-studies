<?php

$current = VmConfig::get('forSale_path','');
if(!empty($current)) {
	echo VirtueMartModelInvoice::getInvoicePath();
}

?>
<form id="gdpr">
<fieldset><legend><?php echo vmText::_( 'COM_VM_REMOVE_SPPRS' )?></legend>
    <?php echo $this->renderTaskButton( 'removeSpamUsers', 'COM_VM_GDPR_REMOVE_SPAM_SPPRS', '', 'button' ); ?><div class="clear"></div>
	<?php echo $this->renderTaskButton( 'removeJoomlaUsersNoShoppers', 'COM_VM_GDPR_REMOVE_JUSERS_NO_SPPRS', '', 'button' ); ?><div class="clear"></div>
    <?php echo $this->renderTaskButton( 'removeShpprsInactiveY', 'COM_VM_GDPR_REMOVE_SPPRS_Y', '', 'button' ); ?><input type="text" value="10" name="years_shpprs">
</fieldset>
    <?php if(!empty($current)){ ?>
<fieldset><legend><?php echo vmText::_( 'COM_VM_GDPR_REMOVE_ORDINV' )?></legend>
	<?php echo $this->renderTaskButton( 'removeOrdersInvoicesY', 'COM_VM_GDPR_REMOVE_ORDINV_Y', '', 'button' ); ?><input type="text" value="10" name="years_invoice"><br>
	<?php //echo 'Delete files also by cDate'; echo VmHtml::checkbox('DeleteByFileCDate',0)?>
    </fieldset>
    <?php } ?>


<br>
<fieldset><legend><?php echo vmText::_( 'COM_VM_GDPR_REMOVE_SPPR' )?></legend>
<?php echo $this->renderTaskButton( 'removeShpprOrders', 'COM_VM_GDPR_REMOVE_SPPR_ID', '', 'button' ); ?><input type="text" value="" name="virtuemart_user_id">
</fieldset>
<fieldset><legend><?php echo vmText::_( 'COM_VM_GDPR_REMOVE_CARTS' )?></legend>
    <?php echo $this->renderTaskButton( 'removeOldCarts', 'COM_VM_GDPR_REMOVE_CARTS', '', 'button' ); ?>
</fieldset>
<?php echo $this->addStandardHiddenToForm('updatesmigration')?>
</form>
<div class=""></div>
