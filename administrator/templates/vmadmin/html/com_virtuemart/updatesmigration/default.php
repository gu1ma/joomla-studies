<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage UpdatesMigration
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: default.php 10929 2023-11-01 12:13:19Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart/';
JLoader::register('vmuikitAdminUIHelper', $adminTemplate . 'helpers/vmuikit_adminuihelper.php');
vmuikitAdminUIHelper::startAdminArea($this);

echo '<div id="cpanel">';

$tabs = array (	'tools' 	=> 	'COM_VIRTUEMART_UPDATE_TOOLS_TAB',
	'gdpr' => 'COM_VM_GDPR_TAB',
'spwizard' 	=> 	'COM_VM_WIZARD_TAB',
'migrator' 	=> 	'COM_VIRTUEMART_MIGRATION_TAB');

if(vRequest::getBool('show_spwizard',false)){
	unset($tabs['spwizard']);
	$tabs = array_merge(array('spwizard' 	=> 	'COM_VM_WIZARD_TAB'), $tabs);
}
vmuikitAdminUIHelper::buildTabs ( $this,  $tabs);

vmuikitAdminUIHelper::endAdminArea();

echo '</div>';
$j = '

if (typeof Virtuemart === "undefined")
    var Virtuemart = {};
    
	Virtuemart.vmConfirm = function(event){
		console.log("my event.currentTarget",event.currentTarget);
		event.preventDefault();
		if(!confirm(event.currentTarget.dataset.query)){
			console.log("confirm false");
		} else {
			var nodeName = event.currentTarget.nodeName;
			if(event.currentTarget.nodeName == "BUTTON"){
				f = jQuery("#gdpr");
				console.log("my form",f);
				f[0].task.value = event.currentTarget.dataset.task;
				f.submit();
			} else {
				window.location.href = event.currentTarget.dataset.url;
			}
		}
	};
	
	jQuery(document).ready(function() {
		var confi = jQuery(".vmjs-confirm");
		confi.off("click submit",Virtuemart.vmConfirm);
		confi.on("click submit",Virtuemart.vmConfirm);
	});

';


vmJsApi::addJScript('vm-confirm',$j);