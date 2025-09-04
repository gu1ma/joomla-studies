<?php
if( !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/**
*
* @version $Id: virtuemart.php 11032 2024-06-27 10:05:28Z Milbo $
* @package VirtueMart
* @subpackage core
* @author Max Milbers
* @copyright Copyright (C) 2009-23 by the authors of the VirtueMart Team listed at /administrator/com_virtuemart/copyright.php - All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.net
*/

/* Require the config */

if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');

VmConfig::loadConfig();
//vmEcho::$logDebug=true;
vRequest::setRouterVars();

vmRam('Start');
//vmTime('joomla start until Vm is called','joomlaStart');
vmSetStartTime('vmStart');

vmLanguage::loadJLang('com_virtuemart', true);

//$_controller = vRequest::getCmd('view', vRequest::getCmd('controller', 'category')) ;
$_controller = vRequest::getCmd('view', 0) ;    //Old legacy, that 'view' is priorised

if(strlen($_controller)>25 or strpos($_controller,'%')!=false or strpos($_controller,' ')!=false
	or strpos($_controller,'*')!=false ){
	return false;
}

$task = vRequest::getCmd('task','') ;

vmdebug('FE main controller with controller '.$_controller.' and task '. $task);

$trigger = 'onVmSiteController';
// 	$task = vRequest::getCmd('task',vRequest::getCmd('layout',$_controller) );		$this makes trouble!

$vmFE = vRequest::getInt('vmFE',0);
if($vmFE){
	$managing = 0;
} else {
	$managing = vmAccess::isManagingFE($_controller);
}

$feViews = array('askquestion','cart','invoice','pdf','pluginresponse','productdetails','recommend','vendor','vmplg');
$app = JFactory::getApplication();


if($managing and $task!='feed' and $vmFE == 0 and !in_array($_controller,$feViews)){

	vmdebug('I am a FE-Manager');

	vRequest::setVar('managing','1');
	vRequest::setVar('tmpl','component') ;

	//vmLanguage::loadJLang('com_virtuemart');
	$jlang = vmLanguage::getLanguage();
	$tag = $jlang->getTag();
	$jlang->load('', JPATH_ADMINISTRATOR,$tag,true);
	vmLanguage::loadJLang('com_virtuemart');
	$basePath = VMPATH_ADMIN;
	$trigger = 'onVmAdminController';

	vmJsApi::jQuery(false);
	vmJsApi::loadBECSS();

	if(JVM_VERSION<4){
		$router = $app->getRouter();
		$router->setMode(0);
	}
	if(empty($_controller)) {
		//defaults to virtuemart view
		$_controller = vRequest::getCmd('controller', 'virtuemart');
		vRequest::setVar('view', $_controller);
	}

	$j = "history.pushState(null, null, document.URL);
window.addEventListener('popstate', function () {
    history.pushState(null, null, document.URL);
});";
	vmJsApi::addJScript('blockBrowserBack',$j);
} else {
	vmJsApi::jQuery();
	vmJsApi::jSite();
	vmJsApi::cssSite();
	$basePath = VMPATH_SITE;
	if(empty($_controller)){
		//defaults to category view
		$_controller = vRequest::getCmd('controller', 'category');
		vRequest::setVar('view',$_controller);
	}
}


// controller alias
if ($_controller=='pluginresponse') {
	$_controller='vmplg';
}
/* Create the controller name */
$_class = 'VirtuemartController'.ucfirst($_controller);

if (file_exists($basePath.'/controllers/'.$_controller.'.php')) {
	if (!class_exists($_class)) {
		require ($basePath.'/controllers/'.$_controller.'.php');
	}
}
else {
	// try plugins
	JPluginHelper::importPlugin('vmextended');
	$rets = vDispatcher::trigger($trigger, array($_controller));

	foreach($rets as $ret){
		if($ret) return true;
	}
	vmError('Tried to load controller "'.$_controller.'" on base path "'.$basePath.'". No File available '.$_class,'',5);
}


if (class_exists($_class)) {
    $controller = new $_class();

    $controller->execute($task);

	if(class_exists('vmrouterHelper') and vmrouterHelper::$updateCache and VmConfig::get('useCacheVmGetCategoryRoute',1)){
		vmrouterHelper::updateCache();
	}

    //vmTime($_class.' Finished task '.$task,'Start');
    vmRam('End');
    vmRamPeak('Peak');
	vmTime('"'.$_class.'" Finished task '.$task.' in '.$basePath,'vmStart');

    /* Redirect if set by the controller */
    $controller->redirect();
} else {
    vmDebug('VirtueMart controller not found: '. $_class. ' file loaded on '.$basePath.'/controllers/'.$_controller.'.php');
    if (VmConfig::get('handle_404',1)) {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		$basePath = VMPATH_SITE;
		if (file_exists($basePath.'/controllers/category.php')) {
			if (!class_exists($_class)) {
				require ($basePath.'/controllers/category.php');
			}
		}
		$controller = new VirtueMartControllerCategory();
		$controller->execute($task);


		$controller->redirect();

    } else {
		throw new RuntimeException(sprintf('VirtueMart controller not found `%s`.', $_class), 404);
    }
}
