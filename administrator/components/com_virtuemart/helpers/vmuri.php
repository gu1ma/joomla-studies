<?php
/**
 * Uri helper class
 *
 * Handles the current URL
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2014-2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;

class vmURI{

	static function getCurrentUrlBy ($source = 'get',$route = false, $white = true, $ignore = false, $query = false){

		$vars = array('id', 'option', 'view', 'controller', 'task',
			'virtuemart_category_id', 'virtuemart_manufacturer_id', 'virtuemart_product_id', 'virtuemart_user_id', 'virtuemart_vendor_id',
			'virtuemart_shipment_id', 'virtuemart_payment_id', 'virtuemart_country_id', 'virtuemart_currency_id', 'virtuemart_order_id',
			'addrtype', 'virtuemart_user_info', 'layout', 'format', 'limitstart', 'limit', 'language', 'keyword', 'search',
			'order_number', 'order_pass', 'tmpl', 'usersearch', 'managing', 'orderby', 'dir', 'Itemid', 'customfields', 'lang', 'searchAllCats');	//TODO Maybe better to remove the 'lang', which keeps the SEF suffix

		if($query){
			$url = array();
		} else {
			$url = '';
		}

		if($source=='get') {
			$get = vRequest::getGet(FILTER_SANITIZE_URL);
		} else if($source=='request'){
			$get = vRequest::getRequest(FILTER_SANITIZE_URL);
		} else {
			$get = vRequest::getPost(FILTER_SANITIZE_URL);
		}

		if($white){
			if(is_array($white) ){
				$vars = array_merge($vars, $white);
			}
			if(is_array($ignore) ){
				$vars = array_diff($vars, $ignore);
			}

			$params = array();
			if(is_array($get) and !empty($get) and is_array($vars) and !empty($vars)){
				foreach($vars as $var){
					if(isset($get[$var])){
						$params[$var] = $get[$var];
					}
				}
				//$vars = array_intersect($vars, $get); //Did not work with it, got PHP-errors
			}
			//vmEcho::$echoDebug = true;vmdebug('getCurrentUrlBy', $get, $params);
			foreach ($params as $k=>$v){
				$k = vRequest::filterUrl($k);
				$v = vRequest::getVar($k);
				if(isset($v)){
					if(is_array($v)){
						foreach($v as $ka => $va){
							if($query){
								$url[$k][urlencode(vRequest::filterUrl($ka))] = urlencode(vRequest::filterUrl($va));
							} else{
								$url .= $k.'['.urlencode(vRequest::filterUrl($ka)).']='.urlencode(vRequest::filterUrl($va)).'&';
							}
						}
					} else {
						if($query){
							$url[$k] = urlencode(vRequest::filterUrl($v));
						} else {
							$url .= $k.'='.urlencode(vRequest::filterUrl($v)).'&';
						}
					}
				}
			}
		} else {


			foreach($get as $k => $v){
				$k = vRequest::filterUrl($k);
				if(is_array($v)){
					foreach($v as $ka => $va){
						if($query){
							$url[$k][urlencode(vRequest::filterUrl($ka))] = urlencode(vRequest::filterUrl($va));
						} else{
							$url .= $k.'['.urlencode(vRequest::filterUrl($ka)).']='.urlencode(vRequest::filterUrl($va)).'&';
						}
					}
				} else {
					if($query){
						$url[$k] = urlencode(vRequest::filterUrl($v));
					} else {
						$url .= $k.'='.urlencode(vRequest::filterUrl($v)).'&';
					}
				}
			}
		}

		if(!$query){
			$url = $urlold = rtrim($url,'&');
			if(!empty($url)){
				$url = 'index.php?'.$url;
				if ($route){
					$url = JRoute::_($url);
				}
			}
		}

		return $url;
	}

	/**
	 * @deprecated  use getCurrentUrlBy instead
	 * @param bool $route
	 * @return string
	 */
	static function getGetUrl ($route = false){
		return self::getCurrentUrlBy('get',$route, false);
	}

	static function getCleanUrl ($JURIInstance = 0,$parts = array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment')) {

		if($JURIInstance===0) $JURIInstance = self::getURI();
		return vRequest::filterUrl($JURIInstance->toString($parts));
	}

	static function createUrlWithPrefix($url){

		$admin = '';
		if(!VmConfig::isSiteByApp()){
			$admin = 'administrator/';
		}

		$rurl = JURI::root(false).$admin.$url;
		//vmdebug('createUrlWithPrefix',$rurl,$url);
		return $rurl;
	}

	static function useSSL (){
		static $useSSL = null;

		if(isset($useSSL)) return $useSSL;

		$jconf = JFactory::getConfig();
		if(VmConfig::get('useSSL', 0)!=0 or $jconf->get('force_ssl')=='2'){
			$useSSL = 1;
			vmdebug('SSL enabled');
		} else {
			$useSSL = 0;
		}
		return $useSSL;
	}

	static function getURI(){
		static $cUri = null;
		if($cUri === null){
			$cUri = JUri::getInstance();//->toString();
		}
		return $cUri;
	}

	static function urlencode($in){

		if(empty($in)){
			return '';
		} else {
			return urlencode($in);
		}
	}
}
