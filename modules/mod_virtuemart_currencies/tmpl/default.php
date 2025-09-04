<?php // no direct access
defined('_JEXEC') or die('Restricted access');
vmJsApi::jQuery();
vmJsApi::chosenDropDowns();
?>

<!-- Currency Selector Module -->
<?php echo $text_before ?>

<form action="<?php echo vmURI::getCurrentUrlBy('get',true) ?>" method="post">
	<!--input class="button" type="submit" name="submit" value="<?php echo vmText::_('MOD_VIRTUEMART_CURRENCIES_CHANGE_CURRENCIES') ?>" /-->
	<?php echo JHTML::_('select.genericlist', $currencies, 'virtuemart_currency_id', 'class="changeSendForm inputbox vm-chzn-select"', 'virtuemart_currency_id', 'currency_txt', $virtuemart_currency_id) ; ?>
</form>

<?php
$j = 'jQuery(document).ready(function() {

jQuery(".changeSendForm")
	.off("change",Virtuemart.sendCurrForm)
	.on("change",Virtuemart.sendCurrForm);
})';

vmJsApi::addJScript('sendFormChange',$j);
echo vmJsApi::writeJS();