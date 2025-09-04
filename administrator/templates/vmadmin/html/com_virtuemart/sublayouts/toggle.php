<?php
/**
 * Administrator toggle : replaces the toggle function from administrator/components/com_virtuemart/helpers/vmviewadmin.php
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
 * @version $Id: toggle.php 10649 2022-05-05 14:29:44Z Milbo $
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die ();


/** @var TYPE_NAME $viewData */
$field = $viewData['field'];
$i = $viewData['i'];
$toggle = $viewData['toggle'];
$icon = isset($viewData['icon']) ? $viewData['icon'] : 'check';
$untoggleable = isset($viewData['untoggleable']) ? $viewData['untoggleable'] : false;
$iconRatio = isset($viewData['iconRatio']) ? $viewData['iconRatio'] : 0.75;
$iconColor = isset($viewData['iconColor']) ? $viewData['iconColor'] : false;


$field = intval($field);

if ($toggle == 'published') {
	$task = $field ? 'unpublish' : 'publish';
	$alt = $field ? vmText::_('COM_VIRTUEMART_PUBLISHED') : vmText::_('COM_VIRTUEMART_UNPUBLISHED');
	$action = $field ? vmText::_('COM_VIRTUEMART_UNPUBLISH_ITEM') : vmText::_('COM_VIRTUEMART_PUBLISH_ITEM');

} else {
	$task = $field ? $toggle . '.0' : $toggle . '.1';
	$alt = $field ? vmText::_('COM_VIRTUEMART_PUBLISHED') : vmText::_('COM_VIRTUEMART_DISABLED');
	$action = $field ? vmText::_('COM_VIRTUEMART_DISABLE_ITEM') : vmText::_('COM_VIRTUEMART_ENABLE_ITEM');
}
if (!$iconColor) {
	$iconColor=$field ? 'md-color-green-800':'md-color-grey-400';
}


if ($untoggleable) {
	$untoggleableReason = isset($viewData['untoggleableReason']) ? $viewData['untoggleableReason'] : '';
	?>
	<span uk-tooltip="<?php echo vmText::_($untoggleableReason) ?>" class="<?php echo $iconColor ?>">
			<span uk-icon="icon: <?php echo $icon ?>; ratio: <?php echo $iconRatio ?>"></span>
		</span>
<?php
	return;
}

?>
<a href="javascript:void(0);"
	class="uk-icon-button uk-icon-button-small uk-button-default <?php echo $iconColor ?>"
	onclick="return listItemTask('cb<?php echo $i ?>' ,'<?php echo $task ?>')"
	title="<?php echo $action ?>">
	<span uk-tooltip="<?php echo vmText::_($action) ?>">
			<span uk-icon="icon: <?php echo $icon ?>; ratio: <?php echo $iconRatio ?>"></span>
		</span>
</a>




