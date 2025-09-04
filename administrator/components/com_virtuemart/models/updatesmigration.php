<?php
/**
*
* Data module for updates and migrations
*
* @package	VirtueMart
* @subpackage updatesMigration
* @author Max Milbers, RickG
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2022 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: updatesmigration.php 10794 2023-02-27 14:40:08Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Model class for updates and migrations
 *
 * @package	VirtueMart
 * @subpackage updatesMigration
 * @author Max Milbers
 */
class VirtueMartModelUpdatesMigration extends VmModel {

    /**
     * @author Max Milbers
     */
    function determineStoreOwner() {

		$virtuemart_user_id = VirtueMartModelVendor::getUserIdByVendorId(1);
		if (isset($virtuemart_user_id) && $virtuemart_user_id > 0) {
		    $this->_user = JFactory::getUser($virtuemart_user_id);
		}
		else {
		    $this->_user = JFactory::getUser();
		}
		return $this->_user->id;
    }


    /**
     * @author Max Milbers
     */
	function setStoreOwner($userId=-1) {

		if(!vmAccess::manager('core')){
			vmError('You have no rights to performe this action');
			return false;
		}

		if (empty($userId)) {
			vmInfo('No uer id given, can not set vendor relation');
			return false;
		}

		if ($userId===-1 or empty($userId)) {
			$userId = $this->determineStoreOwner();
			vmdebug('setStoreOwner $userId = '.$userId.' by determineStoreOwner');
		}

		if ( empty($userId)) return false;

		$db = JFactory::getDBO();

		$db->setQuery( 'SELECT `virtuemart_user_id` FROM `#__virtuemart_vmusers` WHERE `virtuemart_user_id` ="'.$userId.'" ');
		$vmuid = $db->loadResult();

		$multix = Vmconfig::get('multix', 'none');

		$q = 'UPDATE `#__virtuemart_vmusers` SET `virtuemart_vendor_id` = "0", `user_is_vendor` = "0" ';
		if($multix!='none'){
			$q .= 'WHERE `virtuemart_vendor_id` = "1" ';
		}
		$db->setQuery($q);
		if ($db->execute() == false ) {
			vmWarn( 'UPDATE __vmusers failed for virtuemart_user_id '.$userId.' query '.$q);
			//return false;
		}

		$utable = $this->getTable('vmusers');

		if($vmuid){
			$data = array('virtuemart_user_id' => $userId, 'virtuemart_vendor_id' => "1", 'user_is_vendor' => "1");
			if($utable->bindChecknStore($data,true)){
				vmInfo('setStoreOwner VmUser updated new main vendor has user id  '.$userId);
			}
		} else {
			//Can not use the table here, it would set the virtuemart_vendor_id to zero if there is no entry
			$date = JFactory::getDate();
			$today = $date->toSQL();
			$db->setQuery('INSERT INTO `#__virtuemart_vmusers` (`virtuemart_user_id`,`virtuemart_vendor_id`,`user_is_vendor`,`created_on`)
					VALUES ("'.$userId.'","1","1","'.$today.'") ');
			if ($db->execute() == false ) {
				vmWarn( 'INSERT __vmusers failed for virtuemart_user_id '.$userId);
				return false;
			}
		}

	    return $userId;
    }



    /**
     * Installs sample data to the current database.
     *
     * @author Max Milbers, RickG
     * @params $userId User Id to add the userinfo and vendor sample data to
     */
    function installSampleData($userId = null) {

	if ($userId == null) {
	    $userId = $this->determineStoreOwner();
	}

    if(!VmConfig::$vmlang){
	    vmLanguage::initialise();
    }

	$fields['username'] =  $this->_user->username;
	$fields['virtuemart_user_id'] =  $userId;
	$fields['address_type'] =  'BT';
	// Don't change this company name; it's used in install_sample_data.sql
	$fields['company'] =  "Sample Company";
	$fields['title'] =  'Mr';
	$fields['last_name'] =  'John';
	$fields['first_name'] =  'Doe';
	$fields['middle_name'] =  '';
	$fields['phone_1'] =  '555-555-555';
	$fields['address_1'] =  'PO Box 123';
	$fields['city'] =  'Seattle';
	$fields['zip'] =  '98101';
	$fields['virtuemart_state_id'] =  '48';
	$fields['virtuemart_country_id'] =  '223';

	//Dont change this, atm everything is mapped to mainvendor with id=1
	$fields['user_is_vendor'] =  '1';
	$fields['virtuemart_vendor_id'] = '1';
	$fields['vendor_name'] =  'Sample Company';
		//quickndirty hack for vendor_phone
		vRequest::setVar('phone_1',$fields['phone_1']);
	//$fields['vendor_phone'] =  '555-555-1212';
	$fields['vendor_store_name'] =  "VirtueMart 3 Sample store";
	$fields['vendor_store_desc'] =  '<p>Welcome to VirtueMart the ecommerce managment system. The sample data give you a good insight of the possibilities with VirtueMart. The product description is directly the manual to configure the demonstrated features. </p><p>You see here the store description used to describe your store. Check it out!</p> <p>We were established in 1869 in a time when getting good clothes was expensive, but the quality was good. Now that only a select few of those authentic clothes survive, we have dedicated this store to bringing the experience alive for collectors and master carrier everywhere.</p>';
	$fields['virtuemart_media_id'] =  1;
	$fields['vendor_currency'] = '47';
	$fields['vendor_accepted_currencies'] = '52,26,47,144';
	$fields['vendor_terms_of_service'] =  '<h5>This is a demo store. Your orders will not proceed. You have not configured any terms of service yet. Click <a href="'.JURI::base(true).'/index.php?option=com_virtuemart&view=user&task=editshop">here</a> to change this text.</h5>';
	$fields['vendor_url'] = JURI::root();
	$fields['vendor_name'] =  'Sample Company';
	$fields['vendor_legal_info']="VAT-ID: XYZ-DEMO<br />Reg.Nr: DEMONUMBER";
	$fields['vendor_letter_css']='.vmdoc-header { }
.vmdoc-footer { }
';
	$fields['vendor_letter_header_html']='<h1>{vm:vendorname}</h1><p>{vm:vendoraddress}</p>';
	$fields['vendor_letter_header_image']='1';
	$fields['vendor_letter_footer_html']='{vm:vendorlegalinfo}<br /> Page {vm:pagenum}/{vm:pagecount}';

	$usermodel = VmModel::getModel('user');
	$usermodel->setId($userId);

	//Save the VM user stuff
	if(!$usermodel->store($fields)){
		vmError(vmText::_('COM_VIRTUEMART_NOT_ABLE_TO_SAVE_USER_DATA')  );
	}

	$lang = VmConfig::$vmlang;

	$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_sample_data_'.$lang.'.sql';
	if (!file_exists($filename)) {
		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_sample_data.sql';
	}

	//copy sampel media
	$src = VMPATH_ROOT .'/administrator/components/com_virtuemart/assets/images/vmsampleimages';
	// 			if(version_compare(JVERSION,'1.6.0','ge')) {
	$dst = VMPATH_ROOT .'/images/virtuemart';

	$this->recurse_copy($src,$dst);

	if(!$this->execSQLFile($filename)){
		vmError(vmText::_('Problems execution of SQL File '.$filename));
	} else {

		$comdata['virtuemart_vendor_id'] = 1;
		$comdata['ordering'] = 1;
		$comdata['shared'] = 0;
		$comdata['published'] = 1;

		//update jplugin_id from shipment and payment
		$db = JFactory::getDBO();
		$q = 'SELECT `extension_id` FROM #__extensions WHERE element = "weight_countries" AND folder = "vmshipment"';
		$db->setQuery($q);
		$shipment_plg_id = $db->loadResult();

		if(!empty($shipment_plg_id)){

			$shipdata =& $comdata;
			$shipdata['shipment_jplugin_id'] = $shipment_plg_id;
			$shipdata['shipment_element'] = "weight_countries";
			$shipdata['shipment_params'] = 'shipment_logos=""|countries=""|zip_start=""|zip_stop=""|weight_start=""|weight_stop=""|weight_unit="KG"|nbproducts_start=0|nbproducts_stop=0|orderamount_start=""|orderamount_stop=""|cost="0"|package_fee="2.49"|tax_id="0"|free_shipment="500"';
			$shipdata['shipment_name'] = "Self pick-up";

			$table = $this->getTable('shipmentmethods');
			$table->bindChecknStore($shipdata);

			//Create table of the plugin
			$url = '/plugins/vmshipment/weight_countries';

			if (!class_exists ('plgVmShipmentWeight_countries')) require(VMPATH_ROOT .'/'. $url .'/weight_countries.php');
			vDispatcher::directTrigger('vmshipment', 'weight_countries', 'plgVmOnStoreInstallShipmentPluginTable', array($shipment_plg_id));
		}

		$q = 'SELECT `extension_id` FROM #__extensions WHERE element = "standard" AND folder = "vmpayment"';
		$db->setQuery($q);
		$payment_plg_id = $db->loadResult();
		if(!empty($payment_plg_id)){

			$pdata =& $comdata;
			$pdata['payment_jplugin_id'] = $payment_plg_id;
			$pdata['payment_element'] = "standard";
			$pdata['payment_params'] = 'payment_logos=""|countries=""|payment_currency="0"|status_pending="U"|send_invoice_on_order_null="1"|min_amount=""|max_amount=""|cost_per_transaction="0.10"|cost_percent_total="1.5"|tax_id="0"|payment_info=""';
			$pdata['payment_name'] = "Cash on delivery";

			$table = $this->getTable('paymentmethods');
			$table->bindChecknStore($pdata);

			$url = '/plugins/vmpayment/standard';

			if (!class_exists ('plgVmPaymentStandard')) require(VMPATH_ROOT .'/'. $url .'/standard.php');
			vDispatcher::directTrigger('vmpayment', 'standard', 'plgVmOnStoreInstallPaymentPluginTable', array($payment_plg_id));
		}
		VirtueMartModelCategory::updateCategories();
		vmInfo(vmText::_('COM_VIRTUEMART_SAMPLE_DATA_INSTALLED'));
	}

	return true;

    }

	/**
	 * copy all $src to $dst folder and remove it
	 *
	 * @author Max Milbers
	 * @param String $src path
	 * @param String $dst path
	 * @param String $type modules, plugins, languageBE, languageFE
	 */
	static public function recurse_copy($src,$dst,$delete = true ) {

		$dir = '';
		if(JFolder::exists($src)){
			$dir = opendir($src);
			JFolder::create($dst);

			if(is_resource($dir)){
				while(false !== ( $file = readdir($dir)) ) {
					if (( $file != '.' ) && ( $file != '..' )) {
						if ( is_dir($src .DS. $file) ) {
							if(!JFolder::create($dst . DS . $file)){
								$app = JFactory::getApplication ();
								$app->enqueueMessage ('Couldnt create folder ' . $dst . DS . $file);
							}
							self::recurse_copy($src .DS. $file,$dst .DS. $file);
						}
						else {
							if($delete and JFile::exists($dst .DS. $file)){
								if(!JFile::delete($dst .DS. $file)){
									$app = JFactory::getApplication();
									$app -> enqueueMessage('Couldnt delete '.$dst .DS. $file);
								}
							}
							if(!JFile::copy($src .DS. $file,$dst .DS. $file)){
								$app = JFactory::getApplication();
								$app -> enqueueMessage('Couldnt move '.$src .DS. $file.' to '.$dst .DS. $file);
							}
						}
					}
				}
				closedir($dir);
				//if (is_dir($src)) JFolder::delete($src);
				return true;
			}
		}

		$app = JFactory::getApplication();
		$app -> enqueueMessage('Couldnt read dir '.$dir.' source '.$src);

	}



    function restoreSystemDefaults() {

		JPluginHelper::importPlugin('vmextended');

	    vDispatcher::trigger('onVmSqlRemove', $this);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/uninstall_essential_data.sql';
		$this->execSQLFile($filename);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/uninstall_required_data.sql';
		$this->execSQLFile($filename);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install.sql';
		$this->execSQLFile($filename);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_essential_data.sql';
		$this->execSQLFile($filename);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_required_data.sql';
		$this->execSQLFile($filename);

	    $filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_country_data.sql';
		$this->execSQLFile($filename);

		$updater = new GenericTableUpdater();
		$updater->createLanguageTables();

		vDispatcher::trigger('onVmSqlRestore', $this);
	}

	function restoreSystemTablesCompletly() {

		$this->removeAllVMTables();

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install.sql';
		$this->execSQLFile($filename);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_essential_data.sql';
		$this->execSQLFile($filename);

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_required_data.sql';
		$this->execSQLFile($filename);

	    $filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_country_data.sql';
		$this->execSQLFile($filename);

		$updater = new GenericTableUpdater();
		$updater->createLanguageTables();

		JPluginHelper::importPlugin('vmextended');
		vDispatcher::trigger('onVmSqlRestore', array($this));
	}

    /**
     * Parse a sql file executing each sql statement found.
     *
     * @author Max Milbers
     */
    function execSQLFile($sqlfile ) {

		// Check that sql files exists before reading. Otherwise raise error for rollback
		if ( !file_exists($sqlfile) ) {
			vmError('execSQLFile, SQL file not found! '.$sqlfile);
			return false;
		}

		if(!class_exists('VmConfig')){
			require_once(VMPATH_ADMIN .'/helpers/config.php');
			VmConfig::loadConfig(false,true);
		}

		if(!VmConfig::$vmlang){
			$params = JComponentHelper::getParams('com_languages');
			$lang = $params->get('site', 'en-GB');//use default joomla
			$lang = strtolower(strtr($lang,'-','_'));
		} else {
			$lang = VmConfig::$vmlang;
		}

		// Create an array of queries from the sql file
		jimport('joomla.installer.helper');
		$db = JFactory::getDBO();
		$queries = $db->splitSql(file_get_contents($sqlfile));

		if (count($queries) == 0) {
		    vmError('SQL file has no queries!');
		    return false;
		}
		$ok = true;

		// Process each query in the $queries array (split out of sql file).
		foreach ($queries as $k=>$query) {
			if(empty($query)){
				vmWarn('execSQLFile Query was empty in file '.$sqlfile);
				continue;
			}
		    $query = trim($query);
			$queryLines = explode("\n",$query);
			//vmdebug('test',$queryLines);
			foreach($queryLines as $n=>$line){
				if(empty($line)){
					unset($queryLines[$n]);
				} else {
					if(strpos($line, 'CREATE' )!==false or strpos( $line, 'INSERT')!==false){
						$queryLines[$n] = str_replace('XLANG',$lang,$line);
					}
				}
			}
			$query = implode("\n",$queryLines);

			if(!empty($query)){
				try {
					$db->setQuery($query);
					$db->execute();
				}
				catch(Exception $e) {
					vmWarn( 'JInstaller::install: '.$sqlfile.' '.$e->getMessage().': '.vmText::_('COM_VIRTUEMART_SQL_ERROR').' Query: '.$query);
					$ok = false;
				}
		    }
		}

		return $ok;
	}

    /**
     * Delete all Virtuemart tables.
     *
     * @return True if successful, false otherwise
     */
    function removeAllVMTables() {
		$db = JFactory::getDBO();
		$config = JFactory::getConfig();

		$prefix = $config->get('dbprefix').'virtuemart_%';
		$db->setQuery('SHOW TABLES LIKE "'.$prefix.'"');
		if (!$tables = $db->loadColumn()) {
			vmInfo ('removeAllVMTables no tables found ');
		    return false;
		}

		$app = JFactory::getApplication();
		foreach ($tables as $table) {

		    $db->setQuery('DROP TABLE ' . $table);
		    if($db->execute()){
		    	$droppedTables[] = substr($table,strlen($prefix)-1);
		    } else {
		    	$errorTables[] = $table;
		    	$app->enqueueMessage('Error drop virtuemart table ' . $table);
		    }
		}


		if(!empty($droppedTables)){
			$app->enqueueMessage('Dropped virtuemart table ' . implode(', ',$droppedTables));
		}

	    if(!empty($errorTables)){
			$app->enqueueMessage('Error dropping virtuemart table ' . implode($errorTables,', '));
			return false;
		}

		//Delete VM menues
		$q = 'DELETE FROM #__menu WHERE `link` = "%option=com_virtuemart%" ';
		$db->setQuery($q);
		$db->execute();

		return true;
    }


    /**
     * Remove all the data from all Virutmeart tables.
     *
     * @return boolean True if successful, false otherwise.
     */
    function removeAllVMData() {
		JPluginHelper::importPlugin('vmextended');
	    vDispatcher::trigger('onVmSqlRemove', array($this));

		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/uninstall_data.sql';
		$this->execSQLFile($filename);
		$tables = array('categories','manufacturers','manufacturercategories','paymentmethods','products','shipmentmethods','vendors');
		$db = JFactory::getDBO();
		$prefix = $db->getPrefix();
		foreach ($tables as $table) {
			$query = 'SHOW TABLES LIKE "'.$prefix.'virtuemart_'.$table.'_%"';
			$db->setQuery($query);
			if($translatedTables= $db->loadColumn()) {
				foreach ($translatedTables as $translatedTable) {
					$db->setQuery('TRUNCATE TABLE `'.$translatedTable.'`');
					if($db->execute()) vmInfo( $translatedTable.' empty');
					else vmError($translatedTable.' language table Cannot be deleted');
				}
			} else vmInfo('No '.$table.' language table found to delete '.$query);
		}
		//"TRUNCATE TABLE IS FASTER and reset the primary Keys;

		//install required data again
		$filename = VMPATH_ROOT .'/administrator/components/com_virtuemart/install/install_required_data.sql';
		$this->execSQLFile($filename);

		return true;
    }

	/**
	 * @param $type= 'plugin'
	 * @param $element= 'textinput'
	 * @param $src = path .'/plugins/'. $group .'/'. $element;
	 *
	 */
	public function updateJoomlaUpdateServer( $type, $element, $dst, $group=''  ){

		$db = JFactory::getDBO();
		$extensionXmlFileName = self::getExtensionXmlFileName($type, $element, $dst );
		if(JFile::exists($extensionXmlFileName)){
			$xml=simplexml_load_file($extensionXmlFileName);
		} else {
			vmdebug('updateJoomlaUpdateServer xml file not found ',$type, $element, $dst, $group);
			return false;
		}

		// get extension id
		$query="SELECT `extension_id` FROM `#__extensions` WHERE `type`=".$db->quote($type)." AND `element`=".$db->quote($element);
		if ($group) {
			$query.=" AND `folder`=".$db->quote($group);
		}

		$db->setQuery($query);
		$extension_id=$db->loadResult();
		if(!$extension_id) {
			vmdebug('updateJoomlaUpdateServer no extension id ',$query);
			return;
		}
		// Is the extension already in the update table ?
		$query="SELECT * FROM `#__update_sites_extensions` WHERE `extension_id`=".$extension_id;
		$db->setQuery($query);
		$update_sites_extensions=$db->loadObject();
		//vmEcho::$echoDebug=true;


		// Update the version number for all
		if(isset($xml->version)) {
			$query="UPDATE `#__updates` SET `version`=".$db->quote((string)$xml->version)."
					         WHERE `extension_id`=".$extension_id;
			$db->setQuery($query);
			$db->execute();
		}


		if(isset($xml->updateservers->server)) {
			if (!$update_sites_extensions) {

				$query="INSERT INTO `#__update_sites` SET `name`=".$db->quote((string)$xml->updateservers->server['name']).",
				        `type`=".$db->quote((string)$xml->updateservers->server['type']).",
				        `location`=".$db->quote((string)$xml->updateservers->server).", enabled=1 ";
				$db->setQuery($query);
				$db->execute();

				$update_site_id=$db->insertId();

				$query="INSERT INTO `#__update_sites_extensions` SET `update_site_id`=".$update_site_id." , `extension_id`=".$extension_id;
				$db->setQuery($query);
				$db->execute();
			} else {
				if(empty($update_sites_extensions->update_site_id)){
					vmWarn('Update site id not found for '.$element);
					vmdebug('Update site id not found for '.$element,$update_sites_extensions);
					return false;
				}
				$query="SELECT * FROM `#__update_sites` WHERE `update_site_id`=".$update_sites_extensions->update_site_id;
				$db->setQuery($query);
				$update_sites= $db->loadAssocList();
				//vmdebug('updateJoomlaUpdateServer',$update_sites);
				if(empty($update_sites)){
					vmdebug('No update sites found, they should be inserted');
					return false;
				}
				//Todo this is written with an array, but actually it is only tested to run with one server
				foreach($update_sites as $upSite){
					if (strcmp($upSite['location'], (string)$xml->updateservers->server) != 0) {
						// the extension was already there: we just update the server if different
						$query="UPDATE `#__update_sites` SET `location`=".trim($db->quote((string)$xml->updateservers->server))."
					         WHERE update_site_id=".(int)$update_sites_extensions->update_site_id;
						vmdebug('updateJoomlaUpdateServer update $update_sites', $query);
						$db->setQuery($query);
						$db->execute();
					}
				}

			}

		} else {
			echo ('<br />UPDATE SERVER NOT FOUND IN XML FILE:'.$extensionXmlFileName);
		}
	}

	/**
	 * @param $type= 'plugin'
	 * @param $element= 'textinput'
	 * @param $src = path .'/plugins/'. $group .'/'. $element;
	 */
	static function getExtensionXmlFileName($type, $element, $dst ){
		if ($type=='plugin') {
			$extensionXmlFileName=  $dst .'/'. $element.  '.xml';
		} else if ($type=='module'){
			$extensionXmlFileName = $dst .'/'. $element .'/'. $element. '.xml';
		} else {
			$extensionXmlFileName = $dst;
		}
		return $extensionXmlFileName;
	}

	public function setSafePathCreateFolders($token = ''){

		if(empty($token)){
			$safePath = shopFunctions::getSuggestedSafePath();
		} else {
			$safePath = VMPATH_ADMIN.'/'.$token.'/';
		}
		$safePath = str_replace('/',DS,$safePath);

		$invoice = $safePath.ShopFunctionsF::getInvoiceFolderName();

		//$invoice = shopFunctions::getInvoicePath($safePath);
		$encryptSafePath = $safePath. vmCrypt::ENCRYPT_SAFEPATH;

		JFolder::create($safePath,0755);vmdebug('setSafePathCreateFolders $safePath',$safePath);
		JFolder::create($invoice,0755);vmdebug('setSafePathCreateFolders $invoice',$invoice);
		JFolder::create($encryptSafePath,0755);vmdebug('setSafePathCreateFolders $encryptSafePath',$encryptSafePath);

		$config = VmConfig::loadConfig();
		//vmdebug('setSafePathCreateFolders set forSale_path ',$safePath,$config);
		$config->set('forSale_path', $safePath);

		$data['virtuemart_config_id'] = 1;
		$data['config'] = $config->toString();

		$confTable = $this->getTable('configs');
		//vmdebug('setSafePathCreateFolders set forSale_path ',$safePath,$data['config']);
		$confTable->bindChecknStore($data);

		VmConfig::loadConfig(true);

		return true;
	}

	/**
	 * This function deletes all stored thumbs and deletes the entries for all thumbs, usually this is need for shops
	 * older than vm2.0.22. The new pattern is now not storing the url as long it is not overwritten.
	 * Of course the function deletes all overwrites, but you can now relativly easy change the thumbsize in your shop
	 * @author Max Milbers
	 */
	function resetThumbs(){

		$db = JFactory::getDbo();
		$q = 'UPDATE `#__virtuemart_medias` SET `file_url_thumb`=""';

		$db->setQuery($q);
		try{
			$db->execute();
		} catch (Exception $e){
			vmError('resetThumbs Update entries failed '.$q);
		}

		jimport('joomla.filesystem.folder');
		$tmpimg_resize_enable = VmConfig::get('img_resize_enable',1);

		VmConfig::set('img_resize_enable',0);
		$this->deleteMediaThumbFolder('media_category_path');
		$this->deleteMediaThumbFolder('media_product_path');
		$this->deleteMediaThumbFolder('media_manufacturer_path');
		$this->deleteMediaThumbFolder('media_vendor_path');
		$this->deleteMediaThumbFolder('forSale_path_thumb','');

		VmConfig::set('img_resize_enable',$tmpimg_resize_enable);
		return true;

	}

	/**
	 * Delets a thumb folder and recreates it, contains small nasty hack for the thumbnail folder of the "file for sale"
	 * @author Max Milbers
	 * @param $type
	 * @param string $resized
	 * @return bool
	 */
	private function deleteMediaThumbFolder($type,$resized='resized'){

		if(!empty($resized)) $resized = DS.$resized;
		$typePath = VmConfig::get($type);
		if(!empty($typePath)){

			$path = VMPATH_ROOT.DS.str_replace('/',DS,$typePath).$resized;
			$msg = JFolder::delete($path);
			if(!$msg){
				vmWarn('Problem deleting '.$type);
			}
			$msg = JFolder::create($path);
			return $msg;
		} else {

			return 'Config path for '.$type.' empty';
		}

	}

	public function reset_Has_x_Fields(){

		$db = JFactory::getDbo();

		$q = 'UPDATE #__virtuemart_calcs SET `has_categories`=NULL,`has_shoppergroups`=NULL,`has_countries`=NULL,`has_manufacturers`=NULL, `has_states`=NULL';
		$db->setQuery($q);
		$db->execute();

		$q = 'UPDATE #__virtuemart_categories SET `has_children`=NULL,`has_medias`=NULL, `category_parent_id`=NULL, `ordering`=NULL';
		$db->setQuery($q);
		$db->execute();

		$q = 'UPDATE #__virtuemart_products SET `has_categories`=NULL,`has_shoppergroups`=NULL,`has_medias`=NULL,`has_manufacturers`=NULL, `has_prices`=NULL';
		$db->setQuery($q);
		$db->execute();

		VirtueMartModelCategory::updateCategories();
	}


	public function readCountrySql($path){
		//Open country data file
		$file = $path .'/administrator/components/com_virtuemart/install/install_country_data.sql';
		if(!file_exists($file)){
			vmError('updateCountryTableISONumbers Could not find file '.$file);
			return false;
		} else {
			//vmdebug('Loaded country sql file '.$file);
		}

		$countries = array();
		$handle = fopen($file, 'rb');
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$line = trim($line);
				$pos = strpos($line,'(');

				if( $pos !== FALSE){

					if($pos<2){
						$line = str_replace(array('"',';'), '', $line);
						$line = trim ($line,',');
						$line = trim ($line,'(');
						$line = trim ($line,')');
						$vars = explode(',',$line);

						$obj = new stdClass();

						if(count($vars)>6){
							$obj->published = trim(array_pop($vars ));
							$obj->country_num_code = trim(array_pop($vars ));
							$obj->country_2_code = trim(array_pop($vars ));
							$obj->country_3_code = trim(array_pop($vars ));
							$obj->virtuemart_country_id = trim(array_shift($vars ));
							$obj->country_name = trim(implode(',', $vars));
							//vmdebug('Vars not 4 My pos '.$pos.' $line ',$line,$vars,$obj);
							//continue;
						} else {
							//We override always according to
							$obj->virtuemart_country_id = trim($vars[0]);
							$obj->country_name = trim($vars[1]);
							$obj->country_3_code = trim($vars[2]);
							$obj->country_2_code = trim($vars[3]);
							$obj->country_num_code = trim($vars[4]);
							$obj->published = trim($vars[5]);
						}
						$countries[] = $obj;
					}
				}
			}

			fclose($handle);
		} else {
			vmError('updateCountryTableISONumbers Could not open file '.$file);
		}
		//vmdebug('My countries from SQL file '.$file, $countries);
		//return false;
		return $countries;
	}

	/**
	 * We just delete any non Vm Entry and just keep the old ordering
	 */
	public function resetCountryTableISONumbers(){


		$countries = $this->readCountrySql(VMPATH_ROOT);
		//vmdebug('resetCountryTableISONumbers',$countries);
		$db = JFactory::getDbo();
		$q = 'DELETE FROM #__virtuemart_countries WHERE (virtuemart_country_id>249 and virtuemart_country_id<350) or virtuemart_country_id>354';
		$db->setQuery($q);
		$db->execute();

		$this->addDropKeys( $db, 'country_2_code');
		$this->addDropKeys( $db, 'country_3_code');
		$this->addDropKeys( $db, 'country_num_code');

		$this->countryM = VmModel::getModel('country');
		foreach($countries as $country){
			$loaded = $this->countryM->getCountry($country->virtuemart_country_id);
			//vmdebug('resetCountryTableISONumbers '.$country->virtuemart_country_id,$loaded->loadFieldValues());
			if($loaded) {

				$loaded->country_name = $country->country_name;
				$loaded->country_2_code = $country->country_2_code;
				$loaded->country_3_code = $country->country_3_code;
				$loaded->country_num_code = $country->country_num_code;
				//if(empty($country->published)) $loaded->published = $country->published;
				$loaded->store();
			}
		}
		$this->addDropKeys( $db, 'country_2_code', false);
		$this->addDropKeys( $db, 'country_3_code', false);
		$this->addDropKeys( $db, 'country_num_code', false);

		$db->setQuery($q);
		$db->execute();
		return ;
	}

	private function addDropKeys($db, $key, $drop = true, $unique = true){

		$q = 'ALTER TABLE `#__virtuemart_countries` ';
		if($drop){
			$q .= 'DROP INDEX `'.$key.'` ;';
		} else {
			$uniqueStr = 'INDEX';
			if($unique) $uniqueStr = 'UNIQUE KEY';
			$q .= 'ADD '.$uniqueStr.' `'.$key.'` (`'.$key.'`) ;';
		}
		$db->setQuery($q);
		//vmdebug('Executing '.$q);
		try{
			$db->execute();
		} catch (Exception $e){
			vmdebug('key, exception ',$e->getMessage());
		}
	}

	/** Updates countries to iso-3166-country-codes
	 * @return false
	 */
	public function updateCountryTableISONumbers($safe = true, $path = VMPATH_ROOT){

		$this->safe = $safe;
		$this->countryM = VmModel::getModel('country');

		$countries = $this->readCountrySql($path);

		$db = JFactory::getDbo();

		if(!$safe){
			$this->addDropKeys( $db, 'country_2_code');
			$this->addDropKeys( $db, 'country_3_code');
			$this->addDropKeys( $db, 'country_num_code');
		}
		//vmdebug('My countries from SQL', $countries);
		//return false;

		//In Safemode, we just unpublish any non iso country

		$country = new stdClass();
		$country->country_name = "%-1";     //remove accidently doubled countries
		$this->removeNotUniqueCountries($db, $country, 'country_name', 'LIKE');

		$iso = array();
		foreach($countries as $country){

			$this->cleanDoubledEntries($db, $country);

			$loaded = $this->countryM->getCountry($country->virtuemart_country_id);
			if($loaded /*and (!$this->safe or empty($loaded->country_num_code) )*/ ) {

				$overwritten = false;
				//We override always according to
				if($loaded->country_2_code != $country->country_2_code){
					if(!empty($loaded->country_2_code)) vmInfo('Overwriting '.$country->virtuemart_country_id.' '.$loaded->country_name.' country_2_code '.$loaded->country_2_code.' to '.$country->country_2_code);
					$loaded->country_2_code = $country->country_2_code;
					$overwritten = true;
				}

				if($loaded->country_3_code != $country->country_3_code){
					if(!empty($loaded->country_3_code)) vmInfo('Overwriting '.$country->virtuemart_country_id.' '.$loaded->country_name.' country_3_code '.$loaded->country_3_code.' to '.$country->country_3_code);
					$loaded->country_3_code = $country->country_3_code;
					$overwritten = true;
				}

				if($loaded->country_name != $country->country_name){
					if(!empty($loaded->country_name)) vmInfo('Overwriting country_name '.$country->virtuemart_country_id.' '.$loaded->country_name.' to '.$country->country_name);
					$loaded->country_name = $country->country_name;
					$overwritten = true;
				}

				if($loaded->country_num_code != $country->country_num_code){
					if(!empty($loaded->country_num_code)) vmInfo('Overwriting '.$country->virtuemart_country_id.' '.$loaded->country_name.' country num code '.$loaded->country_num_code.' to '.$country->country_num_code);
					$loaded->country_num_code = $country->country_num_code;
					$overwritten = true;
				}

				//Always unpublish outdated countries, but do not publish already unpublished
				/*if(empty($country->published) and !empty($loaded->published)){
					vmInfo('Overwriting '.$country->virtuemart_country_id.' '.$loaded->country_name.' country published '.(int)$loaded->published.' to '.(int)$country->published);
					$loaded->published = $country->published;
					$overwritten = true;
				}*/

				if( $overwritten ) {
					vmdebug('Overwriting ',$loaded->loadFieldValues(), $country);
					$this->cleanDoubledEntries($db, $loaded);
					$loaded->store();
					$iso[] = $loaded->virtuemart_country_id;
				}

			}
		}

		if(!$safe){
			$this->addDropKeys( $db, 'country_2_code', false);
			$this->addDropKeys( $db, 'country_3_code', false);
			$this->addDropKeys( $db, 'country_num_code', false);
		}

		if(!empty($iso)){
			$q = 'SELECT * FROM #__virtuemart_countries WHERE virtuemart_country_id IS NOT NULL AND virtuemart_country_id NOT IN('.implode(',',$iso).')';
			$db->setQuery($q);
			$nonIsoCountries = $db->loadObjectList();

			if(!empty($nonIsoCountries)) {
				$strA = array();
				foreach ($nonIsoCountries as $nonIsoCountry) {
					$strA[] = $nonIsoCountry->country_name;
				}
				vmInfo('Not updated Countries (either up to date or not ISO-3166: ' . implode(', ', $strA) . "\n" . $q);
			} else {
				vmInfo( 'All countries are up to date');
			}
		} else {
			vmInfo( 'All countries are up to date');
		}


	}

	private function cleanDoubledEntries($db, &$country, $name = false){
		$countryIdByCode2 = $this->removeNotUniqueCountries($db, $country, 'country_2_code');
		$countryIdByCode3 = $this->removeNotUniqueCountries($db, $country, 'country_3_code');
		$countryIdByCodeNum = $this->removeNotUniqueCountries($db, $country, 'country_num_code');
		if($name) $countryIdByCodeName = $this->removeNotUniqueCountries($db, $country, 'country_name');

		if($country->virtuemart_country_id != $countryIdByCode2 /*and
				($countryIdByCode2 == $countryIdByCode3*/ and ( empty($countryIdByCodeNum) or $countryIdByCodeNum==$countryIdByCode2 or $countryIdByCodeNum==$countryIdByCode2)  ){

			if(empty($countryIdByCodeNum)){
				if(!empty($countryIdByCode2) and $countryIdByCode2 == $countryIdByCode3){
					$country->virtuemart_country_id = $countryIdByCode2;
					vmInfo('$countryIdByCodeNum is empty. Use already existing entry for '.$country->country_name.', override countryId = '.$country->virtuemart_country_id.' with '.$countryIdByCode2);
				}

			} else if(!empty($countryIdByCodeNum) and $countryIdByCodeNum==$countryIdByCode2){
				vmInfo('Use already existing entry for '.$country->country_name.', override countryId = '.$country->virtuemart_country_id.' with '.$countryIdByCode2);
				$country->virtuemart_country_id = $countryIdByCode2;
			} else if(!empty($countryIdByCodeNum) and $countryIdByCodeNum==$countryIdByCode3){
				vmInfo('Use already existing entry for '.$country->country_name.', override countryId = '.$country->virtuemart_country_id.' with '.$countryIdByCode3);
				$country->virtuemart_country_id = $countryIdByCode3;
			}

		}
	}

	private function removeNotUniqueCountries($db, $country, $field, $equals = '='){

		$q = 'SELECT * FROM `#__virtuemart_countries` WHERE `'.$field.'` '.$equals.' "'. $country->{$field} .'" LIMIT 0,1000';
		$db->setQuery($q);
		$dbCountries = $db->loadObjectList();
		//vmdebug('updateCountryTableISONumbers removing doubled '.$field.' country '.$q,$dbCountries);
		if(count($dbCountries)>0){
			//vmdebug('updateCountryTableISONumbers removing doubled '.$field.' country ',$dbCountries);
			foreach($dbCountries as $i => $dbCountry){
				if($i==0 and $equals != 'LIKE') continue;

				if($this->checkDeleteCountryEntry($db, $dbCountry)){
					//vmdebug('updateCountryTableISONumbers removed doubled '.$field.' country ',$dbCountry);
				}

			}
		} else {
			//vmdebug('There is just one entry for '.$field.' = '.$country->{$field});
		}

		if(isset($dbCountries[0])){
			return $dbCountries[0]->virtuemart_country_id;
		} else {
			return false;
		}

	}

	static $countryTables = array('#__virtuemart_calc_countries', '#__virtuemart_states', '#__virtuemart_userinfos', '#__virtuemart_order_userinfos');
	private function checkDeleteCountryEntry($db, $dbCountry){

		if(!empty($dbCountry->country_3_code) and strlen($dbCountry->country_3_code)==3){
			$country = $this->countryM->getCountry($dbCountry->country_3_code, 'country_3_code');
		} else if(!empty($dbCountry->country_2_code) and strlen($dbCountry->country_2_code)==2){
			$country = $this->countryM->getCountry($dbCountry->country_2_code, 'country_2_code');
		} else if(!empty($dbCountry->country_num_code) and strlen($dbCountry->country_num_code)==3){
			$country = $this->countryM->getCountry($dbCountry->country_num_code, 'country_num_code');
		} else {
			$country = $dbCountry;
		}

		if(!empty($country->virtuemart_country_id)){
			$country_3_code = $country->country_3_code;
			$q = 'SELECT virtuemart_calc_id FROM #__virtuemart_calc_countries WHERE virtuemart_country_id = '. $country->virtuemart_country_id;
			$db->setQuery($q);
			$res = $db->loadColumn();
			if($res){
				foreach($res as $re){
					vmInfo('Cannot delete country with country_3_code = '.$country_3_code.', because the country is used by calculation rule with id '.$re);
				}
				$this->unpublishCountry($country->virtuemart_country_id);
				return false;
			}

			$q = 'SELECT virtuemart_state_id FROM #__virtuemart_states WHERE virtuemart_country_id = '. $country->virtuemart_country_id;
			$db->setQuery($q);
			$res = $db->loadColumn();
			if($res){
				foreach($res as $re){
					vmInfo('Cannot delete country with country_3_code = '.$country_3_code.', because the country is used by state with id '.$re);
				}
				$this->unpublishCountry($country->virtuemart_country_id);
				return false;
			}

			$q = 'SELECT virtuemart_user_id FROM #__virtuemart_userinfos WHERE virtuemart_country_id = '. $country->virtuemart_country_id;
			$db->setQuery($q);
			$res = $db->loadColumn();
			if($res){
				foreach($res as $re){
					vmInfo('Cannot delete country with country_3_code = '.$country_3_code.', because the country is used by user in #__virtuemart_userinfos with id '.$re);
				}
				$this->unpublishCountry($country->virtuemart_country_id);
				return false;
			}

			$q = 'SELECT virtuemart_user_id FROM #__virtuemart_order_userinfos WHERE virtuemart_country_id = '. $country->virtuemart_country_id;
			$db->setQuery($q);
			$res = $db->loadColumn();
			if($res){
				foreach($res as $re){
					vmInfo('Cannot delete country with country_3_code = '.$country_3_code.', because the country is used by user in #__virtuemart_order_userinfos with id '.$re);
				}
				$this->unpublishCountry($country->virtuemart_country_id);
				return false;
			}

			//This is on purpose not on top of the function, we want to see, where it got used
			if($this->safe){
				$this->unpublishCountry($country->virtuemart_country_id);
				vmInfo('Unpublished country '.$country->country_name.' '.$country->virtuemart_country_id);
			} else {
				$q = 'DELETE FROM `#__virtuemart_countries` WHERE `virtuemart_country_id` = "'. (int)$country->virtuemart_country_id .'"';
				$db->setQuery($q);
				$db->execute();
				vmInfo('Deleted country '.$country->country_name.' '.$country->virtuemart_country_id);
			}


			//to hard to check also the Shipment/Payment Plugin configuration. All deleted countries are used rarely, or replaced by the shopowner already
		}
		return true;
	}

	private function unpublishCountry($virtuemart_country_id){
		$table = $this->countryM->getTable();
		$table->load( $virtuemart_country_id );
		$table->toggle('published', 0);
	}


}

//pure php no tag
