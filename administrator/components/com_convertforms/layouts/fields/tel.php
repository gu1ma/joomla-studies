<?php

/**
 * @package         Convert Forms
 * @version         5.0.4 Free
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

extract($displayData);

include_once JPATH_PLUGINS . '/system/nrframework/fields/tfphonecontrol.php';

$_field = new \JFormFieldTFPhoneControl;

$inputmask = isset($field->inputmask['custom']) ? $field->inputmask['custom'] : '';

// If the label uses HTML, it will throw an error (String could not be parsed as XML)
$label = strip_tags($field->label);

$element = new \SimpleXMLElement('
	<field
		id="' . $field->input_id . '"
		name="' . $field->input_name . '"
		required="' . ($field->required === '1') . '"
		readonly="' . ($field->readonly === '1') . '"
		placeholder="' . $field->placeholder . '"
		aria_label="' . $label . '"
		inputmask="' . $inputmask . '"
		browserautocomplete="' . ($field->browserautocomplete === '1') . '"
		input_class="cf-input ' . $field->inputcssclass . '"
		type="TFPhoneControl"
	/>
');

$_field->setup($element, $field->value);
?>
<div class="cf-phone-number-wrapper" <?php echo $field->readonly === '1' ? ' readonly' : ''; ?>>
	<?php echo $_field->__get('input'); ?>
</div>