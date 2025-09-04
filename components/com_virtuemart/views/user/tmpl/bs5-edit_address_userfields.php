<?php

/**
 *
 * Modify user form view, User info
 *
 * @package	VirtueMart
 * @subpackage User
 * @author Oscar van Eijk, Eugen Stranz, Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2019 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: edit_address_userfields.php 10994 2024-04-17 09:39:44Z  $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Status Of Delimiter
$closeDelimiter = false;
$hiddenFields = '';

$i=0;
//When only one Delimiter exists, set it to begin of the array
//not an elegant solution, but works.
$tmp = false;
foreach ($this->userFields['fields'] as $k=>$field) {
	if ($field['type'] == 'delimiter') {
		$tmp = $field;
		$pos = $k;
		$i++;
	}

	if ($i>1) {
		$tmp = false;
		break;
	}
}

if ($tmp){
	unset($this->userFields['fields'][$pos]);
	array_unshift($this->userFields['fields'],$tmp);
}
?>
<div class="vm-form-row row gy-4 mb-4">
	<?php if ($i == 0) : ?>
		<div class="col col-lg-6">
			<div class="p-4 bg-light">
	<?php endif; ?>

	<?php foreach($this->userFields['fields'] as $field) :  // Output: Userfields  ?>
		<?php if ($field['type'] == 'delimiter') : ?>
			<?php
			// For Every New Delimiter
			// We need to close the previous
			// table and delimiter
			?>
			<?php if ($closeDelimiter) : ?>
					</fieldset>
				</div>
				<?php $closeDelimiter = false; ?>
			<?php endif; ?>

			<?php if ($field['name']=='delimiter_userinfo') : ?>
				<div class="<?php echo $i == 2 ? 'col-lg-6' : 'col-lg-8 offset-lg-2'; ?>">
					<fieldset class="py-3 px-4 bg-light">
						<legend class="h5 userfields_info pb-2 mb-3 border-bottom"><?php echo $field['title'] ?></legend>

						<?php if ($this->getBaseLayout() == 'edit') : ?>
							<?php echo $this->loadTemplate('vmshopper'); ?>
						<?php endif; ?>
			<?php else : ?>
				<div class="<?php echo $i == 2 ? 'col-lg-6' : 'col-lg-8 offset-lg-2'; ?>">
					<fieldset class="py-3 px-4 bg-light">
						<legend class="h5 userfields_info pb-2 mb-3 border-bottom"><?php echo $field['title'] ?></legend>
			<?php endif; ?>

			<?php
				$closeDelimiter = true;
				$openTable = true;
			?>
		<?php elseif ($field['hidden'] == true) : ?>
			<?php
			// We collect all hidden fields
			// and output them at the end
			$hiddenFields .= $field['formcode'] . "\n";
			?>
		<?php else : ?>
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
						case 'tos':
							$fieldClass = 'vm-label ';
							break;
						default :
							$fieldClass = 'form-label ';
					}

					echo $fieldClass . $field['name'];
					?>"
					for="<?php echo $field['name'] ?>_field"
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
		<?php endif; ?>
	<?php endforeach; ?>

	<?php if ($closeDelimiter) : ?>
			</fieldset>
		</div>
		<?php $closeDelimiter = false; ?>
	<?php endif; ?>

	<?php echo $hiddenFields; // Output: Hidden Fields ?>

	<?php if ($i == 0) : ?>
			</div>
		</div>
	<?php endif; ?>
</div>