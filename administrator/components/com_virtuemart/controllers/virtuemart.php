<?php 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
/**
*
* Base controller
*
* @package	VirtueMart
* @subpackage Core
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2011 -2022 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id$
*/

/**
 * VirtueMart default administrator controller
 *
 * @package		VirtueMart
 */

class VirtuemartControllerVirtuemart extends VmController {


	public function __construct() {
		parent::__construct();
	}

	/**
	 *
	 * Task for disabling dangerous database tools, used after install
	 * @author Max Milbers
	 */
	public function disableDangerousTools(){

		$config = VmModel::getInstance('config', 'VirtueMartModel');
		$config->setDangerousToolsOff();
		$this->display();
	}


	public function keepalive(){
		//echo 'alive';
		jExit();
	}



	public function getMemberStatus() {

		vRequest::vmCheckToken();

		$data = new stdClass();
		if(!vmAccess::manager()){
			$data->msg = 'No rights';
			echo vmJsApi::safe_json_encode($data);
			jExit();
		}

		$request = 0;
		$ackey = VmConfig::get('member_access_number','');
		$host = JUri::getInstance()->getHost();


		if(!empty($host) AND !empty($ackey)) {

			$link = 'https://extensions.virtuemart.net/index.php?option=com_virtuemart&view=plugin&name=istraxx_download_byhost&ackey='.base64_encode( $ackey ).'&host='.$host.'&vmlang='.VmConfig::$vmlangTag.'&sku=VMMS&vmver='.vmVersion::$RELEASE;

			try {
				$resObj = VmConnector::getHttp(array(), array('curl', 'stream'))->get($link);
				$request = $resObj->body;
			}
			catch (RuntimeException $e) {
				$d = new stdClass();
				$d->res = 'No connection';
				$d->html = '<div style="color:#ff0000;font-size: 30px;line-height: 32px;">Your SERVER does not support allow_url_fopen, nor cUrl! Registration process stopped. Please enable on your server either allow_url_fopen or cUrl. '.$e->getMessage().'</div>';
				$request = json_encode($d);

			}

			if(!empty($request)) {
				/*if(preg_match('@(error|access denied)@i', $request)) {
					return false;
				} else {*/
					$datat = vmJsApi::safe_json_decode($request, false);

					if(empty($datat->res) or empty($datat->html)){
						vmEcho::$echoDebug = 1;
						vmdebug('Data is empty',$data);
						//$data = new stdClass();
						$data->msg = 'Error getting validation file';
					} else {
						$data = $datat;
						$data = $this->nag($data);

						if($data->res == 'valid'){
							//Lets update the update site
							$db = JFactory::getDbo();

							$query='SELECT `extension_id` FROM `#__extensions` WHERE `type`="component" AND `element`="com_virtuemart"';
							$db->setQuery($query);
							$extension_id=$db->loadResult();


							$q = 'SELECT * FROM `#__update_sites` as u LEFT JOIN #__update_sites_extensions as us on u.update_site_id = us.update_site_id WHERE `extension_id` = "'.(int)$extension_id.'"';
							$db->setQuery($q);
							$site = $db->loadObject();

							if($site){

								$extra_query = 'dlkey='.$data->ackey;


								if($site->extra_query!=$extra_query){
									$q = 'UPDATE `#__update_sites` SET `extra_query`="'.$extra_query.'" WHERE update_site_id = "'.$site->update_site_id.'"';
									$db->setQuery($q);
									$db->execute();
								}
							} else {
								$data->msg .= 'Update site missing for extension id '.$extension_id;
							}
						}
					}
				//}
			}
		}
		echo vmJsApi::safe_json_encode($data);
		jExit();
	}

	private function nag($data){

		if(!empty($data->res)){

			if(!empty($data->html)){

				$safePath = shopfunctions::getSafePathFor(1,'regcache');
				$safePath .= DS.'vmm.ini';
				$date = JFactory::getDate();
				$today = $date->toUnix();

				$content = ';<?php die(); */
					[keys]
					key = "'.VmConfig::get('member_access_number','').'"
					unixtime = "'.$today.'"
					res = "'.vRequest::vmSpecialChars($data->res).'"
					html = "'.vRequest::vmSpecialChars($data->html).'"
					; */ ?>';
				$result = JFile::write($safePath, $content);
			}
		}
		return $data;
	}
}
