<?php
/**
*
* Category controller
*
* @package	VirtueMart
* @subpackage Category
* @author Max Milbers, jseros
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: category.php 10821 2023-04-17 18:40:52Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');


class VirtuemartControllerCategory extends VmController {


	/**
	 * We want to allow html so we need to overwrite some request data
	 *
	 * @author Max Milbers
	 */
	function save($data = 0){

		//ACL
		if (!vmAccess::manager('category.edit')) {
			vmError('JERROR_ALERTNOAUTHOR', 'JERROR_ALERTNOAUTHOR');
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart');
		}
		
		$data = vRequest::getPost();

		$this->getStrByAcl(array('category_description','category_name'),$data);

		parent::save($data);
	}


	/**
	* Save the category order
	*
	* @author jseros
	*/
	public function orderUp()
	{
		//ACL
		if (!vmAccess::manager('category.edit')) {
			vmError('JERROR_ALERTNOAUTHOR', 'JERROR_ALERTNOAUTHOR');
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart');
		}

		// Check token
		vRequest::vmCheckToken();

		//capturing virtuemart_category_id
		$cid	= vRequest::getInt( 'cid', array() );

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			vmWarn('COM_VIRTUEMART_NO_ITEMS_SELECTED');
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart&view=category' );
			return false;
		}

		//getting the model
		$model = VmModel::getModel('category');

		if ($model->orderCategory($id, -1)) {
			vmInfo('COM_VIRTUEMART_ITEM_MOVED_UP');
		}

		JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart&view=category' );
	}


	/**
	* Save the category order
	*
	* @author jseros
	*/
	public function orderDown()
	{
		//ACL
		if (!vmAccess::manager('category.edit')) {
			vmError('JERROR_ALERTNOAUTHOR', 'JERROR_ALERTNOAUTHOR');
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart');
		}
		
		// Check token
		vRequest::vmCheckToken();

		//capturing virtuemart_category_id
		$cid	= vRequest::getInt( 'cid', array() );

		if (isset($cid[0]) && $cid[0]) {
			$id = $cid[0];
		} else {
			vmWarn('COM_VIRTUEMART_NO_ITEMS_SELECTED');
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart&view=category' );
			return false;
		}

		//getting the model
		$model = VmModel::getModel('category');

		if ($model->orderCategory($id, 1)) {
			vmInfo('COM_VIRTUEMART_ITEM_MOVED_DOWN');
		}

		JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart&view=category' );
	}


	/**
	* Save the categories order
	*/
	public function saveOrder()
	{
		//ACL
		if (!vmAccess::manager('category.edit')) {
			vmError('JERROR_ALERTNOAUTHOR', 'JERROR_ALERTNOAUTHOR');
			JFactory::getApplication()->redirect( 'index.php?option=com_virtuemart');
		}
		return parent::saveOrder();
	}

}
