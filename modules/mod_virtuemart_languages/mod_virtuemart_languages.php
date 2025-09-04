<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_virtuemart_languages
 * @author Max Milbers
 * @copyright	Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved. 2015 iStraxx GmbH
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
VmConfig::loadConfig();
vRequest::setRouterVars();

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

/** @var TYPE_NAME $params */

$headerText	= trim($params->get('header_text'));
$footerText	= trim($params->get('footer_text'));

$list = modVmLanguagesHelper::getList($params);

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

require JModuleHelper::getLayoutPath('mod_virtuemart_languages', $params->get('layout', 'default'));
