<?php

/**
 * abstract controller class containing get,store,delete,publish and pagination
 *
 *
 * This class provides the functions for the calculatoins
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2021 VirtueMart Team. All rights reserved.
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

class vmActionLogger{

	static $extension=null;

	/**
	 * Record transaction details in log record
	 * @param   object  $user    Saves getting the current user again.
	 * @param   int     $tran_id  The transaction id just created or updated
	 * @param   int     $id  Passed id reference from the form to identify if new record
	 * @return  boolean	True
	 */
	public static function recordActionLog($user = null, $tran_id = 0, $id = 0)
	{
		// get the component details such as the id
		if(vmActionLogger::$extension===null){
			vmActionLogger::getExtensionDetails('com_virtuemart');
		}

		// get the transaction details for use in the log for easy reference
		$tran = MycomponentHelper::getTransaction($tran_id);
		$con_type = "transaction";
		if ($id === 0) { $type = 'New '; } else { $type = 'Update '; }

		$message = array();
		$message['action'] = $con_type;
		$message['type'] = $type . $tran->tran_type . ' - '.$tran->tran_desc . ' $' . $tran->tran_amount;
		$message['id'] = $tran->id;
		$message['title'] = $extension->name;
		$message['extension_name'] = $extension->name;
		$message['itemlink'] = "index.php?option=com_mycomponent&task=transaction.edit&id=".$tran->id;
		$message['userid'] = $user->id;
		$message['username'] = $user->username;
		$message['accountlink'] = "index.php?option=com_users&task=user.edit&id=".$user->id;

		$messages = array($message);

		$messageLanguageKey = vmText::_('COM_MYCOMPONENT_TRANSACTION_LINK');
		$context = $extension->name.'.'.$con_type;

		$fmodel = vmActionLogger::getForeignModel('Actionlog', 'ActionlogsModel');

		$fmodel->addLog($messages, $messageLanguageKey, $context, $user->id);

		return true;
	}

	/**
	 * Get the Model from another component for use
	 * @param   string  $name    The model name. Optional. Default to my own for safety.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 * @return object	The model
	 */
	public function getForeignModel($name = 'Transaction', $prefix = 'MycomponentModel', $config = array('ignore_request' => true))
	{
		\Joomla\CMS\MVC\Model\ItemModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_actionlogs/models', 'ActionlogsModelActionlog');
		$fmodel = \Joomla\CMS\MVC\Model\ItemModel::getInstance($name, $prefix, $config);

		return $fmodel;
	}

	public function getExtensionDetails(){

		$q = 'Select * from #__extensions where `element`="com_virtuemart"';
		$db = JFactory::getDbo();
		$db->setQuery($q);
		vmActionLogger::$extension = $db->loadObject();
	}

}