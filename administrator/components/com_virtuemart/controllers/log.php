<?php
if( !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

/**
*
* @version
* @package VirtueMart
* @subpackage Log
* @copyright Copyright (C) VirtueMart Team - All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.org
*/

/**
 * Report Controller
 *
 * @package	VirtueMart
 * @subpackage Report
 * @author Wicksj
 */

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

class VirtuemartControllerLog extends VmController {

	/**
	 * Log Controller Constructor
	 */
	function __constuct(){
		parent::__construct();

	}
	/**
	 * Generic cancel task
	 *
	 */
	public function cancel(){
		// back from order
		$this->setRedirect('index.php?option=com_virtuemart&view=log' );
	}

	public function download(){

		if(vmAccess::manager('core')){
			$logFile = basename(vRequest::filterPath(vRequest::getString('logfile', '')));
			$config = JFactory::getConfig();
			$log_path = $config->get('log_path', VMPATH_ROOT . "/log");
			VirtueMartControllerInvoice::downloadFile($log_path . '/' . $logFile);
		}
		// back from order
		$this->setRedirect('index.php?option=com_virtuemart&view=log' );
	}

	public function delete() {

		if (vmAccess::manager('core')) {
			$logFile = basename(vRequest::filterPath(vRequest::getString('logfile', '')));
			$config = JFactory::getConfig();
			$log_path = $config->get('log_path', VMPATH_ROOT . "/log");
			$p = $log_path . '/' . $logFile;
			if(is_file($p)){
				File::delete($p);
			}
		}
		$this->setRedirect('index.php?option=com_virtuemart&view=log' );
	}
}
// pure php no closing tag