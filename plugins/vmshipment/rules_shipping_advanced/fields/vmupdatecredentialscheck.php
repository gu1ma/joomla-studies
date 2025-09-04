<?php
defined('_JEXEC') or die();
/**
 *
 * @package    VirtueMart
 * @subpackage Plugins  - Fields
 * @author Reinhold Kainhofer, Open Tools
 * @link http://www.open-tools.net
 * @copyright Copyright (c) 2016 Reinhold Kainhofer. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists( 'VmConfig' )) 
    require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
VmConfig::loadConfig();

class JFormFieldVmUpdateCredentialsCheck extends JFormField {

    var $_name = 'vmUpdateCredentialsCheck';

    protected function getJavaScript() {
		return "
var credentials_ajaxurl = \"".$this->element["ajaxurl"]."\";
var credentials_updateMessages = function(messages, area) {
    jQuery( \"#system-message-container #system-message .\"+area+\"-message\").remove();
    var newmessages = jQuery( messages ).find(\"div.alert, .message\").addClass(area+\"-message\");
    if (!jQuery( \"#system-message-container #system-message\").length && newmessages.length) {
        if (jQuery(newmessages).first().prop(\"tagName\")==\"dt\") { // Joomla 2.x:
            jQuery( \"#system-message-container\" ).append( \"<dl id=\'system-message\'></div>\" );
        } else {
            jQuery( \"#system-message-container\" ).append( \"<div id=\'system-message\'></div>\" );
        }
    }
    newmessages.appendTo( \"#system-message-container #system-message\");
};

var checkUpdateCredentialsError = function() {
	alert (\"".JText::_('OPENTOOLS_CHECK_CREDENTIALS_ERROR')."\"); 
}

var checkUpdateCredentials = function () {
	var ordernumber = jQuery('#jform_params_order_number').val();
	var orderpass = jQuery('#jform_params_order_pass').val();
	
    var ajaxargs = {
        type: \"POST\",
		dataType: \"text\",
        url: credentials_ajaxurl,
        data: { 
			action: \"check_update_access\",
			order_number: ordernumber, 
			order_pass: orderpass
		},
		
		success: function ( json ) {
			try {
				json = jQuery.parseJSON(json);
				credentials_updateMessages(json['messages'], 'ordernumber');
			} catch (e) {
				checkUpdateCredentialsError();
				return;
			}
			var success=0;
			if (json.success>0) {
				success=1;
			}
			jQuery('#update_credentials_hidden_checked').val(success);
			jQuery('.credentials_checked')
				.removeClass('credentials_checked_0')
				.removeClass('credentials_checked_1')
				.addClass('credentials_checked_'+success);
		},
		error: function() { checkUpdateCredentialsError(); },
		complete: function() {  },
	};
	jQuery.ajax(ajaxargs);
};
jQuery(document).ready (function () {
	jQuery('#jform_params_order_number').focusout(checkUpdateCredentials);
	jQuery('#jform_params_order_pass').focusout(checkUpdateCredentials);
});

";
    }
    
    protected function getCSS() {
		return "
div.credentials_checked {
	padding: 10px 5px;
    float: left;
    clear: left;
    display: block;
    width: 100%;
}
div.credentials_checked_0 {
	background-color: #FFD0D0;
}
div.credentials_checked_1 {
	background-color: #D0FFD0;
}
a#credentials_check {
}

";
    }
	protected function getInput() {
		// Tell the user that automatic updates are not available in Joomla 2.5:
		if (version_compare(JVERSION, '3.0', 'lt')) {
			JFactory::getApplication()->enqueueMessage(JText::_('OPENTOOLS_COMMERCIAL_UPDATES_J25'), 'warning');
		}

		vmJsApi::jQuery();
		
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration($this->getJavaScript());
		$doc->addStyleDeclaration($this->getCSS());
		
		if ($this->value!=1) {
			$this->value=0;
		}
// 		if ($this->value==1) {
		return "<input type='hidden' id=\"update_credentials_hidden_checked\" name='".$this->name."' value='".$this->value."' /><div class='credentials_checked credentials_checked_".$this->value."'><a href=\"#\" class=\"btn btn-info credentials_check\" id=\"credentials_check\" onclick=\"checkUpdateCredentials()\" >".JText::_('OPENTOOLS_CHECK_CREDENTIALS')."</a></div>";
	}
}
