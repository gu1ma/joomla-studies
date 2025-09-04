<?php
defined ('_JEXEC') or die();

/**
 * vmLanguage class
 *
 * initialises and holds the JLanguage objects for VirtueMart
 *
 * @package	VirtueMart
 * @subpackage Language
 * @author Max Milbers
 * @copyright Copyright (c) 2016 - 2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL 3, see COPYRIGHT.php
 */

class vmLanguage {

	/** @var string Joomla Default Site Language Tag */
	//public static $jDefLangTag = null;
	//public static $vmDefLangTag = null;
	public static $vmDefLang = null;

	/** @var string Joomla Selected Site Language Tag*/
	public static $jSelLangTag = false;

	/** @var string Current Selected Virtuemart Language Tag*/
	public static $currLangTag = false;
	public static $jLangCount = 1;
	public static $langCount = 0;
	public static $langs = array();
	public static $languages = array();


	/**
	 * Initialises the vm language class. Attention the vm debugger is not working in this function, because the right checks are done after the language
	 * initialisiation.
	 * @param bool $siteLang
	 */
	static public function initialise($siteLang = false){

		if(self::$jSelLangTag!==false){
			return ;
		}

		self::$jLangCount = 1;

		//Determine the shop default language (default joomla site language)
		if(VmConfig::$jDefLang===false){
			if (class_exists('JComponentHelper') && (method_exists('JComponentHelper', 'getParams'))) {
				$params = JComponentHelper::getParams('com_languages');
				VmConfig::$jDefLangTag = $params->get('site', 'en-GB');
			}  else {
				VmConfig::$jDefLangTag = 'en-GB';//use default joomla
				vmError('JComponentHelper not found');
			}
			//VmConfig::$jDefLangTag = self::getShopDefaultOrSiteLangTagByJoomla();
			VmConfig::$jDefLang = strtolower(strtr(VmConfig::$jDefLangTag,'-','_'));
		}

		VmConfig::$defaultLangTag = VmConfig::get('vmDefLang',VmConfig::$jDefLangTag);
		if(empty(VmConfig::$defaultLangTag)){
			VmConfig::$defaultLangTag = VmConfig::$jDefLangTag;
		}
		VmConfig::$defaultLang = strtolower(strtr(VmConfig::$defaultLangTag,'-','_'));

		$l = JFactory::getLanguage();
		//$l = JFactory::getApplication()->getLanguage();
		//Set the "joomla selected language tag" and the joomla language to vmText
		self::$jSelLangTag = $l->getTag();
		self::$languages[self::$jSelLangTag] = $l;
		vmText::$language = $l;

		$siteLang = self::$currLangTag = self::$jSelLangTag;
		//if( !VmConfig::isSite()){ //This creates massive trouble because isSite checks for manager, which is not set yet
			$siteLang = vRequest::getString('vmlang',$siteLang, $_REQUEST );	//0 overwritten on purpose with $_REQUEST
			if (!$siteLang) {
				$siteLang = self::$jSelLangTag;
			}
		//}

		self::$langs = (array)VmConfig::get('active_languages',array(VmConfig::$jDefLangTag));
		self::$langCount = count(self::$langs);

		self::setLanguageByTag($siteLang);

	}

	static public function getShopDefaultSiteLangTagByJoomla(){
		return self::getShopDefaultOrSiteLangTagByJoomla();
	}

	static public function getShopDefaultOrSiteLangTagByJoomla(){

		$l= VmConfig::get('vmDefLang','');
		if(empty($l)) {
			if (class_exists('JComponentHelper') && (method_exists('JComponentHelper', 'getParams'))) {
				$params = JComponentHelper::getParams('com_languages');
				VmConfig::$jDefLangTag = $params->get('site', 'en-GB');
				$l = VmConfig::$jDefLangTag;
			} else {
				$l = 'en-GB';//use default joomla
				vmError('JComponentHelper not found');
			}
		}
		return $l;
	}

	static public function setLanguageByTag($siteLang, $alreadyLoaded = true){

		if(empty($siteLang)){
			$siteLang = self::$currLangTag;
		} else {
			if($siteLang!=self::$currLangTag){
				self::$cgULF = null;
				self::$cgULFS = null;
			}
		}

		self::setLanguage($siteLang);

		// this code is uses logic derived from language filter plugin in j3 and should work on most 2.5 versions as well
		if (class_exists('JLanguageHelper') && (method_exists('JLanguageHelper', 'getLanguages'))) {
			$languages = JLanguageHelper::getLanguages('lang_code');
			self::$jLangCount = count($languages);
			if(isset($languages[$siteLang])){
				VmConfig::$vmlangSef = $languages[$siteLang]->sef;
			} else {
				if(isset($languages[self::$jSelLangTag])){
					VmConfig::$vmlangSef = $languages[self::$jSelLangTag]->sef;
				}
			}
		}



		VmConfig::$vmlangTag = $siteLang;
		VmConfig::$vmlang = strtolower(strtr($siteLang,'-','_'));

		//VmConfig::$defaultLangTag = VmConfig::$jDefLangTag;
		//VmConfig::$defaultLang = strtolower(strtr(VmConfig::$jDefLangTag,'-','_'));

		if(self::$langCount>1){
			$lfbs = VmConfig::get('vm_lfbs','');
			/*	This cannot work this way, because the SQL would need a union with left and right join, much too expensive.
			 *	even worse, the old construction would prefer the secondary language over the first. It can be tested using the customfallback
			 *  for example en-GB~de-DE for en-GB as shop language
			 * if(count($langs)==2 and VmConfig::$vmlangTag==VmConfig::$defaultLangTag and VmConfig::get('dualFallback',false) ){
				foreach($langs as $lang){
					if($lang!=VmConfig::$vmlangTag){
						VmConfig::$defaultLangTag = $lang;
						VmConfig::$defaultLang = strtolower(strtr(VmConfig::$defaultLangTag,'-','_'));
					}
				}
			} else */
			if(!empty($lfbs)){
				//vmdebug('my lfbs '.$lfbs);
				$pairs = explode(';',$lfbs);
				if($pairs and count($pairs)>0){
					$fbsAssoc = array();
					foreach($pairs as $pair){
						$kv = explode('~',$pair);
						if($kv and count($kv)===2){
							$fbsAssoc[$kv[0]] = $kv[1];
						}
					}
					if(isset($fbsAssoc[$siteLang])){

						VmConfig::$defaultLangTag = $fbsAssoc[$siteLang];
						VmConfig::$defaultLang = strtolower(strtr(VmConfig::$defaultLangTag,'-','_'));

						vmdebug('Set lang fallback for '.$siteLang.' to '.VmConfig::$defaultLang,VmConfig::$jDefLang);
					}
					VmConfig::set('fbsAssoc',$fbsAssoc);
				}
			}
		}


		if(!in_array($siteLang, self::$langs)) {
			//vmError('Selected siteLang '. $siteLang.' is not in $langs '.implode(', ',$langs));
			vmdebug('Selected siteLang '. $siteLang.' is not in $langs '.implode(', ',self::$langs));
			$siteLang = VmConfig::$jDefLangTag;	//Set to shop language
			VmConfig::$vmlang = strtolower(strtr($siteLang,'-','_'));
		}


		//JLangTag if also activevmlang set as FB, ShopLangTag($jDefLangTag), vmLangTag, vm_lfbs overwrites
		if(!empty(self::$_loaded) and $alreadyLoaded){
			//vmdebug('Loaded not empty, lets start',self::$_loaded);
			self::loadUsedLangFiles();
		}
		//@deprecated just fallback
		defined('VMLANG') or define('VMLANG', VmConfig::$vmlang );
		//self::debugLangVars();
	}

	static public function loadUsedLangFiles(){

		//vmSetStartTime('loadUsedLangFiles');
		if(!empty(self::$_loaded['com'])){
			if(!empty(self::$_loaded['com'][0])){
				foreach(self::$_loaded['com'][0] as $name){
					self::loadJLang($name,0);
				}
			}
			if(!empty(self::$_loaded['com'][1])){
				foreach(self::$_loaded['com'][1] as $name){
					self::loadJLang($name,1);
				}
			}
		}

		if(!empty(self::$_loaded['mod'])){
			foreach(self::$_loaded['mod'] as $name){
				self::loadModJLang($name);
			}
		}

		if(!empty(self::$_loaded['plg'])){
			foreach(self::$_loaded['plg'] as $cvalue=>$name){

				$t = explode(';',$cvalue);
				//vmdebug('loadUsedLangFiles',$t[0],$t[1],$name);
				vmPlugin::loadJLang($t[0],$t[1],$name);
			}
		}
		//vmTime('loadUsedLangFiles','loadUsedLangFiles');
		//vmRam('loadUsedLangFiles');
	}

	static public function debugLangVars(){
		//vmdebug('LangCount: '.self::$langCount.' $siteLang: '.$siteLang.' VmConfig::$vmlangSef: '.VmConfig::$vmlangSef.' self::$_jpConfig->lang '.VmConfig::$vmlang.' DefLang '.VmConfig::$defaultLang);
		if(self::$langCount==1){
			$l = self::$langCount.' Language, default shoplanguage (VmConfig::$jDefLang): '.VmConfig::$jDefLang.' '.VmConfig::$jDefLangTag;
		} else {
			$l = self::$langCount.' Languages, default joomla language $jDefLang): '.VmConfig::$jDefLang.' '.VmConfig::$jDefLangTag;
			//if(VmConfig::$jDefLang!=VmConfig::$defaultLang){
			if(self::getUseLangFallback()){
				$l .= '<br> Fallback language (VmConfig::$defaultLang): '.VmConfig::$defaultLang.' '.VmConfig::$defaultLangTag;
			}
			$l .= ' <br>Selected VM language (VmConfig::$vmlang): '.VmConfig::$vmlang.' '.VmConfig::$vmlangTag.' SEF: '.VmConfig::$vmlangSef.' $lfbs = '.VmConfig::get('vm_lfbs',''); ;
		}
		vmdebug($l);
	}


	static public function setLanguage($tag){

		if(!isset(self::$languages[$tag])) {
			self::getLanguage($tag);
		}
		if(!empty(self::$languages[$tag])) {

			vmText::$language = self::$languages[$tag];
			self::$currLangTag = $tag;

			//There are plugins working with languages. They rely often on the standard JLanguage, so einjecting our JLanguage Object
			//can create serious side effects. We may set the argument to true for emails, invoices and similar.
			$jLObjToApp = VmConfig::get('ReInjectJLanguage', false);
			if($jLObjToApp){
				$app = JFactory::getApplication();
				try{
					$app->set('language', $tag);
					JFactory::$language =& self::$languages[$tag];

				} catch (Exception $e) {
					vmError('Could not set language');
					return;
				}

				//@author     Yireo (info@yireo.com)
				if (method_exists($app, 'loadLanguage')) {
					$app->loadLanguage(self::$languages[$tag]);
				}

				//@author     Yireo (info@yireo.com)
				if (method_exists($app, 'setLanguageFilter')) {
					$app->setLanguageFilter(true);
				}

				// Falang override @author     Yireo (info@yireo.com)
				$registry = JFactory::getConfig();
				$registry->set('config.defaultlang', self::$jSelLangTag);

				// Falang override @author     Yireo (info@yireo.com)
				JComponentHelper::getParams('com_languages')
				->set('site', self::$jSelLangTag);//*/
			}

			//vmTrace('setLanguage '.$tag, true, 15);	//*/
		} else {
			vmError('Could not set language '.$tag);
		}


	}

	static public function getLanguage($tag = 0){

		if(empty($tag)) {
			$tag = self::$jSelLangTag;	//This is the joomla language, the used tag must not change
		}

		//We dont need the case for the standard language, because it is set in the initialise function
		if(!isset(self::$languages[$tag])) {
			self::$languages[$tag] = JLanguage::getInstance($tag, false);
		}

		return self::$languages[$tag];
	}

	static public $_loaded = array();
	/**
	 * loads a language file, the trick for us is that always the config option enableEnglish is tested
	 * and the path are already set and the correct order is used
	 * We use first the english language, then the default
	 *
	 * @author Max Milbers
	 * @static
	 * @param $name
	 * @return bool
	 */
	static public function loadJLang($name, $site = false, $tag = 0, $cache = true){

		static $loaded = array();

		if(empty($tag)) {
			$tag = self::$currLangTag;
		}
		$site = (int)$site;
		self::$_loaded['com'][$site][$name] = $name;
		self::getLanguage($tag);

		$h = $site.$tag.$name;
		if($cache and isset($loaded[$h])){
			vmText::$language = self::$languages[$tag];
			return self::$languages[$tag];
		} else {
			if(!isset(self::$languages[$tag])){
				vmdebug('No language loaded '.$tag.' '.$name);
				vmEcho::$logDebug = true;
				vmTrace('No language loaded '.$tag.' '.$name,true);
				return false ;
			}
		}

		if($site){
			$path = $basePath = VMPATH_SITE;
		} else {
			$path = $basePath = VMPATH_ADMIN;
		}

		if($tag!='en-GB' and VmConfig::get('enableEnglish', true) ){
			$testpath = $basePath.'/language/en-GB/en-GB.'.$name.'.ini';
			if(!file_exists($testpath)){
				if($site){
					$epath = VMPATH_ROOT;
				} else {
					$epath = VMPATH_ADMINISTRATOR;
				}
			} else {
				$epath = $path;
			}
			self::$languages[$tag]->load($name, $epath, 'en-GB', true, false);
		}

		$testpath = $basePath.'/language/'.$tag.'/'.$tag.'.'.$name.'.ini';
		if(!file_exists($testpath)){
			if($site){
				$path = VMPATH_ROOT;
			} else {
				$path = VMPATH_ADMINISTRATOR;
			}
		}

		self::$languages[$tag]->load($name, $path, $tag, true, true);
		$loaded[$h] = true;
		//vmdebug('loaded '.$h.' '.$path.' '.self::$languages[$tag]->getTag());
		vmText::$language = self::$languages[$tag];
		//vmText::setLanguage(self::$languages[$tag]);
		return self::$languages[$tag];
	}

	/**
	 * @static
	 * @author Max Milbers, Valerie Isaksen
	 * @param $name
	 */
	static public function loadModJLang($name){

		$tag = self::$currLangTag;
		self::$_loaded['mod'][$name] = $name;
		self::getLanguage($tag);

		$path = $basePath = JPATH_VM_MODULES.'/'.$name;
		if(VmConfig::get('enableEnglish', true) and $tag!='en-GB'){
			if(!file_exists($basePath.'/language/en-GB/en-GB.'.$name.'.ini')){
				$path = JPATH_ADMINISTRATOR;
			}
			self::$languages[$tag]->load($name, $path, 'en-GB');
			$path = $basePath = JPATH_VM_MODULES.'/'.$name;
		}

		if(!file_exists($basePath.'/language/'.$tag.'/'.$tag.'.'.$name.'.ini')){
			$path = JPATH_ADMINISTRATOR;
		}
		self::$languages[$tag]->load($name, $path,$tag,true);

		return self::$languages[$tag];
	}

	static $cgULF = null;

	static public function getUseLangFallback($fresh = false){

		if(self::$cgULF===null or $fresh){
			if(VmLanguage::$langCount>1 and VmConfig::$defaultLang!=VmConfig::$vmlang and !VmConfig::get('prodOnlyWLang',false) ){
				self::$cgULF = true;
			} else {
				self::$cgULF = false;
			}
		}

		return self::$cgULF;
	}

	static $cgULFS = null;

	static public function getUseLangFallbackSecondary($fresh = false){

		if(self::$cgULFS===null or $fresh){
			if(self::getUseLangFallback() and VmConfig::$defaultLang!=VmConfig::$jDefLang and VmConfig::$jDefLang!=VmConfig::$vmlang){
				self::$cgULFS = true;
			} else {
				self::$cgULFS = false;
			}
		}
		return self::$cgULFS;
	}
}