<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('rules');


/**
 * This is an overload of the core Rules form field
 * It address the issue where several rules cannot be used in the same configuration file
 */
class JFormFieldVmRules extends JFormFieldRules {
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	var $type = 'VMRules';

	/**
	 * Method to get the field input markup for Access Control Lists.
	 * This is an overload of the core Rules form field
	 * It address the issue where several rules cannot be used in the same configuration file
	 */
	protected function getInput() {
		if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
		VmConfig::loadConfig();
		vmLanguage::loadJLang('com_virtuemart_perms');

		return parent::getInput();

	}

}
