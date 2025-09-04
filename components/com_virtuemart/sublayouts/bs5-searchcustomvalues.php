<?php

/**
 *
 * renders the search customvalues
 *
 * @package     VirtueMart
 * @subpackage
 * @author      Max Milbers, Eugen Stranz
 * @link        https://virtuemart.net
 * @copyright   Copyright (c) 2018 VirtueMart Team. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @version     $Id: searchcustomvalues.php 8024 2014-06-12 15:08:59Z Milbo $
 */

// Joomla Security Check - no direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;

/** @var TYPE_NAME $viewData
 output the passed array / object content */
$searchCustomValues = $viewData['searchcustomvalues'];

?>
<?php foreach ($searchCustomValues as $searchCustomValue) : ?>
	<?php if ($searchCustomValue->field_type == "S") : ?>
		<div class="<?php echo count($searchCustomValues) <=3 ? ' col-lg-3 ' : ' col-xl-2 ';?>col-md-4 col-6">
			<label class="form-label mb-1 d-block" for="customfields<?php echo $searchCustomValue->virtuemart_custom_id; ?>"><?php echo vmText::_($searchCustomValue->custom_title); ?></label>
			<?php echo HTMLHelper::_(
				'select.genericlist', $searchCustomValue->value_options,
				'customfields[' . $searchCustomValue->virtuemart_custom_id . ']',
				'class="changeSendForm select-control vm-chzn-select form-select"', 'virtuemart_custom_id', 'custom_title',
				$searchCustomValue->v
			); ?>
		</div>
		<?php elseif ($searchCustomValue->field_type == "P") : ?>
		<?php
			$name  = 'customfields[' . $searchCustomValue->virtuemart_custom_id . ']';
			$value = vRequest::getString('customfields[' . $searchCustomValue->virtuemart_custom_id . ']');
		?>

		<?php echo vmText::_($searchCustomValue->custom_title); ?>
		<input name="<?php echo $name ?>" class="inputbox vm-chzn-select" type="text" size="20" value="<?php echo $value ?>"/>
	<?php endif; ?>
<?php endforeach; ?>