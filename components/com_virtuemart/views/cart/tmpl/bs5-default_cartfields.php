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

$hiddenFields = '';
?>

<?php if (!empty($this->userFieldsCart['fields'])) : ?>
	<?php foreach($this->userFieldsCart['fields'] as $field) : // Output: Userfields ?>
	<fieldset class="vm-fieldset-<?php echo str_replace('_','-',$field['name']) ?>">
		<div class="<?php echo $field['type'] == 'checkbox' ? 'form-check ' : ''; ?>mb-3">
			<label
				class="<?php
				switch ($field['type']) {
					case 'multicheckbox':
						$fieldClass = 'form-check-label ';
						break;
					case 'checkbox':
					case 'radio':
						$fieldClass = 'form-check-label me-2 ';
						break;
					default :
						$fieldClass = 'form-label ';
				}

				echo $fieldClass . $field['name'];
				?>"
				for="<?php echo $field['name'] == 'tos' ? $field['name'] : $field['name'] . '_field';?>"
			>
				<?php echo $field['title'] . ($field['required'] || $field['register'] == 1 ? ' <span class="asterisk">*</span>' : '') ?>
			</label>

			<?php if ($field['type'] == 'multicheckbox') : ?>
			<div>
			<?php endif; ?>
				<?php echo $field['formcode']; ?>
			<?php if ($field['type'] == 'multicheckbox') : ?>
			</div>
			<?php endif; ?>

			<?php if (!empty($field['description'])) : ?>
				<div class="form-text"><?php echo $field['description']; ?></div>
			<?php endif; ?>
		</div>
	</fieldset>
	<?php endforeach; ?>

	<?php echo $hiddenFields; // Output: Hidden Fields ?>
<?php endif; ?>