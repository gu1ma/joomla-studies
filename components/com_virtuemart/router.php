<?php


if(  !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/**
 *
 * @package VirtueMart
 * @author Kohl Patrick
 * @author Max Milbers
 * @subpackage router
 * @version $Id$
 * @copyright Copyright (C) 2009 - 2022 by the VirtueMart Team and authors
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

if(version_compare(JVERSION,'4.0.0','ge')) {

	/**
	 * Routing class from com_contact
	 *
	 * @since  3.3
	 */
	class VirtuemartRouter extends RouterView {

		/**
		 * Content Component router constructor
		 *
		 * @param   SiteApplication           $app              The application object
		 * @param   AbstractMenu              $menu             The menu object to work with
		 */
		public function __construct(SiteApplication $app, AbstractMenu $menu) {
			parent::__construct($app, $menu);

			$this->attachRule(new MenuRules($this));
			$this->attachRule(new StandardRules($this));
			$this->attachRule(new NomenuRules($this));
		}

		public function parse(&$segments) {
			require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'router.php');
			$ret = virtuemartParseRoute($segments);
			if (!empty($ret)) {
				$segments = array();
			}
			return $ret;
		}

		/**
		 * Called before build
		 * @param array $query
		 * @return array
		 */
		public function preprocess($query) {

			vmrouterHelper::getInstance($query);
			/*if(!empty($query['view']) and $query['view'] == 'productdetails'){
				vmEcho::$_debug = 1;
				vmrouterHelper::$debug = 1;
			}*/

			//vmdebug('preprocess',$query);
			if(empty($query['Itemid'])){
				$Itemid = vmrouterHelper::findCorrectItemid( $query );

				if(empty($Itemid)){
					$Itemid = vmrouterHelper::findCorrectItemidBySQL($query, vmLanguage::$jSelLangTag);
					//if(vmrouterHelper::$debug)vmdebug('My preprocess findCorrectItemidBySQL',$Itemid);
				} else {
					//if(vmrouterHelper::$debug)vmdebug('My preprocess findCorrectItemid',$Itemid);
				}

				if(!empty($Itemid)){
					$query['Itemid'] = $Itemid;
					if(vmrouterHelper::$debug)vmdebug('My preprocess $Itemid',$Itemid);
				} else
					if(!empty(vmrouterHelper::$Itemid)){
						$query['Itemid'] = vmrouterHelper::$Itemid;
						if(vmrouterHelper::$debug)vmdebug('preprocess Itemid by vmrouterHelper::$Itemid '.$query['Itemid']);
					} else {
						vmdebug('Strange error, empty vmrouterHelper::$Itemid');
					}
			}

			/*if(empty($query['view']) or $query['view'] != 'productdetails'){
				vmEcho::$_debug = 0;
				vmrouterHelper::$debug = 0;
			}*/

			return parent::preprocess($query);
		}

		public function build(&$query) {

			require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'router.php');
			$ret = virtuemartBuildRoute($query);
			return $ret;
		}

	}

}

function virtuemartBuildRoute(&$query) {

	vmrouterHelper::getInstance($query);
	//vmSetStartTime('virtuemartBuildRoute');
	//vmrouterHelper::$debug = 1;
	VmEcho::$_debug = vmrouterHelper::$debug;
	//vmTrace('virtuemartBuildRoute',$query);
	if(vmrouterHelper::$debug) vmdebug('Building segments from initial query: ',$query);
	$segments = vmrouterHelper::buildRoute($query);
	if(vmrouterHelper::$debug) vmdebug('Building segments from query, left: ',array('non_segmented_query'=>$query, 'segments'=>$segments));
	//VmConfig::$_debug = TRUE; vmTime('virtuemartBuildRoute', 'virtuemartBuildRoute', true);
	VmEcho::$_debug = vmrouterHelper::$debugSet;
	return $segments;
}

function virtuemartParseRoute($segments) {

	vmrouterHelper::getInstance($query);

	VmEcho::$_debug = vmrouterHelper::$debug;

	$vars = vmrouterHelper::parseRoute($segments);
	//vmdebug('Parse url ',$vars);
	if(!empty($vars)) {

		$app = JFactory::getApplication();
		foreach ($vars as $key=>$val) {
			$app->input->set($key, $val);
		}
		$_GET = array_merge( $_GET, $vars);
		//$_REQUEST = array_merge( $_REQUEST, $_GET);
	}
	//vmdebug('Parse url ',$vars);
	vRequest::setRouterVars();
	VmEcho::$_debug = vmrouterHelper::$debugSet;
	return $vars;   //*/
}


class vmrouterHelper {

	static public $debug = false;
	static public $debugSet = false;
	static public $slang = '';
	static public $langFback = '';
	static protected $query = array();

	static public $andAccess = null;
	static public $authStr = null;

	/* Joomla menus ID object from com_virtuemart */
	static public $menu = null ;

	/* Joomla active menu( Itemid ) object */
	static public $activeMenu = null ;

	/*
	  * $use_id type boolean
	  * Use the Id's of categorie and product or not
	  */
	static public $use_id = false ;

	static public $cats_route_cache = null;
	//static public $route_cache = null;

	static public $updateCache = false;
	static public $seo_translate = false ;
	static public $seo_sufix = '';
	static public $seo_sufix_size = '';
	static public $use_seo_suffix = false;
	static public $full = false;

	static public $useGivenItemid = false;
	static public $Itemid = 0;
	static public $rItemid = 0;
	static protected $orderings = null;
	static public $limit = null ;

	static public $router_disabled = false ;

	static protected $_db = null;

	static protected $_catRoute = array ();
	//static protected $_route = array ();

	static public $byMenu = 0;
	static public $template = 0;
	static public $CategoryName = array();
	static protected $dbview = array( 'askquestion' => 'askquestion', 'cart' => 'cart', 'category' => 'category', 'invoice' => 'invoice', 'manufacturer' => 'manufacturer', 'orders' => 'orders', 'pdf' => 'pdf', 'productdetails' =>'productdetails', 'recommend' => 'recommend', 'user' => 'user', 'vendor' => 'vendor', 'virtuemart' => 'virtuemart', 'vmplg' => 'vmplg');


	static protected function init($query) {

		if(self::$_db!==null) return false;

		if (!class_exists( 'VmConfig' ) or VmConfig::$iniLang or !isset(VmLanguage::$currLangTag)) {
			if (!class_exists( 'VmConfig' )){
				require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
			}

			VmConfig::loadConfig(FALSE,FALSE,true,false);    // this is needed in case VmConfig was not yet loaded before
			//vmdebug('Router Instance, loaded current Lang Tag in config ',VmLanguage::$currLangTag, VmConfig::$vmlang);
		}

		//vmdebug('Router init');
		self::$debugSet = VmConfig::$_debug;
		VmConfig::$_debug = self::$debug = VmConfig::get('debug_enable_router',0);

		if(isset($query['lang'])){
			$lang_code = vmrouterHelper::getLanguageTagBySefTag($query['lang']); // by default it returns a full language tag such as nl-NL
		} else {
			$lang_code = JFactory::getApplication()->input->get('language', null);  //this is set by languageFilterPlugin
		}
		//vmdebug('called get Router instance',VmLanguage::$currLangTag,$lang_code);
		if (empty($lang_code) or VmLanguage::$currLangTag!=$lang_code) {
			//vmdebug('Router language switch from '.VmLanguage::$currLangTag.' to '.$lang_code);
			vmLanguage::setLanguageByTag($lang_code, false, false); //this is needed if VmConfig was called in incompatible context and thus current VmConfig::$vmlang IS INCORRECT
			vmLanguage::loadJLang('com_virtuemart.sef',true);
			//vmdebug('Router language switchED TO '.VmConfig::$vmlangTag.VmConfig::$vmlangTag);
		}//*/

		self::$template = JFactory::getApplication()->getTemplate(true);
		if(empty(self::$template) or !isset(self::$template->id)){
			self::$template->id = 0;
		}

		self::$_db = JFactory::getDbo();
		if (!self::$router_disabled = VmConfig::get('seo_disabled', false)) {


			self::$seo_translate = VmConfig::get('seo_translate', false);


			//if ( $this->seo_translate ) {
			vmLanguage::loadJLang('com_virtuemart.sef',true);
			/*} else {
				$this->Jlang = vmLanguage::getLanguage();
			}*/

			self::$byMenu =  (int)VmConfig::get('router_by_menu', 0);
			self::$seo_sufix = '';
			self::$seo_sufix_size = 0;

			self::$use_id = VmConfig::get('seo_use_id', false);
			self::$use_seo_suffix = VmConfig::get('use_seo_suffix', true);
			self::$seo_sufix = VmConfig::get('seo_sufix', '-detail');
			self::$seo_sufix_size = strlen(self::$seo_sufix) ;


			self::$full = VmConfig::get('seo_full',true);
			self::$useGivenItemid = 0;//VmConfig::get('useGivenItemid',false);

			self::$slang = VmLanguage::$currLangTag;

			if(self::$andAccess === null){
				$user = JFactory::getUser();
				$auth = array_unique($user->getAuthorisedViewLevels());
				self::$andAccess = ' AND client_id=0 AND published=1 AND ( access="' . implode ('" OR access="', $auth) . '" ) ';
				self::$authStr = implode('.',$auth);
			}

		}

		self::setActiveMenu();
		//self::setMenuItemId();

		self::setRoutingQuery($query);

		if(VmConfig::get('useCacheVmGetCategoryRoute',1)) {
			//vmdebug('getCategoryRoute useCacheVmGetCategoryRoute and empty(self::$_catRoute)', self::$_catRoute);
			self::$cats_route_cache = VmConfig::getCache('com_virtuemart_cats_route', '');
			$routeCache = self::$cats_route_cache->get('com_virtuemart_cats_route');
			if ($routeCache) {
				self::$_catRoute = $routeCache;
			}

			/*self::$route_cache = VmConfig::getCache('com_virtuemart_route', '');
			$routeCache = self::$route_cache->get('com_virtuemart_route');
			if ($routeCache) {
				self::$_route = $routeCache;
			}*/
		}

		//vmdebug('Router initialised with language '.$this->slang);
		VmConfig::$_debug = self::$debugSet;

		return true;
	}

	static public function setRoutingQuery($query){

		if(!empty($query['Itemid'])){
			self::$Itemid = $query['Itemid'];
		}

		// if language switcher we must know the $query
		self::$query = $query;

		self::$langFback = vmLanguage::getUseLangFallback(true);

		self::setMenuItemId();

		if(!self::$Itemid){

			self::$Itemid = self::$menu['virtuemart'];
			//vmTrace('setRoutingQuery');
			//vmdebug('my router',$this);
			if(vmrouterHelper::$debug) vmdebug('There is no requested itemid set home Itemid',self::$Itemid);
		}
		if(!self::$Itemid) {
			if(vmrouterHelper::$debug) vmdebug( 'There is still no itemid' );
			self::$Itemid = 0;
		}

		//vmdebug('setRoutingQuery executed with language '.$this->slang, $query);
	}

	static public function getInstance(&$query) {

		if (vmrouterHelper::init ($query)){


			if (self::$limit===null){
				$app = JFactory::getApplication();
				$view = 'category';
				if(isset($query['view'])) $view = $query['view'];

				//We need to set the default here.
				self::$limit = $app->getUserStateFromRequest('com_virtuemart.'.$view.'.limit', 'limit', VmConfig::get('llimit_init_FE', 24), 'int');
				if(empty(self::$limit)){
					self::$limit = VmConfig::get('llimit_init_FE', 24);
				}
				//vmdebug('set router limit ',self::$limit);
			}

		} else {
			if(self::$slang != VmLanguage::$currLangTag or (self::$byMenu and !empty($query['Itemid']) and $query['Itemid'] != self::$Itemid)){
				//vmdebug('Execute setRoutingQuery because, ',VmLanguage::$currLangTag,$query['Itemid']);
				self::$slang = VmLanguage::$currLangTag;
				vmrouterHelper::setRoutingQuery($query);
			}
		}

	}

	static public function buildRoute(&$query){

		$segments = array();

		// simple route , no work , for very slow server or test purpose
		if (self::$router_disabled) {
			foreach ($query as $key => $value){
				if  ($key != 'option')  {
					if ($key != 'Itemid' and $key != 'lang') {
						if(is_array($value)){
							$value = implode(',',$value);
						}
						$segments[]=$key.'/'.$value;
						unset($query[$key]);
					}
				}
			}
			vmrouterHelper::resetLanguage();
			return $segments;
		}

		if (!VmConfig::isSite()) return $segments;

		$view = '';

		$jmenu = self::$menu ;
		//vmdebug('virtuemartBuildRoute $jmenu',self::$query,self::$activeMenu,self::$menuVmitems);
		if(isset($query['langswitch'])) unset($query['langswitch']);

		if(isset($query['view'])){
			$view = $query['view'];
			unset($query['view']);
		}

		switch ($view) {
			case 'virtuemart';
				$query['Itemid'] = $jmenu['virtuemart'] ;
				break;
			case 'category';
				$start = null;
				$limitstart = null;
				$limit = vmrouterHelper::$limit;

				if(vmrouterHelper::$debug) vmdebug('my category build route',$query);
				if ( !empty($query['virtuemart_manufacturer_id'])  ) {
					$segments[] = self::lang('manufacturer').'/'.self::getManufacturerName($query['virtuemart_manufacturer_id']) ;
					//unset($query['virtuemart_manufacturer_id']);
				}

				if ( isset($query['virtuemart_category_id']) or isset($query['virtuemart_manufacturer_id']) ) {
					$categoryRoute = null;
					$catId = empty($query['virtuemart_category_id'])? 0:(int)$query['virtuemart_category_id'];
					$manId = empty($query['virtuemart_manufacturer_id'])? 0:(int)$query['virtuemart_manufacturer_id'];
					if(self::$full or !isset($query['virtuemart_product_id'])){
						$categoryRoute = self::getCategoryRoute( $catId, $manId);
						if ($categoryRoute->route) {
							$segments[] = $categoryRoute->route;
						}
					}
					//We should not need that, because it is loaded, when the category is opened
					//if(!empty($catId)) $limit = vmrouterHelper::getLimitByCategory($catId);

					if(isset($jmenu['virtuemart_category_id'][$catId][$manId])) {
						$query['Itemid'] = $jmenu['virtuemart_category_id'][$catId][$manId];
					} else {
						if($categoryRoute===null) $categoryRoute = self::getCategoryRoute($catId,$manId);
						//http://forum.virtuemart.net/index.php?topic=121642.0
						if (!empty($categoryRoute->Itemid)) {
							$query['Itemid'] = $categoryRoute->Itemid;
						} else if (!empty($jmenu['virtuemart'])) {
							$query['Itemid'] = $jmenu['virtuemart'];
						}
					}

					unset($query['virtuemart_category_id']);
					unset($query['virtuemart_manufacturer_id']);
				}


				/*if ( isset($query['search'])  ) {
					$segments[] = self::lang('search') ;
					unset($query['search']);
				}*/
				/*if ( isset($query['keyword'] )) {
					$segments[] = self::lang('search').'='.$query['keyword'];
					unset($query['keyword']);
				}*/

				if ( isset($query['orderby']) ) {
					$segments[] = self::lang('by').','.self::getOrderingKey( $query['orderby']) ;
					unset($query['orderby']);
				}

				if ( isset($query['dir']) ) {
					if ($query['dir'] =='DESC'){
						$dir = 'dirDesc';
					} else {
						$dir = 'dirAsc';
					}
					$segments[] = $dir;
					unset($query['dir']);
				}


				// Joomla replace before route limitstart by start but without SEF this is start !
				if ( isset($query['limitstart'] ) ) {
					$limitstart = (int)$query['limitstart'] ;
					unset($query['limitstart']);
				}
				if ( isset($query['start'] ) ) {
					$start = (int)$query['start'] ;
					unset($query['start']);
					if($limitstart === null){
						$limitstart = $start;
						if(vmrouterHelper::$debug) vmdebug('Pagination limits $start !== null &&  $limitstart=== null, set $limitstart=$start',$start,$limitstart);
					} else {
						if(vmrouterHelper::$debug) vmdebug('Pagination limits $start !== null &&  $limitstart!== null',$start,$limitstart);
					}
				}
				if ( isset($query['limit'] ) ) {
					$limit = (int)$query['limit'] ;
					unset($query['limit']);
				}

				$limitstart = intval($limitstart);

				$limit = intval($limit);

				if ( $limitstart>0 ) {
					//For the urls leading to the paginated pages
					$segments[] = self::lang('results') .','. ($limitstart+1).'-'.($limitstart+$limit);
					if(vmrouterHelper::$debug) vmdebug('category case $limitstart>0 my limitstart, limit ',$limitstart,$limit);
				} else if (!empty($limit) /*$limit !== null*/ && $limit != vmrouterHelper::$limit ) {
					//for the urls of the list where the user sets the pagination size/limit
					$segments[] = self::lang('results') .',1-'.$limit ;
					if(vmrouterHelper::$debug) vmdebug('category case !empty($limit) my limitstart, limit ',$limitstart,$limit);
				} else if(!empty($query['search']) or !empty($query['keyword'])){
					$segments[] = self::lang('results') .',1-'.vmrouterHelper::$limit ;
					if(vmrouterHelper::$debug) vmdebug('category case last my limitstart, limit ',$limitstart,$limit);
				}

				if ( isset($query['clearCart'] ) and empty($query['clearCart'])) {
					unset($query['clearCart']);
				}

				//By stAn
				if (isset($query['customfields']) && (is_array($query['customfields']))) {
					foreach ($query['customfields'] as $ind=>$val) {
						if ($val === '') {
							unset($query['customfields'][$ind]);
						}
					}
				}
				if (empty($query['customfields'])) unset($query['customfields']);
				if(self::$debug)vmdebug('category link segments',$query, $segments);
				break;
			//Shop product details view
			case 'productdetails';

				$virtuemart_product_id = false;
				if (!empty($query['virtuemart_product_id']) and isset($jmenu['virtuemart_product_id']) and isset($jmenu['virtuemart_product_id'][ $query['virtuemart_product_id'] ] ) ) {
					$query['Itemid'] = $jmenu['virtuemart_product_id'][$query['virtuemart_product_id']];
					unset($query['virtuemart_product_id']);
					unset($query['virtuemart_category_id']);
					unset($query['virtuemart_manufacturer_id']);
				} else {
					if(isset($query['virtuemart_product_id'])) {
						if (self::$use_id) $segments[] = $query['virtuemart_product_id'];
						$virtuemart_product_id = $query['virtuemart_product_id'];
						unset($query['virtuemart_product_id']);
					}

					vmdebug('vmRouter case \'productdetails\' Itemid');
					//unset($query['Itemid']);
					$Itemid = false;
					if(self::$full){
						if(empty( $query['virtuemart_category_id'])){
							$query['virtuemart_category_id'] = self::getParentProductcategory($virtuemart_product_id);
						}
						$catId = empty($query['virtuemart_category_id'])? 0:(int)$query['virtuemart_category_id'];
						$manId = empty($query['virtuemart_manufacturer_id'])? 0:(int)$query['virtuemart_manufacturer_id'];


						if(!(empty( $catId ) and empty( $manId ))){
							// GJC here it goes wrong - it ignores the canonical cat
							// GJC fix in setMenuItemId() by choosing the desired url manually in the menu template overide parameter
							$categoryRoute = self::getCategoryRoute($catId, $manId, true);
							if ($categoryRoute->route) $segments[] = $categoryRoute->route;

							vmdebug('vmRouter case \'productdetails\' not empty cat ',$query,$catId,$categoryRoute,$segments);
							//Maybe the ref should be just handled by the rItemid?
							/*if(self::$useGivenItemid and self::$rItemid){
								if(self::$checkItemid(self::$rItemid)){
									$Itemid = self::$rItemid;
								}
							}*/
							if(!$Itemid){
								if ($categoryRoute->Itemid) $Itemid = $categoryRoute->Itemid;
								else $Itemid = $jmenu['virtuemart'];
							}

						} else {
							//$query['Itemid'] = $jmenu['virtuemart']?$jmenu['virtuemart']:@$jmenu['virtuemart_category_id'][0][0];
						}
					} else {
						//Itemid is needed even if seo_full = 0
						//$query['Itemid'] = $jmenu['virtuemart']?$jmenu['virtuemart']:@$jmenu['virtuemart_category_id'][0][0];
					}

					if(empty($Itemid)){
						//vmdebug('vmRouter case \'productdetails\' Itemid not found yet '.self::$rItemid,$virtuemart_product_id);
						//Itemid is needed even if seo_full = 0
						if(!empty($jmenu['virtuemart'])){
							$Itemid = $jmenu['virtuemart'];
						} else if(!empty($jmenu['virtuemart_category_id'][0]) and !empty($jmenu['virtuemart_category_id'][0][0])){
							$Itemid = $jmenu['virtuemart_category_id'][0][0];
						}
					}

					if(empty($Itemid)){
						if(vmrouterHelper::$debug) vmdebug('vmRouter case \'productdetails\' No Itemid found, Itemid existing in $query?',$query['Itemid']);
					}  else {
						$query['Itemid'] = $Itemid;
					}
					unset($query['start']);
					unset($query['limitstart']);
					unset($query['limit']);
					unset($query['virtuemart_category_id']);
					unset($query['virtuemart_manufacturer_id']);


					if($virtuemart_product_id)
						$segments[] = self::getProductName($virtuemart_product_id);
				}
				break;
			case 'manufacturer';

				if(isset($query['virtuemart_manufacturer_id'])) {
					if (isset($jmenu['virtuemart_manufacturer_id'][ $query['virtuemart_manufacturer_id'] ] ) ) {
						$query['Itemid'] = $jmenu['virtuemart_manufacturer_id'][$query['virtuemart_manufacturer_id']];
					} else {
						$segments[] = self::lang('manufacturers').'/'.self::getManufacturerName($query['virtuemart_manufacturer_id']) ;
						if ( isset($jmenu['manufacturer']) ) $query['Itemid'] = $jmenu['manufacturer'];
						else $query['Itemid'] = $jmenu['virtuemart'];
					}
					unset($query['virtuemart_manufacturer_id']);
				} else {
					if ( isset($jmenu['manufacturer']) ) $query['Itemid'] = $jmenu['manufacturer'];
					else $query['Itemid'] = $jmenu['virtuemart'];
				}
				break;
			case 'user';
				//vmdebug('virtuemartBuildRoute case user query and jmenu',$query, $jmenu);
				if ( isset($jmenu['user'])) $query['Itemid'] = $jmenu['user'];
				else {
					$segments[] = self::lang('user') ;
					$query['Itemid'] = $jmenu['virtuemart'];
				}

				if (isset($query['task'])) {
					//vmdebug('my task in user view',$query['task']);
					if($query['task']=='editaddresscart'){
						if ($query['addrtype'] == 'ST'){
							$segments[] = self::lang('editaddresscartST') ;
						} else {
							$segments[] = self::lang('editaddresscartBT') ;
						}
					}

					else if($query['task']=='editaddresscheckout'){
						if ($query['addrtype'] == 'ST'){
							$segments[] = self::lang('editaddresscheckoutST') ;
						} else {
							$segments[] = self::lang('editaddresscheckoutBT') ;
						}
					}

					else if($query['task']=='editaddress'){

						if (isset($query['addrtype']) and $query['addrtype'] == 'ST'){
							$segments[] = self::lang('editaddressST') ;
						} else {
							$segments[] = self::lang('editaddressBT') ;
						}
					}
					else if($query['task']=='addST'){
						$segments[] = self::lang('addST') ;
					}
					else {
						$segments[] =  self::lang($query['task']);
					}
					unset ($query['task'] , $query['addrtype']);
				}
				if(JVM_VERSION>3 and isset($jmenu['user'])){
					array_unshift($segments, self::lang('user') );
				}
				//vmdebug('Router buildRoute case user query and segments',$query,$segments);
				break;
			case 'vendor';
				/* VM208 */
				if(isset($query['virtuemart_vendor_id'])) {
					if (isset($jmenu['virtuemart_vendor_id'][ $query['virtuemart_vendor_id'] ] ) ) {
						$query['Itemid'] = $jmenu['virtuemart_vendor_id'][$query['virtuemart_vendor_id']];
					} else {
						if ( isset($jmenu['vendor']) ) {
							$query['Itemid'] = $jmenu['vendor'];
						} else {
							$segments[] = self::lang('vendor') ;
							$query['Itemid'] = $jmenu['virtuemart'];
						}
					}
				} else if ( isset($jmenu['vendor']) ) {
					$query['Itemid'] = $jmenu['vendor'];
				} else {
					$segments[] = self::lang('vendor') ;
					$query['Itemid'] = $jmenu['virtuemart'];
				}
				if (isset($query['virtuemart_vendor_id'])) {
					$segments[] =  self::getVendorName($query['virtuemart_vendor_id']) ;
					unset ($query['virtuemart_vendor_id'] );
				}
				if(!empty($query['Itemid'])){
					unset ($query['virtuemart_vendor_id'] );
					//unset ($query['layout']);

				}
				//unset ($query['limitstart']);
				//unset ($query['limit']);
				break;
			case 'cart';

				$layout = (empty( $query['layout'] )) ? 0 : $query['layout'];
				//vmdebug('Router link to cart',$segments,$query,$jmenu,$layout,$jmenu['cart'][$layout]);
				if(isset( $jmenu['cart'][$layout] )) {
					$query['Itemid'] = $jmenu['cart'][$layout];
				} else if ($layout!=0 and isset($jmenu['cart'][0]) ) {
					$query['Itemid'] = $jmenu['cart'][0];
				} else if ( isset($jmenu['virtuemart']) ) {
					$query['Itemid'] = $jmenu['virtuemart'];
					$segments[] = self::lang('cart') ;

				} else {
					// the worst
					$segments[] = self::lang('cart') ;
				}
				//vmdebug('Router link to cart',$segments, $query);
				break;
			case 'orders';
				if ( isset($jmenu['orders']) ) $query['Itemid'] = $jmenu['orders'];
				else {
					$segments[] = self::lang('orders') ;
					$query['Itemid'] = $jmenu['virtuemart'];
				}
				if ( isset($query['order_number']) ) {
					$segments[] = 'number/'.$query['order_number'];
					unset ($query['order_number'],$query['layout']);
				} else if ( isset($query['virtuemart_order_id']) ) {
					$segments[] = 'id/'.$query['virtuemart_order_id'];
					unset ($query['virtuemart_order_id'],$query['layout']);
				}
				break;

			// sef only view
			default ;
				$segments[] = $view;

			//VmConfig::$vmlang = $oLang;
		}

		//stAn - clean up non sef URL so it's not appended to SEF URL
		if (isset($query['language']) && (isset($query['lang']))) {
			if ($query['language'] === $query['lang']) unset($query['language']);
			if (empty($query['language'])) unset($query['language']);
		}
		if (isset($query['keyword']) && ($query['keyword'] === '')) { unset($query['keyword']); }
		if (isset($query['Itemid']) && (empty($query['Itemid']))) { unset($query['Itemid']); }
		//stAn - we never allow special character in GET query after SEF URL:
		foreach ($query as $k=>$v) {
			$query[$k] = str_replace('`', '', $v);
			if (empty($v)) {
				//we don't want SEF URL to be appended by empty values such as clearCart=0 or limitstart=0 or similar
				unset($query[$k]);
			}
		}

		vmrouterHelper::resetLanguage();
		return $segments;
	}

	/* This function can be slower because is used only one time  to find the real URL*/
	static function parseRoute($segments) {
		//vmrouterHelper::$debug = 1;
		//VmEcho::$_debug = 1;
		$vars = array();
		vmdebug('parse $segments = ',$segments,$_REQUEST);
		if(vmrouterHelper::$debug) vmdebug('virtuemartParseRoute $segments ',$segments);
		//self::setActiveMenu();

		if(!empty(self::$activeMenu->view and !array_key_exists(self::$activeMenu->view,self::$dbview))) {
			$vars['view'] = self::$activeMenu->view;
			vmdebug('parseRoute not vm core view');
			return $vars;
		}

		if (self::$router_disabled) {
			$total = count($segments);
			for ($i = 0; $i < $total; $i=$i+2) {
				if(isset($segments[$i+1])){
					if(isset($segments[$i+1]) and strpos($segments[$i+1],',')!==false){
						$vars[ $segments[$i] ] = explode(',',$segments[$i+1]);
					} else {
						$vars[ $segments[$i] ] = $segments[$i+1];
					}
				}
			}
			if(isset($vars[ 'start'])) {
				$vars[ 'limitstart'] = $vars[ 'start'];
			} else {
				$vars[ 'limitstart'] = 0;
			}
			return $vars;
		}

		if (empty($segments)) {
			return $vars;
		}

		foreach  ($segments as &$value) {
			$value = str_replace(':', '-', $value);
		}

		$splitted = explode(',',end($segments),2);

		if ( self::compareKey($splitted[0] ,'results')){
			array_pop($segments);
			$results = explode('-',$splitted[1],2);
			//Pagination has changed, removed the -1 note by Max Milbers NOTE: Works on j1.5, but NOT j1.7
			// limitstart is swapped by joomla to start ! See includes/route.php
			if ($start = intval($results[0])-1) $vars['limitstart'] = $start;
			else $vars['limitstart'] = 0 ;
			$vars['limit'] = (int)$results[1]-(int)$results[0]+1;

		} else {
			$vars['limitstart'] = 0 ;

		}

		if (empty($segments)) {
			$vars['view'] = 'category';
			$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id ;
			if(!isset($vars['limit'])) $vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_category_id'],$vars['view']);
			return $vars;
		}

		//Translation of the ordering direction is not really useful and costs just energy
		if ( end($segments) == 'dirDesc' or end($segments) == 'dirAsc' ){
			if ( end($segments) == 'dirDesc' ) {
				$vars['dir'] = 'DESC';
			} else {
				$vars['dir'] ='ASC' ;
			}
			array_pop($segments);
			if (empty($segments)) {
				$vars['view'] = 'category';
				$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id ;
				if(!isset($vars['limit'])) $vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_category_id'],$vars['view']);
				return $vars;
			}
		}
		if(vmrouterHelper::$debug) vmdebug('virtuemartParseRoute $segments ',$segments);
		/*$searchText = 'search';
		//if (self::$seo_translate ) {
			$searchText = vmText::_( 'COM_VIRTUEMART_SEF_search' );
		//}

		$searchPre = substr($segments[0],0,strlen($searchText));
		if($searchPre==$searchText){

		//}


		//if ( self::compareKey($segments[0] ,'search') ) {
			$vars['search'] = 'true';
			array_shift($segments);
			if ( !empty ($segments) ) {
				$vars['keyword'] = array_shift($segments);
			}
			$vars['view'] = 'category';
			$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id ;
			$vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_category_id'],$vars['view']);
			vmdebug('my segments checking for search',$segments,$vars);
			if (empty($segments)) return $vars;
		}*/

		$orderby = explode(',',end($segments),2);
		if ( count($orderby) == 2 and self::compareKey($orderby[0] , 'by') ) {
			$vars['orderby'] = self::getOrderingKey($orderby[1]) ;
			// array_shift($segments);
			array_pop($segments);

			if (empty($segments)) {
				$vars['view'] = 'category';
				$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id ;
				if(!isset($vars['limit'])) $vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_category_id'],$vars['view']);
				return $vars;
			}
		}

		if ( $segments[0] == 'product') {
			$vars['view'] = 'product';
			$vars['task'] = $segments[1];
			$vars['tmpl'] = 'component';
			return $vars;
		}

		if ( $segments[0] == 'checkout' or $segments[0] == 'cart' or self::compareKey($segments[0] ,'cart')) {
			$vars['view'] = 'cart';
			if(count($segments) > 1){ // prevent putting value of view variable into task variable by Viktor Jelinek
				$vars['task'] = array_pop($segments);
			}

			if(empty($vars['Itemid'])){
				vmrouterHelper::findCorrectItemid($vars);
			}

			return $vars;
		}

		if (  self::compareKey($segments[0] ,'manufacturer') ) {
			if(!empty($segments[1])){
				array_shift($segments);
				$vars['virtuemart_manufacturer_id'] =  self::getManufacturerId($segments[0]);

			}

			array_shift($segments);
			// OSP 2012-02-29 removed search malforms SEF path and search is performed
			if (empty($segments)) {
				$vars['view'] = 'category';
				$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id ;
				if(empty($vars['limit'])) $vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_manufacturer_id'],'manufacturer');
				return $vars;
			}

		}
		/* added in vm208 */
// if no joomla link: vendor/vendorname/layout
// if joomla link joomlalink/vendorname/layout
		if (  self::compareKey($segments[0] ,'vendor') ) {
			$vars['virtuemart_vendor_id'] =  self::getVendorId($segments[1]);
			// OSP 2012-02-29 removed search malforms SEF path and search is performed
			// $vars['search'] = 'true';
			// this can never happen
			vmdebug('Parsing segements vendor view',$segments);
			if (empty($segments)) {
				$vars['view'] = 'vendor';
				$vars['virtuemart_vendor_id'] = self::$activeMenu->virtuemart_vendor_id ;
				return $vars;
			}

		}


		if (end($segments) == 'modal') {
			$vars['tmpl'] = 'component';
			array_pop($segments);

		}
		if ( self::compareKey(end($segments) ,'askquestion') ) {
			$vars = (array)self::$activeMenu ;
			$vars['task'] = 'askquestion';
			array_pop($segments);

		} elseif ( self::compareKey(end($segments) ,'recommend') ) {
			$vars = (array)self::$activeMenu ;
			$vars['task'] = 'recommend';
			array_pop($segments);

		} elseif ( self::compareKey(end($segments) ,'notify') ) {
			$vars = (array)self::$activeMenu ;
			$vars['layout'] = 'notify';
			array_pop($segments);

		}

		if (empty($segments)) return $vars ;

		// View is first segment now
		$view = $segments[0];
		if ( self::compareKey($view,'orders') || self::$activeMenu->view == 'orders') {
			$vars['view'] = 'orders';
			if ( self::compareKey($view,'orders')){
				array_shift($segments);
			}
			if (empty($segments)) {
				$vars['layout'] = 'list';
			}
			else if (self::compareKey($segments[0],'list') ) {
				$vars['layout'] = 'list';
				array_shift($segments);
			}
			if ( !empty($segments) ) {
				if ($segments[0] =='number') {
					if (isset($segments[1])) $vars['order_number'] = $segments[1];
				} else if(isset($segments[1])) {
					$vars['virtuemart_order_id'] = $segments[1] ;
				}
				$vars['layout'] = 'details';
			}
			if(!isset($vars['limit'])){
				$vars['limit'] = vmrouterHelper::$limit;
			}
			return $vars;
		}
		else if ( self::compareKey($view,'user') || self::$activeMenu->view == 'user') {
			$vars['view'] = 'user';
			if ( self::compareKey($view,'user') ) {
				array_shift($segments);
			}

			if ( !empty($segments) ) {
				if (  self::compareKey($segments[0] ,'editaddresscartBT') ) {
					$vars['addrtype'] = 'BT' ;
					$vars['task'] = 'editaddresscart' ;
				}
				elseif (  self::compareKey($segments[0] ,'editaddresscartST') ) {
					$vars['addrtype'] = 'ST' ;
					$vars['task'] = 'editaddresscart' ;
				}
				elseif (  self::compareKey($segments[0] ,'editaddresscheckoutBT') ) {
					$vars['addrtype'] = 'BT' ;
					$vars['task'] = 'editaddresscheckout' ;
				}
				elseif (  self::compareKey($segments[0] ,'editaddresscheckoutST') ) {
					$vars['addrtype'] = 'ST' ;
					$vars['task'] = 'editaddresscheckout' ;
				}
				elseif (  self::compareKey($segments[0] ,'editaddressST') ) {
					$vars['addrtype'] = 'ST' ;
					$vars['task'] = 'editaddressST' ;
				}
				elseif (  self::compareKey($segments[0] ,'editaddressBT') ) {
					$vars['addrtype'] = 'BT' ;
					$vars['task'] = 'edit' ;
					$vars['layout'] = 'edit' ;      //I think that should be the layout, not the task
				}
				elseif (  self::compareKey($segments[0] ,'edit') ) {
					$vars['layout'] = 'edit' ;      //uncomment and lets test
				}
				elseif (  self::compareKey($segments[0] ,'pluginresponse') ) {
					$vars['view'] = 'pluginresponse' ;
					if(isset($segments[1]))
						$vars['task'] = $segments[1] ;
				}

				else $vars['task'] = $segments[0] ;
			}
			if(!isset($vars['limit'])){
				$vars['limit'] = vmrouterHelper::$limit;
			}
			return $vars;
		}
		else if ( self::compareKey($view,'vendor') || self::$activeMenu->view == 'vendor') {
			$vars['view'] = 'vendor';

			if ( self::compareKey($view,'vendor') ) {
				array_shift($segments);
				if (empty($segments)) return $vars;
			}

			$vars['virtuemart_vendor_id'] =  self::getVendorId($segments[0]);
			array_shift($segments);
			if(!empty($segments)) {
				if ( self::compareKey($segments[0] ,'contact') ) $vars['layout'] = 'contact' ;
				elseif ( self::compareKey($segments[0] ,'tos') ) $vars['layout'] = 'tos' ;
				elseif ( self::compareKey($segments[0] ,'details') ) $vars['layout'] = 'details' ;
			} else {
				$vars['layout'] = vRequest::getCmd('layout','details') ;
			}

			if(!isset($vars['limit'])){
				$vars['limit'] = vmrouterHelper::$limit;
			}
			if(vmrouterHelper::$debug) vmdebug('virtuemartParseRoute return vendor',$vars, $segments);
			return $vars;

		}
		elseif ( self::compareKey($segments[0] ,'pluginresponse') ) {
			$vars['view'] = 'pluginresponse';
			array_shift($segments);
			if ( !empty ($segments) ) {
				$vars['task'] = $segments[0];
				array_shift($segments);
			}
			if ( isset($segments[0]) && $segments[0] == 'modal') {
				$vars['tmpl'] = 'component';
				array_shift($segments);
			}
			return $vars;
		}
		else if ( self::compareKey($view,'cart') || self::$activeMenu->view == 'cart') {
			$vars['view'] = 'cart';
			if ( self::compareKey($view,'cart') ) {
				array_shift($segments);
				if (empty($segments)) return $vars;
			}
			if ( self::compareKey($segments[0] ,'edit_shipment') ) $vars['task'] = 'edit_shipment' ;
			elseif ( self::compareKey($segments[0] ,'editpayment') ) $vars['task'] = 'editpayment' ;
			elseif ( self::compareKey($segments[0] ,'delete') ) $vars['task'] = 'delete' ;
			elseif ( self::compareKey($segments[0] ,'checkout') ) $vars['task'] = 'checkout' ;
			elseif ( self::compareKey($segments[0] ,'orderdone') ) $vars['layout'] = 'orderdone' ;
			else $vars['task'] = $segments[0];
			if(vmrouterHelper::$debug) vmdebug('virtuemartParseRoute return cart',$vars, $segments);
			return $vars;
		}

		else if ( self::compareKey($view,'manufacturers') || self::$activeMenu->view == 'manufacturer') {
			$vars['view'] = 'manufacturer';

			if ( self::compareKey($view,'manufacturers') ) {
				array_shift($segments);
			}

			if (!empty($segments) ) {
				$vars['virtuemart_manufacturer_id'] =  self::getManufacturerId($segments[0]);
				array_shift($segments);
			}
			if ( isset($segments[0]) && $segments[0] == 'modal') {
				$vars['tmpl'] = 'component';
				array_shift($segments);
			}

			if(!isset($vars['limit'])){
				$vars['limit'] = vmrouterHelper::$limit;
			}
			if(vmrouterHelper::$debug) vmdebug('virtuemartParseRoute return manufacturer',$vars, $segments);
			return $vars;
		}


		/*
		 * seo_sufix must never be used in category or router can't find it
		 * eg. suffix as "-suffix", a category with "name-suffix" get always a false return
		 * Trick : YOu can simply use "-p","-x","-" or ".htm" for better seo result if it's never in the product/category name !
		 */
		$last_elem = end($segments);
		$slast_elem = prev($segments);
		if(vmrouterHelper::$debug) vmdebug('ParseRoute no view found yet',$segments, $vars,$last_elem,$slast_elem);
		if ( !empty(self::$seo_sufix_size) and ((substr($last_elem, -(int)self::$seo_sufix_size ) == self::$seo_sufix)
				|| ($last_elem=='notify' && substr($slast_elem, -(int)self::$seo_sufix_size ) == self::$seo_sufix)) ) {

			$vars['view'] = 'productdetails';
			if($last_elem == 'notify') {
				$vars['layout'] = 'notify';
				array_pop( $segments );
			}

			if(!self::$use_id) {
				$product = self::getProductId( $segments, self::$activeMenu->virtuemart_category_id,true );
				$vars['virtuemart_product_id'] = $product['virtuemart_product_id'];
				$vars['virtuemart_category_id'] = $product['virtuemart_category_id'];
				if(vmrouterHelper::$debug) vmdebug('View productdetails, using case !self::$use_id',$vars,$product,self::$activeMenu);
				/*} elseif(isset($segments[1])) {
					$vars['virtuemart_product_id'] = $segments[0];
					$vars['virtuemart_category_id'] = $segments[1];
					vmdebug('View productdetails, using case isset($segments[1]',$vars);*/
			} else {
				if(!empty($segments[0]) and ctype_digit($segments[0]) ){
					$pInt = $segments[0];
				} else if(isset($slast_elem) and ctype_digit($slast_elem)) {
					$pInt = $slast_elem;
				}

				$vars['virtuemart_product_id'] = $pInt;
				if(!empty(self::$activeMenu->virtuemart_category_id)){
					$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id;
				} else {
					$product = VmModel::getModel('product')->getProduct($pInt);
					if($product->canonCatId){
						$vars['virtuemart_category_id'] = $product->canonCatId;
					}
				}
				if(vmrouterHelper::$debug) vmdebug('View productdetails, using case "else", which uses self::$activeMenu->virtuemart_category_id ',$vars);
			}
		}

		if(!isset($vars['virtuemart_product_id'])) {

			//$vars['view'] = 'productdetails';	//Must be commmented, because else we cannot call custom views per extended plugin
			if($last_elem=='notify') {
				$vars['layout'] = 'notify';
				array_pop($segments);
			}
			$product = self::getProductId($segments ,self::$activeMenu->virtuemart_category_id, true);

			//codepyro - removed suffix from router
			//check if name is a product.
			//if so then its a product load the details page
			if(!empty($product['virtuemart_product_id'])) {
				$vars['view'] = 'productdetails';
				$vars['virtuemart_product_id'] = $product['virtuemart_product_id'];
				if(isset($product['virtuemart_category_id'])) {
					$vars['virtuemart_category_id'] = $product['virtuemart_category_id'];
				}
			} else {
				$catId = self::getCategoryId ($last_elem ,self::$activeMenu->virtuemart_category_id);
				if($catId!=false){
					$vars['virtuemart_category_id'] = $catId;
					$vars['view'] = 'category' ;
					if(!isset($vars['limit'])) $vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_category_id'],$vars['view']);
				}
			}
		}

		if (!isset($vars['virtuemart_category_id'])){
			if(vmrouterHelper::$debug) vmdebug('ParseRoute $vars[\'virtuemart_category_id\'] not set',$segments,self::$activeMenu);
			if (!self::$use_id && (self::$activeMenu->view == 'category' ) )  {
				$vars['virtuemart_category_id'] = self::getCategoryId (end($segments) ,self::$activeMenu->virtuemart_category_id);
				$vars['view'] = 'category' ;

			} elseif (isset($segments[0]) && ctype_digit ($segments[0]) || self::$activeMenu->virtuemart_category_id>0 ) {
				$vars['virtuemart_category_id'] = $segments[0];
				$vars['view'] = 'category';

			} elseif (self::$activeMenu->virtuemart_category_id >0 && $vars['view'] != 'productdetails') {
				$vars['virtuemart_category_id'] = self::$activeMenu->virtuemart_category_id ;
				$vars['view'] = 'category';

			} elseif ($id = self::getCategoryId (end($segments) ,self::$activeMenu->virtuemart_category_id )) {

				// find corresponding category . If not, segment 0 must be a view
				$vars['virtuemart_category_id'] = $id;
				$vars['view'] = 'category' ;
			}
			if(!isset($vars['virtuemart_category_id'])) {
				$vars['error'] = '404';
				$vars['virtuemart_category_id'] = -2;
			}
			if(empty($vars['view'])) $vars['view'] = 'category';

			if(!isset($vars['limit'])) $vars['limit'] = vmrouterHelper::getLimitByCategory($vars['virtuemart_category_id'],$vars['view']);
		}
		if (!isset($vars['view'])){
			$vars['view'] = $segments[0] ;
			if ( isset($segments[1]) ) {
				$vars['task'] = $segments[1] ;
			}
		}

		if(vmrouterHelper::$debug){
			vmdebug('my vars from router',$vars);
		}
		return $vars;
	}

	static public function getLimitByCategory($catId, $view = 'category'){

		static $c = array();

		if(empty($c[$catId][$view])){

			$initial = VmConfig::get('llimit_init_FE', 24);
			if($view!='manufacturer'){	//Take care, this could be the categor view, just displaying manufacturer products
				$catModel = VmModel::getModel('category');
				$cat = $catModel->getCategory($catId);
				if(!empty($cat->limit_list_initial)){
					$initial = $cat->limit_list_initial;
					if(vmrouterHelper::$debug) vmdebug('limit by category '.$view.' '.$catId.' '.$cat->limit_list_initial);
				}
			}

			$app = JFactory::getApplication();
			$c[$catId][$view] = $app->getUserStateFromRequest('com_virtuemart.category.limit', 'limit',$initial, 'int');
		}
		self::$limit = $c[$catId][$view];

		return self::$limit;
	}

	static public function updateCache(){
		//$cache = VmConfig::getCache('com_virtuemart_cats_route', '');
		if(vmrouterHelper::$updateCache and self::$cats_route_cache!==null){
			vmdebug('storing router $_catRoute cache');
			self::$cats_route_cache->store(self::$_catRoute, 'com_virtuemart_cats_route');
		}
		//return true;
	}

	/* Get Joomla menu item and the route for category */
	static public function getCategoryRoute($catId, $manId, $wMenuAlias = false){

		$key = $catId. VmConfig::$vmlang . $manId/*.'r'.(int)$wMenuAlias*/;
		if (!isset(self::$_catRoute[$key])){

			vmrouterHelper::$updateCache = true;
			$category = new stdClass();
			$category->route = '';
			$category->Itemid = 0;
			$menuCatid = 0 ;
			$ismenu = false ;
			$catModel = VmModel::getModel('category');

			// control if category is joomla menu
			/*if (isset(self::$menu['virtuemart_category_id'][$catId][$manId])) {
				$ismenu = true;
				$category->Itemid = self::$menu['virtuemart_category_id'][$catId][$manId];
			} else */
			vmdebug('my menu ',self::$menu);
			if (isset(self::$menu['virtuemart_category_id'])) {
				if (isset( self::$menu['virtuemart_category_id'][$catId][$manId])) {
					$ismenu = true;
					$category->Itemid = self::$menu['virtuemart_category_id'][$catId][$manId] ;
					//if ( self::$use_id ) $category->route = $catId.'/';


				} else {
					$catModel->categoryRecursed = 0;
					$CatParentIds = $catModel->getCategoryRecurse($catId,0) ;
					/* control if parent categories are joomla menu */
					foreach ($CatParentIds as $CatParentId) {
						// No ? then find the parent menu categorie !
						if (isset( self::$menu['virtuemart_category_id'][$CatParentId][$manId]) ) {
							$category->Itemid = self::$menu['virtuemart_category_id'][$CatParentId][$manId] ;
							$menuCatid = $CatParentId;
							//$ismenu = true;
							break;
						}
					}
				}
			}

			if ($ismenu==false) {
				if ( self::$use_id ) $category->route = $catId.'/';
				if (!isset (self::$CategoryName[self::$slang][$catId])) {
					self::$CategoryName[self::$slang][$catId] = self::getCategoryNames($catId, $menuCatid );
				}
				$category->route .= self::$CategoryName[self::$slang][$catId] ;
				if ($menuCatid == 0  and !empty(self::$menu['virtuemart'])) $category->Itemid = self::$menu['virtuemart'] ;
			} /*else if ($wMenuAlias) {
				$menuItem = self::$jMenuItems->getItem($category->Itemid);
				if(!empty($menuItem)){
					vmdebug('my menuItem',$menuItem);
					$category->route .= $menuItem->alias ;
				}
			}*/
			self::$_catRoute[$key] = $category;

		}

		return self::$_catRoute[$key] ;
	}

	/*get url safe names of category and parents categories  */
	static public function getCategoryNames($catId,$catMenuId=0){

		static $categoryNamesCache = array();
		$strings = array();

		$catModel = VmModel::getModel('category');

		if(self::$full) {
			$catModel->categoryRecursed = 0;
			if($parent_ids = $catModel->getCategoryRecurse($catId,$catMenuId)){

				$parent_ids = array_reverse($parent_ids) ;
			}
		} else {
			$parent_ids[] = $catId;
		}

		//vmdebug('Router getCategoryNames getCategoryRecurse finished '.$catId,self::$slang,$parent_ids);
		foreach ($parent_ids as $id ) {
			if(!isset($categoryNamesCache[self::$slang][$id])){

				$cat = $catModel->getCategory($id,0);

				if(!empty($cat->published)){
					$categoryNamesCache[self::$slang][$id] = $cat->slug;
					$strings[] = $cat->slug;

				} else if(!empty($id)){
					//vmdebug('router.php getCategoryNames set 404 for id '.$id,$cat);
					//$categoryNamesCache[self::$slang][$id] = '404';
					//$strings[] = '404';
				}
			} else {
				$strings[] = $categoryNamesCache[self::$slang][$id];
			}
		}

		if(function_exists('mb_strtolower')){
			return mb_strtolower(implode ('/', $strings ) );
		} else {
			return strtolower(implode ('/', $strings ) );
		}
	}

	/** return id of categories
	 * $names are segments
	 * $virtuemart_category_ids is joomla menu virtuemart_category_id
	 */
	static public function getCategoryId($slug,$catId ){

		$catIds = self::getFieldOfObjectWithLangFallBack('#__virtuemart_categories_','virtuemart_category_id','virtuemart_category_id','slug',$slug);
		if (!$catIds) {
			$catIds = $catId;
		}

		return $catIds;
	}

	static public $productNamesCache = array();

	/* Get URL safe Product name */
	static public function getProductName($id){


		static $suffix = '';
		static $prTable = false;
		if(!isset(self::$productNamesCache[self::$slang][$id])){
			if(self::$use_seo_suffix){
				$suffix = self::$seo_sufix;
			}
			if(!$prTable){
				$prTable = VmTable::getInstance('products');
			}
			$i = 0;
			//vmSetStartTime('Routerloads');
			if(!isset(self::$productNamesCache[self::$slang][$id])){
				$prTable->_langTag = VmConfig::$vmlang;
				$prTable->load($id);
//vmdebug('getProductName '.self::$slang, $prTable->_langTag,VmConfig::$vmlang,$prTable->slug);
				//a product cannot derive a slug from a parent product
				//if(empty($prTable->slug) and $prTable->product_parent_id>0 ){}

				if(!$prTable or empty($prTable->slug)){
					self::$productNamesCache[self::$slang][$id] = false;
				} else {
					self::$productNamesCache[self::$slang][$id] = $prTable->slug.$suffix;
				}
			}

			//*/

			/*$virtuemart_shoppergroup_ids = VirtueMartModelProduct::getCurrentUserShopperGrps();
			$checkedProductKey= VirtueMartModelProduct::checkIfCached($id,TRUE, FALSE, TRUE, 1, $virtuemart_shoppergroup_ids,0);
			if($checkedProductKey[0]){
				if(VirtueMartModelProduct::$_products[$checkedProductKey[1]]===false){
					self::$productNamesCache[self::$slang][$id] = false;
				} else if(isset(VirtueMartModelProduct::$_products[$checkedProductKey[1]])){
					self::$productNamesCache[self::$slang][$id] = VirtueMartModelProduct::$_products[$checkedProductKey[1]]->slug.$suffix;
				}
			}

			if(!isset(self::$productNamesCache[self::$slang][$id])){
				$pModel = VmModel::getModel('product');
				//Adding shoppergroup could be needed
				$pr = $pModel->getProduct($id, TRUE, FALSE, TRUE, 1, $virtuemart_shoppergroup_ids,0);
				if(!$pr or empty($pr->slug)){
					self::$productNamesCache[self::$slang][$id] = false;
				} else {
					self::$productNamesCache[self::$slang][$id] = $pr->slug.$suffix;
				}
			}//*/
			//vmTime('Router load  '.$id,'Routerloads');
		}

		return self::$productNamesCache[self::$slang][$id];
	}

	static $counter = 0;
	/* Get parent Product first found category ID */
	static public function getParentProductcategory($id){

		static $parProdCat= array();
		static $catPar = array();
		if(!isset($parProdCat[$id])){
			if(!class_exists('VirtueMartModelProduct')) VmModel::getModel('product');
			$parent_id = VirtueMartModelProduct::getProductParentId($id);

			//If product is child then get parent category ID
			if ($parent_id and $parent_id!=$id) {

				if(!isset($catPar[$parent_id])){

					$checkedProductKey= VirtueMartModelProduct::checkIfCached($parent_id);

					if($checkedProductKey[0]){
						if(VirtueMartModelProduct::$_products[$checkedProductKey[1]]===false){
							//$parentCache[$product_id] = false;
						} else if(isset(VirtueMartModelProduct::$_products[$checkedProductKey[1]]->virtuemart_category_id)){
							$parProdCat[$id] = $catPar[$parent_id] = VirtueMartModelProduct::$_products[$checkedProductKey[1]]->virtuemart_category_id;
						}
					} else {

						$ids = VirtueMartModelProduct::getProductCategoryIds($parent_id);
						if(isset($ids[0])){
							$parProdCat[$id] = $catPar[$parent_id] = $ids[0]['virtuemart_category_id'];
						} else {
							$parProdCat[$id] = $catPar[$parent_id] = false;
						}
						//->loadResult();
						//vmdebug('Router getParentProductcategory executed sql for '.$id, $parProdCat[$id]);
					}

				} else {
					$parProdCat[$id] = $catPar[$parent_id];
					//vmdebug('getParentProductcategory $catPar[$parent_id] Cached ',$id );
				}

				//When the child and parent id is the same, this creates a deadlock
				//add $counter, dont allow more then 10 levels
				if (!isset($parProdCat[$id]) or !$parProdCat[$id]){
					self::$counter++;
					if(self::$counter<10){
						self::getParentProductcategory($parent_id) ;
					}
				}
			} else {
				$parProdCat[$id] = false;
			}

			self::$counter = 0;
		}

		if(!isset($parProdCat[$id])) $parProdCat[$id] = 0;
		return $parProdCat[$id] ;
	}


	/* get product and category ID */
	static public function getProductId($names,$catId = NULL, $seo_sufix = true ){
		$productName = array_pop($names);
		if(self::$use_seo_suffix and !empty(self::$seo_sufix_size) ){
			if(substr($productName, -(int)self::$seo_sufix_size ) !== self::$seo_sufix) {
				return array('virtuemart_product_id' =>0, 'virtuemart_category_id' => false);
			}
			$productName =  substr($productName, 0, -(int)self::$seo_sufix_size );
		}

		static $prodIds = array();
		$categoryName = array_pop($names);

		$hash = base64_encode($productName.VmConfig::$vmlang);

		if(!isset($prodIds[$hash])){
			$prodIds[$hash]['virtuemart_product_id'] = self::getFieldOfObjectWithLangFallBack('#__virtuemart_products_', 'virtuemart_product_id', 'virtuemart_product_id', 'slug', $productName);
			if(empty($categoryName) and empty($catId)){
				$prodIds[$hash]['virtuemart_category_id'] = false;
			} else if(!empty($categoryName)){
				$prodIds[$hash]['virtuemart_category_id'] = self::getCategoryId($categoryName,$catId ) ;
			} else {
				$prodIds[$hash]['virtuemart_category_id'] = false;
			}
		}

		return $prodIds[$hash] ;
	}

	/* Get URL safe Manufacturer name */
	static public function getManufacturerName($manId ){

		return self::getFieldOfObjectWithLangFallBack('#__virtuemart_manufacturers_','virtuemart_manufacturer_id','slug','virtuemart_manufacturer_id',(int)$manId);
	}

	/* Get Manufacturer id */
	static public function getManufacturerId($slug ){

		return self::getFieldOfObjectWithLangFallBack('#__virtuemart_manufacturers_','virtuemart_manufacturer_id','virtuemart_manufacturer_id','slug',$slug);
	}
	/* Get URL safe Manufacturer name */
	static public function getVendorName($virtuemart_vendor_id ){

		return self::getFieldOfObjectWithLangFallBack('#__virtuemart_vendors_','virtuemart_vendor_id','slug','virtuemart_vendor_id',(int)$virtuemart_vendor_id);
	}
	/* Get Manufacturer id */
	static public function getVendorId($slug ){

		return self::getFieldOfObjectWithLangFallBack('#__virtuemart_vendors_','virtuemart_vendor_id','virtuemart_vendor_id','slug',$slug);
	}

	static public function getFieldOfObjectWithLangFallBack($table, $idname, $name, $wherename, $value){

		static $ids = array();
		$value = trim($value);
		$hash = substr($table,14,-1).self::$slang.$wherename.$value;
		if(isset($ids[$hash])){
			//vmdebug('getFieldOfObjectWithLangFallBack return cached',$hash);
			return $ids[$hash];
		}

		//It is useless to search for an entry with empty where value.
		if(empty($value)) return false;

		$select = implode(', ',VmModel::joinLangSelectFields(array($name), true));
		$joins = implode(' ',VmModel::joinLangTables(substr($table,0,-1),'i',$idname,'FROM'));
		$wherenames = implode(', ',VmModel::joinLangSelectFields(array($wherename), false));

		$q = 'SELECT '.$select.' '.$joins.' WHERE '.$wherenames.' = "'.self::$_db->escape($value).'"';
		$useFb = vmLanguage::getUseLangFallback();
		if(($useFb)){
			$q .= ' OR ld.'.$wherename.' = "'.self::$_db->escape($value).'"';
		}
		$useFb2 = vmLanguage::getUseLangFallbackSecondary();
		if(($useFb2)){
			$q .= ' OR ljd.'.$wherename.' = "'.self::$_db->escape($value).'"';
		}
		self::$_db->setQuery($q);
		try{
			$ids[$hash] = self::$_db->loadResult();
		} catch (Exception $e){
			vmError('Error in slq router.php function getFieldOfObjectWithLangFallBack '.$e->getMessage());
		}

		if(!isset($ids[$hash])){
			$ids[$hash] = false;
			if(self::$debug){
				vmEcho::$logDebug = 1;
				vmdebug('Router getFieldOfObjectWithLangFallBack Could not find '.$q );
				vmEcho::$logDebug = 0;
			}
		}
		//vmdebug('getFieldOfObjectWithLangFallBack my query ',str_replace('#__',self::$_db->getPrefix(),self::$_db->getQuery()),$ids[$hash]);
		return $ids[$hash];
	}

	/**
	 * Checks Itemid if it is a vm itemid and allowed to visit
	 * @return bool
	 */
	static public function checkItemid($id){

		static $res = array();
		if(isset($res[$id])) {
			return $res[$id];
		} else {

			$q = 'SELECT * FROM `#__menu` WHERE `link` like "index.php?option=com_virtuemart%" and (language="*" or language = "'.vmLanguage::$jSelLangTag.'" )'.self::$andAccess;

			$q .= ' and `id` = "'.(int)$id.'" ';

			$q .= ' ORDER BY `language` DESC';

			self::$_db->setQuery($q);
			$r = self::$_db->loadResult();
			$res[$id] = boolval($r);
		}

		if(vmrouterHelper::$debug) vmdebug('checkItemid query and result ', $q, $res);
		return $res[$id];
	}

	/* Set self::$menu with the Item ID from Joomla Menus */
	static public function setMenuItemId(){

		$home 	= false ;
		static $mCache = array();

		$jLangTag = self::$slang;


		$h = $jLangTag.self::$authStr;
		if(self::$byMenu){
			$h .= 'i'.self::$Itemid;
		}

		if(isset($mCache[$h])){
			self::$menu = $mCache[$h];
			//vmdebug('Found cached menu',$h.self::$Itemid);
			return;
		} else {
			//vmdebug('Existing cache',$h.self::$Itemid,$mCache);
		}

		//$db			= JFactory::getDBO();

		$q = 'SELECT * FROM `#__menu` WHERE `link` like "index.php?option=com_virtuemart%" and (language="*" or language = "'.$jLangTag.'" ) '.self::$andAccess;

		if(self::$byMenu === 1 and !empty(self::$Itemid)) {
			$q .= ' and `menutype` = (SELECT `menutype` FROM `#__menu` WHERE `id` = "'.self::$Itemid.'") ';
		}
		$q .= ' ORDER BY `language` DESC';
		self::$_db->setQuery($q);
		$menuVmitems = self::$_db->loadObjectList();
		//VmConfig::$_debug = 1;
		//vmdebug('setMenuItemId $q',$q,$menuVmitems);
		$homeid =0;

		self::$menu = array();
		if(empty($menuVmitems)){
			$mCache[$h] = false;
			if(vmrouterHelper::$debug) vmdebug('my $menuVmitems ',$q,$menuVmitems);
			vmLanguage::loadJLang('com_virtuemart', true);
			vmWarn(vmText::_('COM_VIRTUEMART_ASSIGN_VM_TO_MENU'));
		} else {
			//vmdebug('my menuVmItems',self::$template,$menuVmitems);
			// Search  Virtuemart itemID in joomla menu
			foreach ($menuVmitems as $item)	{

				$linkToSplit= explode ('&',$item->link);

				$link =array();
				foreach ($linkToSplit as $tosplit) {
					$splitpos = strpos($tosplit, '=');
					$link[ (substr($tosplit, 0, $splitpos) ) ] = substr($tosplit, $splitpos+1);
				}

				//This is fix to prevent entries in the errorlog.
				if(!empty($link['view'])){
					$view = $link['view'] ;
					if (array_key_exists($view,self::$dbview) ){
						$dbKey = self::$dbview[$view];
					}
					else {
						$dbKey = false ;
					}

					if($dbKey){
						if($dbKey=='category'){
							$catId = empty($link['virtuemart_category_id'])? 0:$link['virtuemart_category_id'];
							$manId = empty($link['virtuemart_manufacturer_id'])? 0:$link['virtuemart_manufacturer_id'];

							if(!isset(self::$menu ['virtuemart_'.$dbKey.'_id'] [$catId] [$manId])){
								self::$menu ['virtuemart_'.$dbKey.'_id'] [$catId] [$manId] = $item->id;
							} else {
								//vmdebug('This menu item exists two times',$item,self::$template->id);
								if($item->template_style_id==self::$template->id){
									self::$menu ['virtuemart_'.$dbKey.'_id'] [$catId] [$manId]= $item->id;

								}
							}

						} else if ( isset($link['virtuemart_'.$dbKey.'_id']) ){
							if(!isset(self::$menu['virtuemart_'.$dbKey.'_id'][ $link['virtuemart_'.$dbKey.'_id'] ])){
								self::$menu['virtuemart_'.$dbKey.'_id'][ $link['virtuemart_'.$dbKey.'_id'] ] = $item->id;
							} else {
								//vmdebug('This menu item exists two times',$item,self::$template->id);
								if($item->template_style_id==self::$template->id){
									self::$menu['virtuemart_'.$dbKey.'_id'][ $link['virtuemart_'.$dbKey.'_id'] ] = $item->id;
								}
							}
						} else if ( $dbKey == 'cart' ){
							$layout = empty($link['layout'])? 0:$link['layout'];
							if(!isset(self::$menu[$dbKey][$layout])){
								self::$menu[$dbKey][$layout] = $item->id;
							} else {
								//vmdebug('This menu item exists two times',$item,self::$template->id);
								if($item->template_style_id==self::$template->id){
									self::$menu[$dbKey][$layout] = $item->id;
								}
							}
						} else {
							if(!isset(self::$menu[$dbKey])){
								self::$menu[$dbKey] = $item->id;
							} else {
								//vmdebug('This menu item exists two times',$item,self::$template->id);
								if($item->template_style_id==self::$template->id){
									self::$menu[$dbKey] = $item->id;
								}
							}
						}
					}

					elseif ($home == $view ) continue;
					else {
						if(!isset(self::$menu[$view])){
							self::$menu[$view]= $item->id ;
						} else {
							//vmdebug('This menu item exists two times',$item,self::$template->id);
							if($item->template_style_id==self::$template->id){
								self::$menu[$view]= $item->id ;
							}
						}
					}

					if ((int)$item->home === 1) {
						$home = $view;
						$homeid = $item->id;
					}
				} else {
					static $msg = array();
					$id = empty($item->id)? '0': $item->id;
					if(empty($msg[$id])){
						if(vmrouterHelper::$debug) vmdebug('my item with empty $link["view"]',$item);
						$msg[$id] = 1;
					}

					//vmError('$link["view"] is empty');
				}
			}
			$mCache[$h] = self::$menu;

			//I wonder if this still makes sense
			if(self::$byMenu){
				foreach ($menuVmitems as $item)	{
					if(self::$Itemid!=$item->id){
						$mCache[$h.$item->id] = &$mCache[$h.self::$Itemid];
					}
				}
			}

		}

		if ( !isset( self::$menu['virtuemart']) or !isset(self::$menu['virtuemart_category_id'][0])) {

			if (!isset (self::$menu['virtuemart_category_id'][0][0]) ) {
				self::$menu['virtuemart_category_id'][0][0] = $homeid;
			}
			// init unsetted views  to defaut front view or nothing(prevent duplicates routes)
			if ( !isset( self::$menu['virtuemart']) ) {
				if (isset (self::$menu['virtuemart_category_id'][0][0]) ) {
					self::$menu['virtuemart'] = self::$menu['virtuemart_category_id'][0][0] ;
				} else self::$menu['virtuemart'] = $homeid;
			}
		}
		if(self::$debug)vmdebug('setMenuItemId',self::$menu);
		$mCache[$h] = self::$menu;
	}

	static $jMenuItems = null;

	/* Set self::$activeMenu to current Item ID from Joomla Menus */
	static public function setActiveMenu(){
		if (self::$activeMenu === null ) {

			$app		= JFactory::getApplication();
			self::$jMenuItems		= $app->getMenu('site');

			self::$rItemid = (int)vRequest::getInt('Itemid',0, $_REQUEST);
			if(!empty(self::$query['Itemid'])){
				self::$Itemid = (int)self::$query['Itemid'];
			} else {
				self::$Itemid = self::$rItemid;
			}
			if(vmrouterHelper::$debug) vmdebug('setActiveMenu',self::$Itemid,self::$rItemid);
			$menuItem = false;
			if (self::$Itemid ) {
				$menuItem = self::$jMenuItems->getItem(self::$Itemid);
			} else {
				$menuItem = self::$jMenuItems->getActive();
				if($menuItem){
					self::$Itemid = $menuItem->id;
				}
				if(vmrouterHelper::$debug) vmdebug('setActiveMenu by getActive',self::$Itemid);
			}

			if(!$menuItem){
				if(vmrouterHelper::$debug) vmdebug('There is no menu item',$menuItem);
			}
			self::$activeMenu = new stdClass();
			self::$activeMenu->view			= (empty($menuItem->query['view'])) ? null : $menuItem->query['view'];
			self::$activeMenu->virtuemart_category_id	= (empty($menuItem->query['virtuemart_category_id'])) ? 0 : $menuItem->query['virtuemart_category_id'];
			self::$activeMenu->virtuemart_product_id	= (empty($menuItem->query['virtuemart_product_id'])) ? null : $menuItem->query['virtuemart_product_id'];
			self::$activeMenu->virtuemart_manufacturer_id	= (empty($menuItem->query['virtuemart_manufacturer_id'])) ? null : $menuItem->query['virtuemart_manufacturer_id'];
			/* added in 208 */
			self::$activeMenu->virtuemart_vendor_id	= (empty($menuItem->query['virtuemart_vendor_id'])) ? null : $menuItem->query['virtuemart_vendor_id'];

			self::$activeMenu->component	= (empty($menuItem->component)) ? null : $menuItem->component;
		}

	}

	static public function findCorrectItemid(&$query){

		if( isset($query['view']) ) {
			$view = $query['view'];
		} else {
			$view = '';
		}

		if(empty(self::$menu)) self::setMenuItemId();

		if ($view == 'category' or $view == 'productdetails') {
			$catId = empty($query['virtuemart_category_id'])? 0:(int)$query['virtuemart_category_id'];
			$manId = empty($query['virtuemart_manufacturer_id'])? 0:(int)$query['virtuemart_manufacturer_id'];


			if(isset(self::$menu['virtuemart_category_id'][$catId][$manId])) {
				$query['Itemid'] = self::$menu['virtuemart_category_id'][$catId][$manId];
			} else {
				/*if($categoryRoute===null)*/ $categoryRoute = self::getCategoryRoute($catId,$manId);
				//http://forum.virtuemart.net/index.php?topic=121642.0

				if (!empty($categoryRoute->Itemid)) {
					$query['Itemid'] = $categoryRoute->Itemid;
				} else if (!empty(self::$menu['virtuemart'])) {
					$query['Itemid'] = self::$menu['virtuemart'];
				}
			}
			if ( !empty(self::$menu['category']) ){
				vmdebug('Itemid for jmenu category?');
				$query['Itemid'] = self::$menu['category'];
			}
			//vmdebug('findCorrectItemid view = category '.$catId, $query['Itemid'], self::$menu);
			if ($view == 'productdetails') {
				if (!empty($query['virtuemart_product_id']) and isset(self::$menu['virtuemart_product_id']) and isset(self::$menu['virtuemart_product_id'][ $query['virtuemart_product_id'] ] ) ) {
					$query['Itemid'] = self::$menu['virtuemart_product_id'][$query['virtuemart_product_id']];
				}
			}
		}
		else if ($view == 'cart') {

			$layout = (empty( $query['layout'] )) ? 0 : $query['layout'];
			if(empty($layout) ) (empty( $query['task'] )) ? 0 : $query['task'];

			if(isset( self::$menu['cart'][$layout] )) {
				$query['Itemid'] = self::$menu['cart'][$layout];
			} else if ($layout!=0 and isset(self::$menu['cart'][0]) ) {
				$query['Itemid'] = self::$menu['cart'][0];
			} else if ( isset(self::$menu['virtuemart']) ) {
				$query['Itemid'] = self::$menu['virtuemart'];
			}
		}
		/*else if ($view == 'invoice') {

		}*/
		else if ($view == 'manufacturer') {
			if(isset($query['virtuemart_manufacturer_id'])) {
				if (isset(self::$menu['virtuemart_manufacturer_id'][ $query['virtuemart_manufacturer_id'] ] ) ) {
					$query['Itemid'] = self::$menu['virtuemart_manufacturer_id'][$query['virtuemart_manufacturer_id']];
				} else {
					if ( isset(self::$menu['manufacturer']) ) $query['Itemid'] = self::$menu['manufacturer'];
					else $query['Itemid'] = self::$menu['virtuemart'];
				}
			} else {
				if ( isset(self::$menu['manufacturer']) ) $query['Itemid'] = self::$menu['manufacturer'];
				else $query['Itemid'] = self::$menu['virtuemart'];
			}
		}
		else if ($view == 'orders') {
			if ( isset(self::$menu['orders']) ) $query['Itemid'] = self::$menu['orders'];
			else {
				$query['Itemid'] = self::$menu['virtuemart'];
			}
		}
		/*else if ($view == 'productdetails') {
			if (!empty($query['virtuemart_product_id']) and isset(self::$menu['virtuemart_product_id']) and isset(self::$menu['virtuemart_product_id'][ $query['virtuemart_product_id'] ] ) ) {
				$query['Itemid'] = self::$menu['virtuemart_product_id'][$query['virtuemart_product_id']];
			}
		}*/
		else if ($view == 'user') {
			if ( isset(self::$menu['user'])) $query['Itemid'] = self::$menu['user'];
			else {
				$query['Itemid'] = self::$menu['virtuemart'];
			}
		} else if ($view == 'vendor') {
			if(isset($query['virtuemart_vendor_id'])) {
				if (isset(self::$menu['virtuemart_vendor_id'][ $query['virtuemart_vendor_id'] ] ) ) {
					$query['Itemid'] = self::$menu['virtuemart_vendor_id'][$query['virtuemart_vendor_id']];
				} else {
					if ( isset(self::$menu['vendor']) ) {
						$query['Itemid'] = self::$menu['vendor'];
					} else {
						$query['Itemid'] = self::$menu['virtuemart'];
					}
				}
			} else if ( isset(self::$menu['vendor']) ) {
				$query['Itemid'] = self::$menu['vendor'];
			} else {
				$query['Itemid'] = self::$menu['virtuemart'];
			}

		}

		if(empty($query['Itemid']) and isset (self::$menu['virtuemart'])){
			$query['Itemid'] = self::$menu['virtuemart'] ;
		}

		if(isset($query['Itemid'])) return $query['Itemid']; else return false;

	}

	static public function findCorrectItemidBySQL($query, $tag){

		static $andAccess = null;

		if($andAccess === null){
			$user = JFactory::getUser();
			$auth = array_unique($user->getAuthorisedViewLevels());
			$andAccess = ' AND client_id=0 AND published=1 AND ( access="' . implode ('" OR access="', $auth) . '" ) ';
		}

		$like = '';
		$cmds = array('option', 'view', 'task', 'layout');
		$vm=0;
		foreach($cmds as $cmd){
			if(isset($query[$cmd])){
				$like .= '&'.$cmd.'='.$query[$cmd];
			}
			/*if( '' !== $v = vRequest::getCmd($cmd, '')){
				$like .= '&'.$cmd.'='.$v;
			}*/
		}

		$option = isset($query['option'])? $query['option'] : vRequest::getCmd('option', '');
		$view =  isset($query['view'])? $query['view'] : vRequest::getCmd('view', '');

		if($option=='com_virtuemart'){
			$ints = array();
			if($view == 'category'){
				$ints = array('virtuemart_category_id','virtuemart_manufacturer_id');
			} else if($view == 'productdetails'){
				$ints = array('virtuemart_product_id');
			}
		} else {
			$ints = array('id');
		}

		foreach($ints as $cmd){
			if(isset($query[$cmd])){
				$like .= '&'.$cmd.'='.$query[$cmd];
			} else {
				$like .= '&'.$cmd.'=0';
				//vmdebug('The $cmd '.$cmd.' was not in the query');
			}

		}

		if($like!==''){
			$like = '`link` like "index.php?'.substr($like,1).'%"';
		} else {
			$like = '`home`="1"';
		}

		$q = 'SELECT id FROM `#__menu` WHERE '.$like.' and (language="*" or language = "'.$tag.'" )'.$andAccess;

		//$q .= ' and `id` = "'.(int)$id.'" ';

		$q .= ' ORDER BY `language` DESC';
		$h = md5($q);

		static $c = array();

		if(isset($c[$h])){
			if(vmrouterHelper::$debug) {

				vmdebug('Found CACHED itemid '.$tag,$c[$h]);
			}

			return $c[$h];
		} else {
			$db = JFactory::getDbo();
			$db->setQuery($q);
			$c[$h] = $db->loadResult();
			//VmConfig::$_debug = true;vmrouterHelper::$debug=true;
			vmdebug('findCorrectItemidBySQL use as like '.vmLanguage::$currLangTag.' '.$like, $c[$h]);
			if(!$c[$h]){
				if($view == 'productdetails'){
					$query['view'] = 'category';
					if(vmrouterHelper::$debug) vmdebug('Productdetails Check with view category',$query);
					return static::findCorrectItemidBySQL($query,$tag);
				} else if($view == 'category' and isset($query['virtuemart_category_id']) and !empty($query['virtuemart_category_id'])){
					$query['virtuemart_category_id'] = 0;
					if(vmrouterHelper::$debug) vmdebug('Check with empty virtuemart_category_id',$query);
					return static::findCorrectItemidBySQL($query,$tag);
				}
			} else {
				if(vmrouterHelper::$debug) vmdebug('Found as new itemid for '.$tag.' '.$like,$q,$c[$h]);
			}

			return $c[$h];

		}
	}

	/*
	 * Get language key or use $key in route
	 */
	static public function lang($key) {
		if (self::$seo_translate ) {
			$jtext = (strtoupper( $key ) );
			if (vmText::$language->hasKey('COM_VIRTUEMART_SEF_'.$jtext) ){
				return vmText::_('COM_VIRTUEMART_SEF_'.$jtext);
			}
		}

		return $key;
	}

	/*
	 * revert key or use $key in route
	 */
	static public function getOrderingKey($key) {

		if (strpos($key, '.') !== false) {
			$xa = explode('.', $key);
			$key = $xa[1];
			$key = str_replace('`', '', $key);
		}

		if (self::$seo_translate ) {
			if (self::$orderings == null) {
				self::$orderings = array(
					'virtuemart_product_id'=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_ID'),
					'product_sku'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_SKU'),
					'product_price'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_PRICE'),
					'category_name'		=> vmText::_('COM_VIRTUEMART_SEF_CATEGORY_NAME'),
					'category_description'=> vmText::_('COM_VIRTUEMART_SEF_CATEGORY_DESCRIPTION'),
					'mf_name' 			=> vmText::_('COM_VIRTUEMART_SEF_MF_NAME'),
					'product_s_desc'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_S_DESC'),
					'product_desc'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_DESC'),
					'product_weight'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_WEIGHT'),
					'product_weight_uom'=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_WEIGHT_UOM'),
					'product_length'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_LENGTH'),
					'product_width'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_WIDTH'),
					'product_height'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_HEIGHT'),
					'product_lwh_uom'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_LWH_UOM'),
					'product_in_stock'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_IN_STOCK'),
					'low_stock_notification'=> vmText::_('COM_VIRTUEMART_SEF_LOW_STOCK_NOTIFICATION'),
					'product_available_date'=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_AVAILABLE_DATE'),
					'product_availability'  => vmText::_('COM_VIRTUEMART_SEF_PRODUCT_AVAILABILITY'),
					'product_special'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_SPECIAL'),
					'created_on' 		=> vmText::_('COM_VIRTUEMART_SEF_CREATED_ON'),
					// 'p.modified_on' 		=> vmText::_('COM_VIRTUEMART_SEF_MDATE'),
					'product_name'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_NAME'),
					'product_sales'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_SALES'),
					'product_unit'		=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_UNIT'),
					'product_packaging'	=> vmText::_('COM_VIRTUEMART_SEF_PRODUCT_PACKAGING'),
					'intnotes'			=> vmText::_('COM_VIRTUEMART_SEF_INTNOTES'),
					'pc.ordering' => vmText::_('COM_VIRTUEMART_SEF_ORDERING')
				);
			}

			if ($result = array_search($key,self::$orderings )) {
				$result = str_replace('`', '', $result);
				return $result;
			}
		}

		$key = str_replace('`', '', $key);
		return $key;
	}

	static public function getLanguageTagBySefTag($lTag) {

		static $langs = null;
		if($langs===null){
			$langs = JLanguageHelper::getLanguages('sef');
			//vmdebug('my langs in router '.$lTag,$langs);
		}
		static $langTags = array();

		if(isset($langTags[$lTag])) {
			return $langTags[$lTag];
		} else {
			foreach ($langs as $langTag => $language) {
				if ($language->lang_code == $lTag) {
					$langTags[$lTag] = $language->lang_code;
					break;
				}
			}
		}
		//vmdebug('getLanguageTagBySefTag',$lTag,$langTags[$lTag]);
		if(isset($langTags[$lTag])) {
			return $langTags[$lTag];
		} else return false;
	}

	static protected function resetLanguage(){
		//Reset language of the router helper in case
		if(VmLanguage::$jSelLangTag!=VmLanguage::$currLangTag){
			//vmdebug('Reset language to '.VmLanguage::$jSelLangTag);
			vmLanguage::setLanguageByTag(VmLanguage::$jSelLangTag, false);
			self::$slang = false;//VmLanguage::$currLangTag;

		}
	}
	/*
	 * revert string key or use $key in route
	 */
	static protected function compareKey($string, $key) {
		if (self::$seo_translate ) {
			if (vmText::_('COM_VIRTUEMART_SEF_'.$key) == $string ) {
				return true;
			}

		}
		if ($string == $key) return true;
		return false;
	}
}

// pure php no closing tag