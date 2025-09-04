<?php
/**
 *
 * Administrator menu helper class New admin template
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: vmuikit_adminuihelper.php 11016 2024-06-05 11:06:32Z Milbo $
 */


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die ();
$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart/';
JLoader::register('adminSublayouts', $adminTemplate . 'helpers/adminsublayouts.php');
JLoader::register('VmuikitHtml', $adminTemplate . 'helpers/vmuikit_html.php');
JLoader::register('VmuikitMediaHandler', $adminTemplate . 'helpers/vmuikit_mediahandler.php');

class vmuikitAdminUIHelper {


	public static $vmAdminAreaStarted = false;
	public static $backEnd = true;

	/**
	 * Start the administrator area table
	 *
	 * The entire administrator area with contained in a table which include the admin ribbon menu
	 * in the left column and the content in the right column.  This function sets up the table and
	 * displays the admin menu in the left column.
	 */
	static function startAdminArea($vmView, $selectText = 'COM_VIRTUEMART_DRDOWN_AVA2ALL') {

		if (vRequest::getCmd('format') == 'pdf') {
			return;
		}

		$view = vRequest::getCmd('view', 0);
		if (VmConfig::isSiteByApp()) {
			self::$backEnd = false;
		}

		if (self::$vmAdminAreaStarted) {
			return;
		}
		self::$vmAdminAreaStarted = true;

		//loading defaut admin CSS
		vmJsApi::loadBECSS();

		if ($view != 'virtuemart') {
			vmJsApi::css('chosen');
			vmJsApi::css('ui/jquery.ui.all');
		}

		if ($view != 'virtuemart') {
			VmJsApi::chosenDropDowns();
		}

		JHtml::_('bootstrap.tooltip');
		JHtml::_('bootstrap.popover', '.hasPopover', array('placement' => 'top'));

		/* BOF Load New admin template files */
		$adminTemplateCssPath = 'administrator/templates/vmadmin/html/com_virtuemart/';
		$adminTemplateCssUikit = $adminTemplateCssPath . 'assets/uikit/css';
		$adminTemplateCssVm = $adminTemplateCssPath . 'assets/css';

		vmJsApi::css('uikit.min',$adminTemplateCssUikit);
		vmJsApi::css('colors',$adminTemplateCssVm);

		vmJsApi::css(VmConfig::get('backendStyle', 'default-white-blue'),$adminTemplateCssVm);

		$adminTemplatePath = '/administrator/templates/vmadmin/html/com_virtuemart/';
		$adminTemplateUikit = $adminTemplatePath . 'assets/uikit/';
		vmJsApi::addJScript($adminTemplateUikit . 'js/uikit.min.js');
		vmJsApi::addJScript($adminTemplateUikit . 'js/uikit-icons.min.js');
		vmJsApi::addJScript($adminTemplatePath . 'assets/js/vmuikit-icons.js');

		/* EOF Load New admin template files */

		vmJsApi::addJScript('/administrator/components/com_virtuemart/assets/js/jquery.coookie.js');
		vmJsApi::addJScript('/administrator/components/com_virtuemart/assets/js/vm2admin.js');
		vmJsApi::addJScript($adminTemplatePath.'assets/js/vmuikit_vm2admin.js');


		if (!self::$backEnd) {
			//JToolbarHelper
			$bar = JToolbar::getInstance('toolbar');
			echo adminSublayouts::renderAdminVmSubLayout('toolbar', array('bar' => $bar));
		}
		$menuItems = self::getMenuItems();
		$fnag =  self::writeVmm();

		echo adminSublayouts::renderAdminVmSubLayout('startadmin',
			array('vmView' => $vmView,
				'selectText' => $selectText,
				'menuItems' => $menuItems,
                'fnag' => $fnag
			));


	}

	public static function writeVmm() {

		$token = vRequest::getFormToken();

		preg_match('/[a-z]/', $token, $matches);
		if (!empty($matches[0][0])) {
			$prefix = $matches[0][0];
		} else {
			$prefix = 'a';
		}

		$nag = '';
		$dplyVer = 'display: none;';
		$ackey = VmConfig::get('member_access_number', '');
		//$host = JUri::getInstance()->getHost();

		if(!empty($ack)){
			$keyPath = shopfunctions::getSafePathFor(1, 'regcache');
			if (!empty($keyPath)) {
				$keyPath .= DS . 'vmm.ini';
				if (JFile::exists($keyPath)) {
					$content = parse_ini_file($keyPath);
					if (!empty($content) and !empty($content['key']) and !empty($content['unixtime']) and !empty($content['html'])) {
						if ($content['key'] == $ackey) {
							$date = JFactory::getDate();
							$today = $date->toUnix();
							$diff = $today - $content['unixtime'];
							$spread = (int)substr((string)$diff, -1) * 4320;
							//$d = 8 * 24 * 3600;
							if ($diff > 0 and $diff < ((4 * 86400) + $spread)) {  //4 days
								$nag = htmlspecialchars_decode($content['html']);
								if ($content['res'] == 'valid') {
									$dplyVer = '';
								}
							}
						}
					}
				}
			}
		}


		if (vRequest::getCmd('vmms')) {
			$nag = '';
		}


		if ($nag === '') {
			//style="background:#FF6A00;padding:5px 5px 5px 5px;-webkit-appearance: button;-moz-appearance: button;appearance: button;"

			$nag = '
                <div style="width:auto;background:#FFFBA0;padding:8px 8px 8px 8px;font-size:14px;border:1px solid #FF6A00;color:#000000">
                    <p style="text-align:left;">Like VirtueMart?</p>
                    <p style="text-align:center;font-weight:bold;">Become a Supporter</p>
                    <p style="text-align:center;">Reliable Security and Advanced Development thanks to our members</p>
                    <p style="text-align:center;"><a href="http://extensions.virtuemart.net/support-updates/virtuemart-membership" target="_blank" ><button style="width:100%;background:#FF6A00;padding:5px 5px 5px 5px;font-size:15px;">VirtueMart membership<br>Buy now</button></a></p>
                </div>';

			if (!empty($ackey)) {

				$j = 'jQuery(document).ready(function($) {
				token = "' . $token . '";
		$.ajax({
                    type: "GET",
                    cache: true,
                    dataType: "json",
                    url: "index.php?option=com_virtuemart&view=virtuemart&task=getMemberStatus&"+token+"="+1,
                }).done(
                    function(data) {
                        if(data.html!=="undefined"){
                            var cib = $(".' . $prefix . '"+token);

                            $(".' . $prefix . '"+token).each( function( key, vcib){
	                            $(vcib).html(data.html);
	                            if(data.res=="valid"){
	                                var vmVer = $(".vmver-"+token);
	                                vmVer.show();
	                            }
                            });
                            
                        }
                    }
                )
			});';
				vmJsApi::addJScript('nag', $j);
			}
		}

		return '<div id="" class="vm-installed-version '.$prefix.'vmver-'.$token.'">
			    '.vmVersion::$CODENAME.'
		</div>
		<div class="'.$prefix . $token .'">
			'.$nag.'
		</div>';
/*		?>
		<style>#<?php echo $prefix ?>vmver-<?php echo $token ?> {
			<?php echo $dplyVer ?>
            }</style>
		<div class="vm-installed-version">VirtueMart <?php echo vmVersion::$RELEASE . ' ' . vmVersion::$REVISION ?></div>
		<div id="<?php echo $prefix ?>vmver-<?php echo $token ?>" class="vm-installed-version">
			<?php echo vmVersion::$CODENAME ?>
		</div>
		<div id="<?php echo $prefix . $token ?>">
			<?php echo $nag; ?>
		</div> <?php*/


	}

	/**
	 * Close out the adminstrator area table.
	 * @author RickG, Max Milbers
	 */
	static function endAdminArea() {
		if (!self::$backEnd) {
			return;
		}
		self::$vmAdminAreaStarted = false;

		echo adminSublayouts::renderAdminVmSubLayout('endadmin');

	}

	/**
	 * Admin UI Tabs
	 * Gives A Tab Based Navigation Back And Loads The Templates With A Nice Design
	 * @param $load_template = a key => value array. key = template name, value = Language File contraction
	 * @params $cookieName = choose a cookiename or leave empty if you don't want cookie tabs in this place
	 * @example 'shop' => 'COM_VIRTUEMART_ADMIN_CFG_SHOPTAB'
	 */
	static public function buildTabs($view, $load_template = array(), $cookieName = '', $width = 'uk-width-1-1', $css='') {
// $width = 'uk-width-2-3@m'

		$returnValues = vDispatcher::trigger('plgVmBuildTabs', array(&$view, &$load_template));

		echo adminSublayouts::renderAdminVmSubLayout('buildtabs',
			array(
				'load_template' => $load_template,
				'view' => $view,
				'cookieName' => $cookieName,
				'width' => $width,
				'css' => $css,
			));


	}


	/**
	 * Admin UI Tabs Imitation
	 * Gives A Tab Based Navigation Back And Loads The Templates With A Nice Design
	 * @param $return = return the start tag or the closing tag - choose 'start' or 'end'
	 * @params $language = pass the language string
	 * @params $css = sometimes it needs an extra css example= order view, product view
	 *
	 */
	static function imitateTabs($return, $language = '',$css='') {
		$width = 'uk-width-1-1';
		echo adminSublayouts::renderAdminVmSubLayout('imitatetabs',
			array(
				'return' => $return,
				'language' => $language,
				'width' => $width,
				'css' => $css
			));

	}


	/**
	 * Display the administrative ribbon menu.
	 * @todo The link should be done better
	 */
	static function getMenuItems() {
		if (!isset(VmConfig::$installed)) {
			VmConfig::$installed = false;
		}
		if (!VmConfig::$installed) {
			return false;
		}

		$moduleId = vRequest::getInt('module_id', 0);
		$menuItems = self::_getAdminMenu($moduleId);
		return $menuItems;

	}

	static function _getAdminMenu($moduleId = 0) {
		$db = JFactory::getDBO ();
		static $menuArr = array ();

		if(!$menuArr){
			$filter [] = "jmmod.published='1'";
			$filter [] = "item.published='1'";

			if (! empty ( $moduleId )) {
				$filter [] = 'vmmod.module_id=' . ( int ) $moduleId;
			}

			$query = 'SELECT `jmmod`.`module_id`, `module_name`, `module_perms`, `id`, `name`, `link`, `depends`, `uikit_icon`, `view`, `task`';
			$query .= 'FROM `#__virtuemart_modules` AS jmmod
						LEFT JOIN `#__virtuemart_adminmenuentries` AS item ON `jmmod`.`module_id`=`item`.`module_id`
						WHERE  ' . implode ( ' AND ', $filter ) . '
						ORDER BY `jmmod`.`ordering`, `item`.`ordering` ';

			$db->setQuery ( $query );
			$result = $db->loadAssocList ();

			for($i = 0, $n = count ( $result ); $i < $n; $i ++) {
				$row = $result [$i];
				$menuArr [$row['module_id']] ['title'] = 'COM_VIRTUEMART_' . strtoupper ( $row['module_name'] ) . '_MOD';
				$menuArr [$row['module_id']] ['items'] [] = $row ;
			}
		}

		return $menuArr;
	}


}

?>
