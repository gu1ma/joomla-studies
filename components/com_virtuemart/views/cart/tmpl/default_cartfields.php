<?php

/**
 *
 * renders the userfields with "is cart attribute set"
 *
 *
 * @package     VirtueMart
 * @subpackage
 * @author      Max Milbers
 * @link        https://virtuemart.net
 * @copyright   Copyright (c) 2014 - 2018 VirtueMart Team. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @version     $Id: addtocartbtn.php 8024 2014-06-12 15:08:59Z Milbo $
 */

defined ('_JEXEC') or die();

// Status Of Delimiter
$closeDelimiter = false;
$openTable = true;
$hiddenFields = '';

if(!empty($this->userFieldsCart['fields'])) {

	// Output: Userfields
	foreach($this->userFieldsCart['fields'] as $field) {
	?>
	<fieldset class="vm-fieldset-<?php echo str_replace('_','-',$field['name']) ?>">
		<div  class="cart <?php echo str_replace('_','-',$field['name']) ?>" title="<?php echo strip_tags($field['description']) ?>">
		<span class="cart <?php echo str_replace('_','-',$field['name']) ?>" ><?php echo $field['title'] ?></span>

		<?php
		if ($field['hidden'] == true) {
			// We collect all hidden fields
			// and output them at the end
			$hiddenFields .= $field['formcode'] . "\n";
		} else { ?>
				<?php echo $field['formcode'] ?>
			</div>
	<?php } ?>

	</fieldset>

	<?php
	}
	// Output: Hidden Fields
	echo $hiddenFields;
}
?>