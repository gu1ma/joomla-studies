<?php
defined('_JEXEC') or die();
/**
*
* Base controller
*
* @package	VirtueMart
* @subpackage Core
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2011 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: plugin.php 2641 2010-11-09 19:25:13Z milbo $
*/
defined('_JEXEC') or die('Restricted access');

/**
 * VirtueMart default administrator controller
 *
 * @package		VirtueMart
 */
class VirtuemartControllerPlugin extends VmController
{
	/**
	 * Method to render the plugin datas
	 * this is an entry point to plugin to easy renders json or html
	 *
	 *
	 * @access	public
	 */
	function display($cachable = false, $urlparams = false)  {

		if(!vmAccess::manager()){
			return false;
		}

		if (!$type = vRequest::getCmd ('vmtype', NULL)) {
			$type = vRequest::getCmd ('type', 'vmcustom');
		}
		$typeWhiteList = array('vmshopper','vmcustom','vmcalculation','vmpayment','vmshipment', 'vmuserfield', 'vmextended');
		if(!in_array($type,$typeWhiteList)) return false;
		$name = vRequest::getString('name','none');

		JPluginHelper::importPlugin($type, $name);

		$render = null ;
		vDispatcher::directTrigger($type, $name, 'plgVmOnSelfCallBE', array($type, $name, &$render));
		if ($render) {
			$app = JFactory::getApplication();
			$document = JFactory::getDocument ();
			if (vRequest::getCmd ('cache') == 'no') {
				$app->setHeader ('Cache-Control', 'no-cache, must-revalidate');
				$app->setHeader ('Expires', 'Mon, 6 Jul 2000 10:00:00 GMT');
			}
			$format = vRequest::getCmd ('format', 'json');
			if ($format == 'json') {
				$document->setMimeEncoding ('application/json');
				// Change the suggested filename.
				$app->setHeader ('Content-Disposition', 'attachment;filename="' . $type . '.json"');
				$app->setHeader("Content-type","application/json");
				$app->sendHeaders();
				echo vmJsApi::safe_json_encode ($render);
			}
			else {
				echo $render;
			}
		}
		die();
	}

}
