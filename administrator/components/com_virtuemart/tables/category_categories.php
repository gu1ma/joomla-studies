<?php
/**
*
* category_categories table ( to map calc rules to shoppergroups)
* This table is now deprecated and just there for legacy reasons.
* @package	VirtueMart
* @subpackage nested categories
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2011 - 2021 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: category_categories.php 3002 2011-04-08 12:35:45Z alatak $
*/

defined('_JEXEC') or die();

class TableCategory_categories extends VmTable {

	var $id = null;
	var $category_parent_id = 0;
	var $category_child_id = 0;
	var $ordering = 0;


	/**
	 * @author Max Milbers
	 * @param JDatabase $db connector object
	 */
	function __construct( &$db){
		parent::__construct('#__virtuemart_category_categories', 'id', $db);

		$this->setPrimaryKey('category_child_id');
		//$this->setSecondaryKey('category_parent_id');
		$this->setOrderable();
		$this->setTableShortCut('cx');
	}

	function check() {

		if(empty($this->id)){
			$this->loadTblKeyByPrimaryAnd('category_parent_id');
		}

		return parent::check();

	}

}