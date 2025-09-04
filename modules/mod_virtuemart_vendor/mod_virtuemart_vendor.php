<?php
defined('_JEXEC') or  die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/*
* vendor Module
*
* @package VirtueMart
* @subpackage modules
*
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* VirtueMart is Free Software.
* VirtueMart comes with absolute no warranty.
*
* www.virtuemart.net
*/

if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart'.DS.'helpers'.DS.'config.php');
VmConfig::loadConfig();
     
$vendorId = vRequest::getInt('vendorid', 1);
$model = new VirtueMartModelVendor();
$display_tos_link =		$params->get( 'display_tos_link', 1 ); // Display a Header Text
$display_about_link =	$params->get( 'display_about_link', 1 ); // Display a Header Text
$display_contact_link = $params->get( 'display_contact_link', 1 ); // Display a Header Text
$display_style = 		$params->get( 'display_style', "div" ); // Display Style
$headerText = 			$params->get( 'headerText', '' ); // Display a Header Text
$footerText = 			$params->get( 'footerText', ''); // Display a footerText
$show = 				$params->get( 'show', 'all'); // Display a footerText
$vendor = $model->getVendor(1);
if($show=='all' || $show=='image')$model->addImages($vendor);
if(empty($vendor)) return false;
/* load the template */
require(JModuleHelper::getLayoutPath('mod_virtuemart_vendor'));
?>