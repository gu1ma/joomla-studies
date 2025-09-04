<?php


class VmClearUp extends VmProcess {

	public function __construct () {
		parent::__construct();
		VirtueMartModelUser::$startTime = $this->starttime;
		VirtueMartModelUser::$maxScriptTime = $this->maxScriptTime;

	}

	static public function withVmSystemPlugin(){

		static $id = null;
		if(!isset($id)){
			//If the vm system plugin is enabled, then the deletion of the joomla user triggers the vmuser deletion, so lets check if it active
			$q = 'SELECT extension_id from #__extensions WHERE element="vmloaderpluginupdate" and enabled="1" and `state`="0" ';
			$db = JFactory::getDBO();
			$db->setQuery($q);
			$id = $db->loadResult();
		}
		return $id;
	}

	public function deleteVmUsers($userIds,$ignoreIds){

		$id = self::withVmSystemPlugin();

		if($id){
			foreach($userIds as $userId){

				if($this->_stop || (microtime(true)-$this->starttime) >= ($this->maxScriptTime)){
					vmInfo('Clear up Users. Times up. Please execute the job again');
					return;
				}

				if(isset($ignoreIds)){
					if(in_array($userId,$ignoreIds)){
						vmdebug('Did not delete this user, because on ignore list');
						continue;
					}
				}

				$_JUser = JUser::getInstance($userId);
				$_JUser->delete();

			}
		} else {
			VirtueMartModelUser::removeS($userIds, true, $ignoreIds);
		}
	}

	public function removeSpamUsers(){
		//We gather first the superadmins, the groupid is in j3 and j4 just 8
		$adminIds = self::getUsersWithRole();

		$jnow = JFactory::getDate();
		$jnow->sub(new DateInterval('P2W'));
		$date = $jnow->toSQL();

		//First users with lastvisitDate = null and registerDate at least 2 weeks old
		$q = 'SELECT id from #__users WHERE '.vmTable::checkFieldNullDateSQL('lastvisitDate').' AND  registerDate < "'.$date.'" ';

		$db = JFactory::getDBO();
		$db->setQuery($q);
		vmdebug('my query ',$q);
		$userIds = $db->loadColumn();

		self::deleteVmUsers($userIds, $adminIds);


		//In old times, it was possible to delete joomla users without deleting the vm user, so we must find these orphaned entries
		$q = 'SELECT vmu.virtuemart_user_id from #__virtuemart_vmusers as vmu
				LEFT JOIN #__users as u ON u.id=vmu.virtuemart_user_id 
				WHERE ISNULL(u.id) ';
		$db = JFactory::getDBO();
		$db->setQuery($q);
		$userIds = $db->loadColumn();

		VirtueMartModelUser::removeS($userIds, false, $adminIds);

	}

	public function removeJoomlaUsersNoShoppers(){

		//We gather first the superadmins, the groupid is in j3 and j4 just 8
		$adminIds = self::getUsersWithRole();

		$extras = vRequest::getString('extraTables', false);

		$jnow = JFactory::getDate();
		$jnow->sub(new DateInterval('P6M'));
		$date = $jnow->toSQL();

		//Next joomla users without vmusers and/or without any order at least 2 weeks old
		$q = 'SELECT id from #__users as u 
				LEFT JOIN #__virtuemart_vmusers as vmu ON u.id=vmu.virtuemart_user_id 
				LEFT JOIN #__virtuemart_orders as o ON u.id=o.virtuemart_user_id ';
		if($extras){

		}
		$q .= ' WHERE (ISNULL(vmu.virtuemart_user_id) OR ISNULL(o.virtuemart_user_id))
				AND  u.registerDate < "'.$date.'"';

		$db = JFactory::getDBO();
		$db->setQuery($q);
		$userIds = $db->loadColumn();
		$userIds = array_diff($userIds,$adminIds);
		VmInfo('Removing SPAM user with ids '.vmEcho::varPrintR(array($userIds)));
		logInfo('Removing SPAM user with ids '.vmEcho::varPrintR(array($userIds)));

		self::deleteVmUsers($userIds, $adminIds);

	}

	public function removeShpprsInactiveY($years){

		$adminIds = self::getUsersWithRole();

		$jnow = JFactory::getDate();
		$jnow->sub(new DateInterval('P'.$years.'Y'));
		$date = $jnow->toSQL();

		$q = 'SELECT id from #__users as u 
LEFT OUTER JOIN #__user_usergroup_map as m ON u.id=m.user_id 
LEFT OUTER JOIN #__virtuemart_vmusers as vmu ON u.id=vmu.virtuemart_user_id
WHERE (ISNULL (group_id) OR group_id IN (1,2,9) ) AND (ISNULL (vmu.virtuemart_vendor_id) or vmu.virtuemart_vendor_id = 0)
		AND	u.lastvisitDate < "'.$date.'" ';

		$db = JFactory::getDBO();
		$db->setQuery($q);
		$userIds = $db->loadColumn();

		$userIds = array_diff($userIds,$adminIds);
		VmInfo('Removing outdated shopper with ids '.vmEcho::varPrintR(array($userIds)));
		logInfo('Removing outdated shopper with ids '.vmEcho::varPrintR(array($userIds)));

		self::deleteVmUsers($userIds, $adminIds);
	}

	public function removeOrdersInvoicesY($years){

		$jnow = JFactory::getDate();
		$jnow->sub(new DateInterval('P'.$years.'Y'));
		$date = $jnow->toSQL();
		$q = 'SELECT virtuemart_order_id FROM #__virtuemart_orders WHERE ('.vmTable::checkFieldNullDateSQL('modified_on').' and created_on < "'.$date.'") OR modified_on < "'.$date.'"';
		$db = JFactory::getDbo();
		$db->setQuery($q);

		$orderIds = $db->loadColumn();
		VmInfo('Found '.count($orderIds).' entries to remove');
		vmdebug('removeOrdersInvoicesY my result ',$q,$orderIds);

		logInfo('Job: Remove old orders. Deleting all orders with ids '.VmEcho::varPrintR(array($orderIds)));

		$jnow = JFactory::getDate();
		if($orderIds){
			$orderM = VmModel::getModel('orders');

			$db = JFactory::getDBO();

			//First we delete by Order
			foreach($orderIds as $virtuemart_order_id){

				if($this->_stop || (microtime(true)-$this->starttime) >= ($this->maxScriptTime)){
					vmInfo('Clear up orders. Times up. Please execute the job again');
					return;
				}
				$orderM->removeOrderItems($virtuemart_order_id);
				$orderM->removePSDataOfOrder($virtuemart_order_id);

				//If Guest Order, or removed user, delete anything
				$q = 'SELECT o.virtuemart_user_id FROM `#__virtuemart_orders` as o LEFT JOIN #__virtuemart_vmusers as u ON o.virtuemart_user_id = u.virtuemart_user_id
 						WHERE `virtuemart_order_id` = "' .(int) $virtuemart_order_id.'" !ISNULL(o_hash)';
				$db->setQuery($q);
				$virtuemart_user_id = $db->loadResult();

				if(!$virtuemart_user_id){
					$q ='DELETE from `#__virtuemart_orders` WHERE `virtuemart_order_id` = ' .(int) $virtuemart_order_id;
				} else {
					// else we delete almost anything except the relation and the ordernumber, but keep that user is a real long term customer, we update modified_on,
					// so it is not touched the next time
					$q ='UPDATE `#__virtuemart_orders` SET ';
					$q .= 'order_note = NULL, customer_number=NULL, order_pass=NULL, order_create_invoice_pass=NULL, invoice_locked=0, 
				order_total = "0.00000",order_total = "0.00000",order_salesPrice = "0.00000",order_billTaxAmount = "0.00000",order_billTax = "0.00000",
				order_billDiscountAmount = "0.00000",order_discountAmount = "0.00000",paid = "0.00000",coupon_discount = "0.00",order_discount = "0.00",
				user_currency_rate = "1.00", payment_currency_rate = "1.00",
				order_billTax= NULL, order_subtotal=NULL, order_tax=NULL, order_shipment=NULL, order_shipment_tax=NULL, order_payment=NULL, order_payment_tax=NULL,
				coupon_code=NULL, order_currency=NULL, user_currency_id=NULL, user_shoppergroups=NULL, payment_currency_id=NULL, virtuemart_paymentmethod_id=NULL,
				virtuemart_shipmentmethod_id=NULL, delivery_date=NULL, order_language=NULL, ip_address="", STsameAsBT=0,paid_on = NULL, o_hash=NULL,
				created_by=0, modified_by=0,locked_by=0, modified_on="'.$jnow.'", locked_on=NULL';
					$q .= '	WHERE `virtuemart_order_id` = ' .(int) $virtuemart_order_id;
				}


				$db->setQuery($q);

				$ok = true;
				if ($db->execute() === false) {

				}

				$q ='SELECT invoice_number from `#__virtuemart_invoices` WHERE `virtuemart_order_id` = ' .(int) $virtuemart_order_id;
				$db->setQuery($q);
				$invoiceNumbers = $db->loadColumn();
				foreach($invoiceNumbers as $invoiceNumber){
					$path = VirtueMartModelInvoice::getInvoicePath();
					$layout = 'invoice';
					$pathInvoice = $path . shopFunctionsF::getInvoiceName($invoiceNumber, $layout).'.pdf';
					if(JFile::exists($pathInvoice)){
						JFile::delete($pathInvoice);
					}
					$layout = 'refund';
					$pathRefund =  $path . shopFunctionsF::getInvoiceName($invoiceNumber, $layout).'.pdf';
					if(JFile::exists($pathRefund)){
						JFile::delete($pathRefund);
					}
				}

				$q ='DELETE from `#__virtuemart_invoices` WHERE `virtuemart_order_id` = ' .(int) $virtuemart_order_id;

				$db->setQuery($q);
				$ok = true;
				if ($db->execute() === false) {

				}

			}
		}

		//We add 5 months and do an extra clean up.
		//$jnow = JFactory::getDate();
		//$jnow->sub(new DateInterval('P'.$years.'Y5M'));
		//$date = $jnow->toSQL();


		self::removeByPeriodCheckOrderIdExists('order_items',$date);
		self::removeByPeriodCheckOrderIdExists('order_calc_rules',$date);
		self::removeByPeriodCheckOrderIdExists('order_histories',$date);
		self::removeByPeriodCheckOrderIdExists('invoices',$date);

		$path = VirtueMartModelInvoice::getInvoicePath();

		//too risky, if the shop was moved, the cdate and mdate is updated.
		/*if(vRequest::get('DeleteByFileCDate',false)){
			$deletedFiles = array();
			if($handle = opendir($path)){
				$unixDate = $jnow->toUnix();
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != "..") {
						$mTime = filemtime($path.DS.$file);
						if($mTime<$unixDate){
							//JFile::delete($path.DS.$file);
							$deletedFiles[] = $file;
						}
						//vmdebug('removeOrdersInvoicesY files',$file,$mTime);
					}
				}
			}
			vmInfo('Job: Remove old orders. Deleted all outdated files '.implode(', ',$deletedFiles));
			logInfo('Job: Remove old orders. Deleted all outdated files '.implode(', ',$deletedFiles));
		}*/


	}

	static function removeByPeriodCheckOrderIdExists($tableShort, $date){

		$q ='SELECT d.virtuemart_order_id from `#__virtuemart_'.$tableShort.'` as d LEFT OUTER JOIN `#__virtuemart_orders` as o ON d.virtuemart_order_id = o.virtuemart_order_id 
		WHERE ( ISNULL(d.virtuemart_order_id) OR o.o_hash=NULL) and '.vmTable::checkFieldNullDateSQL('d`.`modified_on').' AND d.modified_on < "'.$date.'" ';

		$db = JFactory::getDBO();
		$db->setQuery($q);

		try{
			$ids = $db->loadColumn() ;
		} catch (Exception $e){
			vmError('removeByPeriodCheckOrderIdExists '.vmEcho::varPrintR($e));
		}

		if(!empty($ids)){
			$q ='DELETE from `#__virtuemart_'.$tableShort.'`  
		WHERE  virtuemart_order_id IN('.implode(', ',$ids).');';

			$db = JFactory::getDBO();
			$db->setQuery($q);

			try{
				$db->execute() ;
			} catch (Exception $e){
				vmError('removeByPeriodCheckOrderIdExists '.vmEcho::varPrintR($e));
			}
		}

	}

	static function getUsersWithRole(){
		static $adminIds = null;

		if(!isset($adminIds)){
			//We gather first the superadmins, the groupid is in j3 and j4 just 8
			$q = 'SELECT id from #__users as u LEFT JOIN #__user_usergroup_map as m ON u.id=m.user_id WHERE group_id NOT IN (1,2,9) AND u.block = "0"';

			$db = JFactory::getDBO();
			$db->setQuery($q);
			$adminIds = $db->loadColumn();
		}

		return $adminIds;
	}

	public function removeShpprOrders($userId){

		//We gather first the superadmins, the groupid is in j3 and j4 just 8
		$adminIds = self::getUsersWithRole();
		self::deleteVmUsers(array($userId), $adminIds);
	}

	public function removeOldCarts(){

		$jnow = JFactory::getDate();
		$jnow->sub(new DateInterval('P6M'));
		$date = $jnow->toSQL();
		$q = 'SELECT virtuemart_cart_id FROM #__virtuemart_carts WHERE ('.vmTable::checkFieldNullDateSQL('modified_on').' and created_on < "'.$date.'") OR modified_on < "'.$date.'"';
		$db = JFactory::getDbo();
		$db->setQuery($q);

		$cartIds = $db->loadColumn();
		VmInfo('Found '.count($cartIds).' old carts to remove');
		vmdebug('removeOldCarts my result ',$q,$cartIds);

		if($cartIds){
			$q = 'DELETE FROM #__virtuemart_carts WHERE ('.vmTable::checkFieldNullDateSQL('modified_on').' and created_on < "'.$date.'") OR modified_on < "'.$date.'"';
			$db = JFactory::getDbo();
			$db->setQuery($q);
			$db->execute();
		}

	}

}