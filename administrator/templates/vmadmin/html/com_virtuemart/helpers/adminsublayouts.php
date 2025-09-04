<?php
/**
 *
 * sublayouts for the admin template
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers, Valérie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: adminsublayouts.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die('Restricted access');


class adminSublayouts {

	/** Caches sublayouts. Currently unused, it is maybe interesting if we have better caches.
	 * like memcache, or similar. File cache seems to be a lot slower!
	 * @param $name
	 * @param int $viewData
	 * @return string|null
	 */
	static public function renderAdminVmSubLayoutCached($name,$viewData=0){

		static $menu = null;
		vmStartTimer('renderAdminVmSubLayoutCached');
		if($menu === null){
			$useCache = VmConfig::get('UseRenderAdminVmSubLayoutCached',false);
			if($useCache and $menu === null){
				$cache = VmConfig::getCache('com_virtuemart_bemenu','callback');
				$cache->setCaching(true);
				$menu = $cache->get( array( 'adminSublayouts', 'renderAdminVmSubLayout' ),array($name,$viewData));

			} else {
				$menu = self::renderAdminVmSubLayout($name,$viewData);
			}
		}

		vmTime('renderAdminVmSubLayoutCached','renderAdminVmSubLayoutCached');
		return $menu;
	}

	/**
	 * Renders sublayouts
	 *
	 * @param $name
	 * @param int $viewData viewdata for the rendered sublayout, do not remove
	 * @return string
	 */
	static public function renderAdminVmSubLayout($name,$viewData=0){

		$lPath = self::getAdminVmSubLayoutPath ($name);

		if($lPath){
			ob_start ();
			include ($lPath);
			return ob_get_clean();
		} else {
			vmdebug('renderVmSubLayout layout not found '.$name);
		}

	}

	static public function getAdminVmSubLayoutPath($name){

		static $layouts = array();

		if(isset($layouts[$name])){
			return $layouts[$name];
		} else {

			// get the template and default paths for the layout if the site template has a layout override, use it
			$tP = VMPATH_ROOT .'/administrator/templates/vmadmin/html/com_virtuemart/sublayouts/'. $name .'.php';

			$layouts[$name] = $tP;

			return $layouts[$name];
		}


	}
}
