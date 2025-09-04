<?php
/**
 * Echo helper class
 *
 * Handles different types of output
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2014-2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL 3, see COPYRIGHT.php
 */
defined('_JEXEC') or die('Restricted access');

/**
 * Where type can be one of
 * 'warning' - yellow
 * 'notice' - blue
 * 'error' - red
 * 'message' (or empty) - green
 * This function shows an info message, the messages gets translated with vmText::,
 * you can overload the function, so that automatically sprintf is taken, when needed.
 * So this works vmInfo('COM_VIRTUEMART_MEDIA_NO_PATH_TYPE',$type,$link )
 * and also vmInfo('COM_VIRTUEMART_MEDIA_NO_PATH_TYPE');
 *
 * @author Max Milbers
 * @param string $publicdescr
 * @param string $value
 */

class vmEcho {
	
	public static $maxMessageCount = 0;
	public static $maxMessage = 400;
	public static $_debug = 0;
	public static $echoAdmin = 0;
	public static $echoDebug = 0;
	public static $logDebug = 0;
	public static $logFileName = 'com_virtuemart';
	const LOGFILEEXT = '.log.php';
	public static $mType = 'notice';
	public static $_starttime = array();

	static public function setOpts($options = array()){

		if(isset($options['_debug'])){
			vmEcho::$_debug = (int)($options['_debug']);
		}
		if(isset($options['echoAdmin'])){
			vmEcho::$echoAdmin = (int)($options['echoAdmin']);
		}
	}

	static public function varPrintR($args){

		$debugdescr = '';
		if(!is_array($args)){
			$args = array($args);
		}
		for($i=0;$i<count($args);$i++){
			if(isset($args[$i])){
				$methods = '';
				$reset = false;
				if(is_object($args[$i])){
					//We always try to unset the db
					//unset($args[$i]->_db);
					$methdArr = get_class_methods($args[$i]);
					if(in_array('unsetForDebug', $methdArr) ){
						//echo 'call unsest for debug';
						$args[$i]->unsetForDebug();
						$reset = true;
					}

					if(!empty($methdArr) and is_array($methdArr) and count($methdArr)>0){
						$methods = print_r($methdArr,1);
						$methods = '<br />'.$methods;
					}

				}

				$debugdescr .= ' Var'.$i.': <pre>'.print_r($args[$i],1).$methods.'</pre>'."\n";

				if($reset and in_array('resetForDebug', $methdArr) ){
					$args[$i]->resetForDebug();
				}
			}
		}
		return $debugdescr;
	}
}

function vmInfo($publicdescr,$value=NULL){

	$app = JFactory::getApplication();

	$msg = '';
	$type = vmEcho::$mType;//'info';

	if(vmEcho::$maxMessageCount<vmEcho::$maxMessage){
		$lang = vmLanguage::getLanguage();
		if($value!==NULL){

			$args = func_get_args();
			if (count($args) > 0) {
				$args[0] = $lang->_($args[0]);
				$msg = call_user_func_array('sprintf', $args);
			}
		}	else {
			$msg = vmText::_($publicdescr);
		}
	}
	else {
		if (vmEcho::$maxMessageCount == vmEcho::$maxMessage) {
			$msg .= ' Max messages reached';
			$type = 'warning';
			vmEcho::$maxMessageCount++;
		} else {
			return false;
		}
	}

	if(!empty($msg)){
		vmEcho::$maxMessageCount++;
		if(vmEcho::$_debug ){
			vmdebug('vmInfo: '.$msg);
		} else {
			$app ->enqueueMessage($msg,$type);
		}

	} else {
		vmTrace('vmInfo Message empty '.$msg);
	}

	return $msg;
}

/**
 * Informations for the vendors or the administrators of the store, but not for developers like vmdebug
 * @param      $publicdescr
 * @param null $value
 */
function vmAdminInfo($publicdescr,$value=NULL){

	if(vmEcho::$echoAdmin){

		$app = JFactory::getApplication();

		if(vmEcho::$maxMessageCount<vmEcho::$maxMessage){
			$lang = vmLanguage::getLanguage();
			if($value!==NULL){

				$args = func_get_args();
				if (count($args) > 0) {
					$args[0] = $lang->_($args[0]);
					vmEcho::$maxMessageCount++;
					$app ->enqueueMessage(call_user_func_array('sprintf', $args),vmEcho::$mType);
				}
			}	else {
				vmEcho::$maxMessageCount++;
				$publicdescr = $lang->_($publicdescr);
				$app ->enqueueMessage('Info: '.vmText::_($publicdescr),vmEcho::$mType);
			}
		}
		else {
			if (vmEcho::$maxMessageCount == vmEcho::$maxMessage) {
				$app->enqueueMessage ('Max messages reached '.vmText::_($publicdescr), vmEcho::$mType);
				vmEcho::$maxMessageCount++;
			}else {
				return false;
			}
		}
	}

}

function vmWarn($publicdescr,$value=NULL){


	$app = JFactory::getApplication();
	$msg = '';
	if(vmEcho::$maxMessageCount<vmEcho::$maxMessage){
		$lang = vmLanguage::getLanguage();
		if($value!==NULL){

			$args = func_get_args();
			if (count($args) > 0) {
				$args[0] = $lang->_($args[0]);
				$msg = call_user_func_array('sprintf', $args);

			}
		}	else {
			$msg = $lang->_($publicdescr);
		}
	}
	else {
		if (vmEcho::$maxMessageCount == vmEcho::$maxMessage) {
			$msg = 'Max messages reached';
			vmEcho::$maxMessageCount++;
		} else {
			return false;
		}
	}

	if(!empty($msg)){
		vmEcho::$maxMessageCount++;
		$app ->enqueueMessage($msg,'warning');
		return $msg;
	} else {
		vmTrace('vmWarn Message empty');
		return false;
	}

}

/**
 * Shows an error message, sensible information should be only in the first one, the second one is for non BE users
 * @author Max Milbers
 */
function vmError($descr, $publicdescr = '', $trace = 4, $debugvalues=NULL){

	$msg = '';
	if(class_exists('vmLanguage')){
		$lang = vmLanguage::getLanguage();
		$descr = $lang->_($descr);
	}
	$adminmsg =  'vmError: '.$descr;
	if (empty($descr)) {
		vmTrace ('vmError message empty');
		return;
	}

	if(isset($debugvalues)) {
		$adminmsg .= vmEcho::varPrintR($debugvalues);
	}


	$body = '';
	if($trace){
		$body = " \n<br> ";
		ob_start();
		echo '<pre>';
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,$trace);
		echo '</pre>';
		$body = ob_get_contents();
		ob_end_clean();
	}

	logInfo($adminmsg.$body,'error');
	if(vmEcho::$maxMessageCount< (vmEcho::$maxMessage+5)){

		if(vmEcho::$echoAdmin){
			$msg = $adminmsg;
		} else {
			if(!empty($publicdescr)){
				$msg = $lang->_($publicdescr);
			}
		}
	}
	else {
		if (vmEcho::$maxMessageCount == (vmEcho::$maxMessage+5)) {
			$msg = 'Max messages reached';
			vmEcho::$maxMessageCount++;
		} else {
			return false;
		}
	}

	if(!empty($msg)){
		vmEcho::$maxMessageCount++;
		if(vmEcho::$echoDebug){
			vmEcho::$maxMessageCount++;
			echo $msg."\n";
		}else {
			vmEcho::$maxMessageCount++;
			$app = JFactory::getApplication();
			$app ->enqueueMessage($msg,'error');
		}
		return $msg;
	}

	return $msg;

}

/**
 * A debug dumper for VM, it is only shown to backend users.
 *
 * @author Max Milbers
 * @param String $descr
 * @param various $values
 */
function vmdebug($debugdescr,$debugvalues=NULL){

	if(vmEcho::$_debug){
		if(vmEcho::$maxMessageCount<vmEcho::$maxMessage){
			if($debugvalues!==NULL){
				$args = func_get_args();
				if (count($args) > 1) {
					array_shift($args);
					$debugdescr .= vmEcho::varPrintR($args);
				}
			}

			if(vmEcho::$echoDebug){
				vmEcho::$maxMessageCount++;
				echo $debugdescr;
			}

			if(vmEcho::$_debug){
				vmEcho::$maxMessageCount++;
				$app = JFactory::getApplication();
				$app ->enqueueMessage('<span class="vmdebug" >'.vmEcho::$maxMessageCount.' vmdebug '.$debugdescr.'</span>');
			}

		}
		else {
			if (vmEcho::$maxMessageCount == vmEcho::$maxMessage) {
				$app = JFactory::getApplication();
				$app->enqueueMessage ('Max messages reached', 'info');
				vmEcho::$maxMessageCount++;
			}
		}
	}

	if(vmEcho::$logDebug){
		if(!vmEcho::$_debug){
			if($debugvalues!==NULL){
				$args = func_get_args();
				if (count($args) > 1) {
					array_shift($args);
					$debugdescr .= vmEcho::varPrintR($args);
				}
			}
		}
		logInfo($debugdescr,'vmdebug');
	}

}

function vmTrace($notice,$force=FALSE, $args = 10){

	if($force || vmEcho::$_debug ){
		ob_start();
		echo '<pre>';
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,$args);

		echo '</pre>';
		$body = ob_get_contents();
		ob_end_clean();

		if(vmEcho::$logDebug){
			logInfo($body,$notice);
		}

		if(vmEcho::$_debug){
			if(vmEcho::$echoDebug){
				echo $notice.' <pre>'.$body.'</pre>';
			}
			$app = JFactory::getApplication();
			$app ->enqueueMessage('<span class="vmdebug" >'.vmEcho::$maxMessageCount.' vmTrace '.$notice.' '.$body.'</span>');
		}
	}

}

function vmRam($notice,$value=NULL){
	vmdebug($notice.' used Ram '.round(memory_get_usage(TRUE)/(1024*1024),2).'M ',$value);
}

function vmRamPeak($notice,$value=NULL){
	vmdebug($notice.' memory peak '.round(memory_get_peak_usage(TRUE)/(1024*1024),2).'M ',$value);
}

function vmStartTimer($n='cur'){
	vmEcho::$_starttime[$n]['t'] = microtime(TRUE);
}

function vmSetStartTime($n='cur', $t = 0){
	if($t === 0){
		vmEcho::$_starttime[$n]['t'] = microtime(TRUE);
	} else {
		vmEcho::$_starttime[$n]['t'] = $t;
	}
}

function vmTime( $descr, $name='cur', $sum = true, $output = true){
	static $dt = 0.0;
	if (empty($descr)) {
		$descr = $name;
	}
	//$starttime = vmEcho::$_starttime ;
	if(empty(vmEcho::$_starttime[$name]['t'])){
		vmdebug('vmTime: '.$descr.' starting '.microtime(TRUE));
		vmEcho::$_starttime[$name] = array();
		vmEcho::$_starttime[$name]['t'] = microtime(TRUE);
	}
	else {
		$t = microtime (TRUE);
		$dt = ( $t - vmEcho::$_starttime[$name]['t'] );
		if(!isset(vmEcho::$_starttime[$name]['Z'])){
			vmEcho::$_starttime[$name]['Z'] = $dt;
		} else {
			vmEcho::$_starttime[$name]['Z'] += $dt;
		}

		if($sum) $dt = vmEcho::$_starttime[$name]['Z'];

		if ($name == 'cur') {
			if($output) vmdebug ('vmTime: ' . $descr . ' time consumed ' . $dt);
			vmEcho::$_starttime[$name]['t'] = microtime (TRUE);
		}
		else {
			$tmp = 'vmTime: ' . $descr . ': ' . $dt;
			if($output) vmdebug ($tmp);
			//if($reset) vmEcho::$_starttime[$name]['t'] = $t;
		}
	}
	return $dt;
}

/**
 * logInfo
 * to help debugging Payment notification for example
 */
function logInfo ($text, $type = 'message') {

	static $file = null;
	//vmSetStartTime('logInfo');
	$head = false;

	if($file===null){


		$config = JFactory::getConfig();
		$log_path = $config->get('log_path', VMPATH_ROOT . "/log" );
		$file = $log_path . "/" . vmEcho::$logFileName . vmEcho::LOGFILEEXT;

		if (!is_dir($log_path)) {
			jimport('joomla.filesystem.folder');
			if (!JFolder::create($log_path)) {
				if (vmEcho::$echoAdmin){
					$msg = 'Could not create path ' . $log_path . ' to store log information. Check your folder ' . $log_path . ' permissions.';
					$app = JFactory::getApplication();
					$app->enqueueMessage($msg, 'error');
				}
				return;
			}
		}
		if (!is_writable($log_path)) {
			if (vmEcho::$echoAdmin){
				$msg = 'Path ' . $log_path . ' to store log information is not writable. Check your folder ' . $log_path . ' permissions.';
				$app = JFactory::getApplication();
				$app->enqueueMessage($msg, 'error');
			}
			return;
		}

		if (!JFile::exists($file)) {
			// blank line to prevent information disclose: https://bugs.php.net/bug.php?id=60677
			// from Joomla log file
			$head = "#\n";
			$head .= '#<?php die("Forbidden."); ?>'."\n";

		}
	}


	// Initialise variables.
	/*if(!class_exists('JClientHelper')) require(VMPATH_LIBS.'/joomla/client/helper.php');
	$FTPOptions = JClientHelper::getCredentials('ftp');
	if (!empty($FTPOptions['enabled'] == 0)){
		//For logging we do not support FTP. For loggin without file permissions using FTP, we need to load the file,..
		//append the text and replace the file. This cannot be fast per FTP and therefore we disable it.
	} else {*/

	$fp = fopen ($file, 'a');
	if ($fp) {
		if ($head) {
			fwrite ($fp,  $head);
		}

		fwrite ($fp, "\n" . JFactory::getDate()->format ('Y-m-d H:i:s'));
		fwrite ($fp,  " ".strtoupper($type) . ' ' . vRequest::vmSpecialChars($text));
		fclose ($fp);
	} else {
		if (vmEcho::$echoAdmin){
			$msg = 'Could not write in file  ' . $file . ' to store log information. Check your file ' . $file . ' permissions.';
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg, 'error');
		}
	}
	//}
	//vmTime('time','logInfo');
	return;

}