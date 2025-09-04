<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_virtuemart_languages
 * @author Max Milbers
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved. 2015 - 2016 iStraxx GmbH
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');


abstract class modVmLanguagesHelper
{
	public static function getList(&$params) {

		$debugSet = false;
		/*if(VmEcho::$_debug = 0 and vmAccess::manager('core')){
			VmEcho::$_debug = 1;
			$debugSet = true;
		}*/
		$user		= JFactory::getUser();
		$lang		= JFactory::getLanguage();
		$languages	= JLanguageHelper::getLanguages();
		$app		= JFactory::getApplication();
		$menu		= $app->getMenu();

		// Get menu home items
		$homes = array();
		$homes['*'] = $menu->getDefault('*');

		foreach ($languages as $item) {
			$default = $menu->getDefault($item->lang_code);
			if ($default && $default->language === $item->lang_code) {
				$homes[$item->lang_code] = $default;
			}
		}

		// Load associations
		$assoc = JLanguageAssociations::isEnabled();


		$cassociations = array();
		if ($assoc)
		{
			$active = $menu->getActive();

			if ($active)
			{
				$associations = MenusHelper::getAssociations($active->id);
			}

			// Load component associations
			$option = $app->input->get('option');
			$class = ucfirst(str_replace('com_', '', $option)) . 'HelperAssociation';
			\JLoader::register($class, JPATH_SITE . '/components/' . $option . '/helpers/association.php');

			if (class_exists($class) && is_callable(array($class, 'getAssociations')))
			{
				$cassociations = call_user_func(array($class, 'getAssociations'));
			}
		}

		$levels    = $user->getAuthorisedViewLevels();
		$sitelangs = JLanguageHelper::getInstalledLanguages(0);
		$multilang = JLanguageMultilang::isEnabled();

		$query = vmUri::getCurrentUrlBy('get',false,true,array('language','lang','Itemid','keyword','virtuemart_currency_id'),true);
		//We need limitstart, except it is empty
		if(empty($query['limitstart'])){
			unset($query['limitstart']);
		}

		vmdebug('my query '.VmLanguage::$currLangTag,$query, $languages);
		$itId = vRequest::getInt('Itemid',false);


		$uri = JUri::getInstance(JUri::base());
		$absoluteUrl = $uri->toString(array('scheme','host'));

		//vmdebug('my getList for ',$languages);
		// Filter allowed languages
		foreach ($languages as $i => &$language) {

			//vmdebug('JLanguageMultilang::isEnabled',$language );
			// Do not display language without frontend UI
			if (!array_key_exists($language->lang_code, $sitelangs)){
				unset($languages[$i]);
			}
			// Do not display language without specific home menu
			elseif (!isset($homes[$language->lang_code])) {
				unset($languages[$i]);
			}
			// Do not display language without authorized access level
			elseif (isset($language->access) && $language->access && !in_array($language->access, $levels)) {
				unset($languages[$i]);
			}
			else {
				$url= '';
				$language->active = ($language->lang_code === $lang->getTag());

				// Fetch language rtl
				// If loaded language get from current JLanguage metadata
				if ($language->active)
				{
					$language->rtl = $lang->isRtl();
				}
				// If not loaded language fetch metadata directly for performance
				else
				{
					$languageMetadata = JLanguageHelper::getMetadata($language->lang_code);
					$language->rtl    = $languageMetadata['rtl'];
				}

				if ($multilang) {

					/*vmdebug('VmLanguage::$jDefLangTag '.$language->lang_code,VmLanguage::$jDefLangTag);
					if(VmLanguage::$jDefLangTag==$language->lang_code){
						$language->link = '';
					} else //*/
					/*if(!empty($language->link)){
						vmdebug('Multilang enabled $language->link not empty',$language->link);
						$language->link = $absoluteUrl.$language->link;
						continue;
					}*/

					$itemid = '';
					if (isset($cassociations[$language->lang_code])) {
						$language->link = $absoluteUrl.JRoute::_($cassociations[$language->lang_code] . '&lang=' . $language->sef);
						vmdebug('Found link in $cassociations ',$language->link);
						continue;
					} else if(isset($associations[$language->lang_code]) && $menu->getItem($associations[$language->lang_code])) {
						$itemid = $associations[$language->lang_code];
						$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid);
						vmdebug('Found link in $associations ',$language->link);
					} else {

						/**
						if ($language->active) {
							$language->link = JUri::getInstance()->toString(array('path', 'query'));
						} else {
							$itemid = isset($homes[$language->lang_code]) ? $homes[$language->lang_code]->id : $homes['*']->id;
							$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid);
						}*/
						vmdebug('NO association found yet '.$itemid);
						if(VmLanguage::$currLangTag==$language->lang_code){		//$language->active ??
						//if($language->active){

							if(isset($query['Itemid'])){
								$itemid = $query['Itemid'];
								vmdebug('Itemid for active language by Query '.$itemid);
							} else {
								$itemid = $menu->getActive()->id;
								vmdebug('Itemid for active language by Active menu '.$itemid);
							}
						} else {
							//vmlanguage::setLanguageByTag($language->lang_code, false);
							$itemid = static::findCorrectItemid($query, $language->lang_code);
							if(empty($itemid)) {
								$itemid = isset($homes[$language->lang_code]) ? $homes[$language->lang_code]->id : $homes['*']->id;
							}
							vmdebug('Itemid for findCorrectItemid '.$itemid);
						}

					}

					$queryTmp = $query;
					if(!empty($itemid)){
						$queryTmp['Itemid'] = $itemid;
					}

					if(!empty($language->sef)){
						$queryTmp['lang'] = $language->sef;
					}

					$url = 'index.php?';
					foreach($queryTmp as $n=>$v){
						if(is_array($v)) {
							foreach( $v as $ka => $va ) {
								$url .= $n.'['.$ka.']='.$va.'&';
							}
						} else {
							$url .= $n .'='.$v.'&';
						}
					}
					$url = rtrim($url,'&');
					vmdebug('my created url',$url);
					//$url = $cUrl.'&lang='.$language->sef.$itemid;
					if ($app->getCfg('sef')=='1') {
						vmLanguage::setLanguageByTag($language->lang_code, false);
						vmLanguage::loadJLang('com_virtuemart.sef',true);

						$language->link = $absoluteUrl.JRoute::_($url);

						vmdebug('my $cUrl for '.$language->lang_code.' '.$absoluteUrl,$url,$language->link);
						//vmdebug('JLanguageMultilang::isEnabled',$language->lang_code, $language->link,$url );
					} else {
						$language->link = $url;
					}

				}
				else {
					$language->link = JRoute::_('&Itemid=' . $homes['*']->id);
					//vmdebug('JLanguageMultilang::isEnabled HOME',$language->lang_code, $language->link,$url );
				}

			}

		}
vmdebug('my languages ',$languages);
		//vmlanguage::setLanguageByTag(vmLanguage::$jSelLangTag);
		if($debugSet){
			VmEcho::$_debug = 0;
		}
		return $languages;
	}

	static public function findCorrectItemid($query, $tag){

		static $activeMenu = null;
		static $andAccess = null;

		if($activeMenu === null){
			$app		= JFactory::getApplication();
			$menu		= $app->getMenu('site');
			$activeMenu = $menu->getActive();

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

		$q = 'SELECT * FROM `#__menu` WHERE '.$like.'  and (language="*" or language = "'.$tag.'" )'.$andAccess;

		//$q .= ' and `id` = "'.(int)$id.'" ';

		$q .= ' ORDER BY `language` DESC';
		$h = md5($q);

		static $c = array();

		if(isset($c[$h])){
			vmdebug('Found CACHED itemid '.$tag,$c[$h]);

			return $c[$h];
		} else {
			$db = JFactory::getDbo();
			$db->setQuery($q);
			$c[$h] = $db->loadResult();
			vmdebug('findCorrectItemid use as like '.vmLanguage::$currLangTag.' '.$like, $c[$h]);
			if(!$c[$h]){
				if($view == 'productdetails'){
					$query['view'] = 'category';
					vmdebug('Productdetails Check with view category',$query);
					return static::findCorrectItemid($query,$tag);
				} else if($view == 'category' and isset($query['virtuemart_category_id']) and !empty($query['virtuemart_category_id'])){
					$query['virtuemart_category_id'] = 0;
					vmdebug('Check with empty virtuemart_category_id',$query);
					return static::findCorrectItemid($query,$tag);
				}
			} else {
				vmdebug('Found as new itemid '.$q,$c[$h]);
			}


			return $c[$h];

		}
	}
}
