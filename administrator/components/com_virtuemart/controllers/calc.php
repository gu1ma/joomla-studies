<?php
/**
*
* Calc controller
*
* @package	VirtueMart
* @subpackage Calc
* @author Max Milbers, jseros
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: calc.php 10821 2023-04-17 18:40:52Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Calculator Controller
 *
 * @package    VirtueMart
 * @subpackage Calculation tool
 * @author Max Milbers
 */
class VirtuemartControllerCalc extends VmController {

	/**
	 * Method to display the view
	 *
	 * @access	public
	 */
	public function __construct() {
		parent::__construct();

	}



	/**
	 * We want to allow html so we need to overwrite some request data
	 *
	 * @author Max Milbers
	 */
	function save($data = 0){

		$data = vRequest::getPost();

		$data['calc_name'] = vRequest::getHtml('calc_name','');
		$data['calc_descr'] = vRequest::getHtml('calc_descr','');
		if(isset($data['params'])){
			$data['params'] = vRequest::getHtml('params','');
		}
		parent::save($data);
	}


	/**
	* Save the calc order
	*
	* @author jseros
	*/
	public function orderUp()
	{
		// Check token
		vRequest::vmCheckToken();

		$cid	= vRequest::getInt( 'cid', array() );

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			vmWarn('COM_VIRTUEMART_NO_ITEMS_SELECTED');
			$this->setRedirect( 'index.php?option=com_virtuemart&view=calc' );
			return false;
		}

		$model = VmModel::getModel('calc');

		if ($model->orderCalc($id, -1)) {
			vmInfo ('COM_VIRTUEMART_ITEM_MOVED_UP');
		}

		$this->setRedirect( 'index.php?option=com_virtuemart&view=calc' );
	}


	/**
	* Save the calc order
	*
	* @author jseros
	*/
	public function orderDown()
	{
		// Check token
		vRequest::vmCheckToken();
		
		$cid	= vRequest::getInt( 'cid', array() );

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			vmWarn('COM_VIRTUEMART_NO_ITEMS_SELECTED');
			$this->setRedirect( 'index.php?option=com_virtuemart&view=calc' );
			return false;
		}

		//getting the model
		$model = VmModel::getModel('calc');
		$msg = '';
		if ($model->orderCalc($id, 1)) {
			vmInfo('COM_VIRTUEMART_ITEM_MOVED_DOWN');
		}

		$this->setRedirect( 'index.php?option=com_virtuemart&view=calc' );
	}


	/**
	* Save the categories order
	*/
	public function saveOrder()
	{
		// Check for request forgeries
		vRequest::vmCheckToken();

		$cid	= vRequest::getInt( 'cid', array() );

		$model = VmModel::getModel('calc');

		$order	= vRequest::getInt('order');

		$msg = '';
		if ($model->setOrder($cid,$order)) {
			vmInfo('COM_VIRTUEMART_NEW_ORDERING_SAVED');
		}
		$this->setRedirect('index.php?option=com_virtuemart&view=calc');
	}

}
// pure php no closing tag
