<?php
/**
 * Access helper class
 *
 * Handles ACL, provides some shortcuts and handling of a background manager
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2015-2018 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL 2, see COPYRIGHT.php
 */
defined('_JEXEC') or die('Restricted access');

class vmAccess {

	static protected $_virtuemart_vendor_id = array();
	static protected $_manager = array();
	static protected $_cu = array();
	static protected $_cuId = null;
	static protected $_site = null;

	static public function getBgManagerId(){

		if(!isset(self::$_cuId)){
			$cuId = JFactory::getSession()->get('vmAdminID',null);

			//echo $cuId;
			if($cuId) {
				$cuId = vmCrypt::decrypt( $cuId );
				if(empty($cuId)){
					$cuId = null;
				}
			}
			self::$_cuId = $cuId;
		}

		return self::$_cuId;
	}

	static public function getBgManager($uid = 0){

		if(!isset(self::$_cu[$uid])){
			if($uid === 0){
				if(self::$_site){
					$ui = self::getBgManagerId();
				} else{
					$ui = null;
				}
			} else {
				$ui = $uid;
			}
			self::$_cu[$uid] = JFactory::getUser($ui);
		}

		return self::$_cu[$uid];
	}

	/**
	 * Checks if user is admin or has vendorId=1,
	 * if superadmin, but not a vendor it gives back vendorId=1 (single vendor, but multiuser administrated)
	 *
	 * @author Mattheo Vicini
	 * @author Max Milbers
	 */
	static public function isSuperVendor($uid = 0, $task=0){

		if(self::$_site === null) {
			self::$_site = VmConfig::isSiteByApp();
		}

		if(!isset(self::$_cu[$uid])){
			self::$_cu[$uid] = self::getBgManager($uid);
		}
		$user = self::$_cu[$uid];

		if(!isset(self::$_virtuemart_vendor_id[$uid])){

			self::$_virtuemart_vendor_id[$uid] = 0;
			if(!empty( $user->id)){
				$q='SELECT `virtuemart_vendor_id` FROM `#__virtuemart_vmusers` as `au`
				WHERE `au`.`virtuemart_user_id`="' .$user->id.'" AND `au`.`user_is_vendor` = "1" ';

				$db= JFactory::getDbo();
				$db->setQuery($q);
				$virtuemart_vendor_id = $db->loadResult();

				if ($virtuemart_vendor_id) {
					self::$_virtuemart_vendor_id[$uid] = $virtuemart_vendor_id;
					vmdebug('isSuperVendor Active vendor '.$uid.' '.$virtuemart_vendor_id );
				} else {
					//$multix = Vmconfig::get('multix','none');
					if( /*($multix == 'none' and*/ self::manager($task, $uid)/*) or ($multix != 'none' and (self::manager($task) or self::manager('managevendors')) )*/){
						//vmTrace('isSuperVendor Fallback to Mainvendor '.$task);
						self::$_virtuemart_vendor_id[$uid] = 1;
					} else {
						self::$_virtuemart_vendor_id[$uid] = 0;
					}
				}
			}
			if($uid==0){
				self::$_virtuemart_vendor_id[$user->id] = self::$_virtuemart_vendor_id[$uid];
				//vmdebug('isSuperVendor Set '.$user->id.' to '.self::$_virtuemart_vendor_id[$uid]);
			}
			if(self::$_virtuemart_vendor_id[$uid] <= 0) vmdebug('isSuperVendor Not a vendor '.$uid.' '.self::$_virtuemart_vendor_id[$uid]);
		}
		//vmdebug('isSuperVendor return for id '.$uid.$task.' vendor id '.self::$_virtuemart_vendor_id[$uid] );
		return self::$_virtuemart_vendor_id[$uid];
	}


	static public function manager($task=0, $uid = 0, $and = false) {


		if(self::$_site === null) {
			self::$_site = VmConfig::isSiteByApp();
		}

		if(!isset(self::$_cu[$uid])){
			self::$_cu[$uid] = self::getBgManager($uid);
		}
		$user = self::$_cu[$uid];

		if(!empty($task) and !is_array($task)){
			$task = array($task);
		}

		$h = serialize($task).$uid;

		if(!isset(self::$_manager[$h])) {
			if(WP_VERSION){	//Atm for Wordpress
				$u = wp_get_current_user();
				self::$_manager[$h] = $u->has_cap('administrator');
				return self::$_manager[$h];
			}
			if($user->authorise('core.admin') or $user->authorise('core.admin', 'com_virtuemart')) {
				self::$_manager[$h] = true;
			} else {
				self::$_manager[$h] = false;

				if(empty($task)){
					$a = $user->authorise('core.manage', 'com_virtuemart');
					if(!$a and self::$_site){
						$a = $user->authorise('vm.manage', 'com_virtuemart');
					}
					self::$_manager[$h] = $a;
				} else {
					foreach($task as $t){
						//vmdebug('Authorise against '.$t);
						if($user->authorise('vm.'.$t, 'com_virtuemart')){
							self::$_manager[$h] = true;
							if(!$and) break;
						}
						else if($and) {
							self::$_manager[$h] = false;
							break;
						}
					}
				}
			}
		}

		return self::$_manager[$h];
	}

	/**
	 * This function returns in the Frontend for nonvendors just the vendorid by REQUEST, a shopper needs information of a vendor
	 * If the FE user is a manager it just wents on to the backend return function
	 * if the user is allowed to manage vendors, the request is returned (a manager is editing a vendor, or checking items of this vendor), if no id is given, it just returns the isSuperVendor
	 * if the user is not allwed to manage vendors, it just returns the isSuperVendor
	 * @param int $task
	 * @param int $uid
	 * @param string $name
	 * @return array|array[]|false|int|mixed|null
	 */
	public static function getVendorId($task=0, $uid = 0, $name = 'virtuemart_vendor_id'){

		if(self::$_site === null) {
			self::$_site = VmConfig::isSiteByApp();
		}

		if(self::$_site){
			$feM = self::isManagingFE($task);
			if(!$feM){
				//normal shopper in FE and NOT in the FE managing mode
				//vmdebug('getVendorId normal shopper');
				return vRequest::getInt($name,false);
			}
		}

		if($task === 0){
			$task = 'managevendors';
		} else if(is_array($task)) {
			$task[] = 'managevendors';
		} else {
			$task = array($task,'managevendors');
		}
		if(self::manager($task, $uid)){
			vmdebug('getVendorId manager');
			return vRequest::getInt($name,self::isSuperVendor($uid));
		} else {
			return self::isSuperVendor($uid);
		}
	}

	static public function isFEmanager ($task = 0, $uid = 0) {

		if(!isset(self::$_cu[$uid])){
			self::$_cu[$uid] = self::getBgManager($uid);
		}

		$user = self::$_cu[$uid];
		if($user->guest){
			vmdebug('isFEmanager return false because user is guest');
			return false;
		}

		if($task == 'virtuemart') $task = 0; //There is no extra permission for the view virtuemart, it is just the standard perm

		if(empty($task)){
			$task = array('manage');
		} else if(!is_array($task)){
			$task = array($task);
			$task[] = 'manage';
		}
		//vmdebug('isFEmanager checks for tasks ',$task);
		return self::manager($task, 0 , true);
	}

	static private $isManager = array();

	/**
	 * Wondered why it was set to managing off, the reason is the category tree, which uses the view, which sets no author and sets
	 * managing=0
	 * @return int|null
	 */
	static public function isManagingFE($view){

		if(self::$_site === null) {
			self::$_site = VmConfig::isSiteByApp();
		}

		if(!self::$_site) return false;

		if($view == 'ajax'){
			$view = 0;
		}
		//vmTrace('isManagingFE called for view '. $view);
		if(!isset(self::$isManager[$view])){
			$sess = JFactory::getSession();
			$sessionManage = (int) $sess->get('managing', false,'vm');
			self::$isManager[$view] = (int) vRequest::getInt('managing', $sessionManage, $_GET);
			//self::$isManager[$view] = (int) vRequest::getInt('managing', 0);
			//vmdebug('isManagingFE result by session/GET for view '.$view, self::$isManager[$view]);
			if($sessionManage != self::$isManager[$view]){
				$sess->set('managing', self::$isManager[$view],'vm');
				vmdebug('Wrote session to db, managing stored '.(int)self::$isManager[$view]);
			}
			if(self::$isManager[$view] and !vmAccess::isFEmanager($view) ){
				self::$isManager[$view] = 0;
				$adminError = vmText::sprintf('COM_VIRTUEMART_RESTRICTED_ACCESS_VIEW',$view);
				vmError($adminError, $adminError);
				vmTrace('isManagingFE restricted access for view '. $view);

				if(vmAccess::isFEmanager()){
					vRequest::setVar('view','virtuemart');
					$app = JFactory::getApplication();
					$app->redirect('index.php?option=com_virtuemart&tmpl=component$view=virtuemart&managing=1');
				} else {
					vRequest::setVar('tmpl');

				}
			}
		}

		return self::$isManager[$view];
	}

}
