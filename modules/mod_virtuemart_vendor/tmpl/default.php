<?php // no direct access
defined('_JEXEC') or die('Restricted access');
$col= 1 ;
?>
<div class="vmgroup<?php echo $params->get( 'moduleclass_sfx' ) ?>">

<?php if ($headerText) : ?>
	<div class="vmheader"><?php echo $headerText ?></div>
<?php endif;?>

	<div class="vmvendor<?php echo $params->get('moduleclass_sfx'); ?>">
		<?php if ($display_about_link) {
			$link = JROUTE::_('index.php?option=com_virtuemart&view=vendor&virtuemart_vendor_id=' . $vendor->virtuemart_vendor_id);

			?>
			<div class="vmvendor_about_link">
				<a   href="<?php echo $link;  ?>">
					<?php echo JText::_('MOD_VIRTUEMART_VENDOR_DETAIL').' ';
					if ($show=='all' || $show=='image') {
						if ($vendor->images ) { ?>
						<div><?php echo $vendor->images[0]->displayMediaThumb('',false) ;?></div>
						<?php
						}
					}
					if ($show == 'text' or $show == 'all' ) { 
						echo $vendor->vendor_name; 
					} ?>
				</a>
			</div>

		<?php }
		if ($display_tos_link) {
			$link = JROUTE::_('index.php?option=com_virtuemart&view=vendor&layout=tos&virtuemart_vendor_id=' . $vendor->virtuemart_vendor_id);
			?>
			<div class="vmvendor_tos_link">
				<a   href="<?php echo $link; ?>"> <?php echo JText::_('MOD_VIRTUEMART_VENDOR_TOS'); ?></a>
			</div>

		<?php }
		if ($display_contact_link) {
		$link = JROUTE::_('index.php?option=com_virtuemart&view=vendor&layout=contact&virtuemart_vendor_id=' . $vendor->virtuemart_vendor_id);
		?>
		<div class="vmvendor_contact_link">
			<a   href="<?php echo $link; ?>"><?php echo JText::_('MOD_VIRTUEMART_VENDOR_CONTACT'); ?>	</a>
		</div>
		<?php } ?>

	<br style='clear:both;' />
</div>
	<?php if ($footerText) : ?>
	<div class="vmfooter"><?php echo $footerText ?></div>
<?php endif;?>
</div>