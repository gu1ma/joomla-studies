<?php
/**
 * Class vRequest
 * Gets filtered request values.
 *
 * @package    VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2014 - 2022 iStraxx UG (haftungsbeschränkt). All rights reserved.
 * @license MIT, see http://opensource.org/licenses/MIT
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *  http://virtuemart.net
 */

defined('FILTER_FLAG_NO_ENCODE') or define ('FILTER_FLAG_NO_ENCODE',!FILTER_FLAG_ENCODE_LOW);

class vRequest {


	public static function getUword($field, $default='', $custom=''){
		$source = self::getVar($field,$default);
		return self::filterUword($source,$custom);
	}

	//static $filters = array( '' =>);
	public static function uword($field, $default='', $custom=''){
		$source = self::getVar($field,$default);
		return self::filterUword($source,$custom);
	}

	public static function filterUword($source, $custom,$replace=''){
		if(function_exists('mb_ereg_replace')){
			//$source is string that will be filtered, $custom is string that contains custom characters
			return mb_ereg_replace('[^\w'.preg_quote($custom).']', $replace, $source);
		} else {
			return preg_replace("~[^\w".preg_quote($custom,'~')."]~", $replace, $source);	//We use Tilde as separator, and give the preq_quote function the used separator
		}
	}

	/**
	 * This function does not allow unicode, replacement for JPath::clean
	 * and makesafe
	 * @param      $string
	 * @param bool $forceNoUni
	 * @return mixed|string
	 */
	static function filterPath($str) {

		if (empty($str)) {
			vmError('filterPath empty string check your paths ');
			vmTrace('Critical error, empty string in filterPath');
			return VMPATH_ROOT;
		}
		$str = trim($str);

		// Delete all '?'
		$str = str_replace('?', '', $str);

		// Replace double byte whitespaces by single byte (East Asian languages)
		$str = preg_replace('/\xE3\x80\x80/', ' ', $str);

		$unicodeslugs = VmConfig::get('transliteratePaths',false);
		if($unicodeslugs){
			$lang = vmLanguage::getLanguage();
			$str = $lang->transliterate($str);
		}

		//This is a path, so remove all strange slashes
		$str = str_replace('/', DS, $str);

		//Clean from possible injection
		while(strpos($str,'..')!==false){
			$str  = str_replace('..', '', $str);
		};
		$str  = preg_replace('#[/\\\\]+#', DS, $str);
		$str = vRequest::vmSpecialChars($str);

		return $str;
	}

	public static function getBool($name, $default = 0){
		$tmp = self::get($name, $default, FILTER_SANITIZE_NUMBER_INT);
		if($tmp){
			$tmp = true;
		} else {
			$tmp = false;
		}
		return $tmp;
	}

	public static function getInt($name, $default = 0, $source = 0){
		return self::get($name, $default, FILTER_SANITIZE_NUMBER_INT,FILTER_FLAG_NO_ENCODE, $source);
	}

	public static function getFloat($name,$default=0.0, $source = 0){
		return self::get($name,$default,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_SCIENTIFIC|FILTER_FLAG_ALLOW_FRACTION, $source);
	}

	/**
	 * - Strips all characters <32 and over 127
	 * - Strips all html.
	 */
	public static function getCmd($name, $default = '', $source = 0){
		$str =  self::get($name, $default, FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH, $source);
		if($str!==null){
			$str = trim($str, ' ' . chr(194) . chr(160));
			$str = vRequest::vmSpecialChars(strip_tags(vRequest::vmSpecialChars_decode($str)));
		}
		return $str;
	}

	/**
	 * - Strips all characters <32
	 * - Strips all html.
	 */
	public static function getWord($name, $default = ''){
		$str =  self::get($name, $default, FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_LOW);
		if($str!==null){
			$str = trim($str, ' ' . chr(194) . chr(160));
			$str = vRequest::vmSpecialChars(strip_tags(vRequest::vmSpecialChars_decode($str)));
		}
		return $str;
	}

	/**
	 * - Encodes all characters that has a numerical value <32.
	 * - encodes <> and similar, so html and scripts do not work
	 */
	public static function getVar($name, $default = null){
		return self::get($name, $default, FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_ENCODE_LOW );
	}

	/**
	 * - Encodes all characters that has a numerical value <32.
	 * - strips html
	 */
	public static function getString($name, $default = '', $source = 0){

		$str = self::get($name, $default, FILTER_UNSAFE_RAW,FILTER_FLAG_ENCODE_LOW, $source);

		if($str!==null){
			if (is_array($str)){
				foreach($str as $k=>$s){
					if(is_array($s)){
						foreach($s as $kk=>$sk){
							$str[$kk] = self::getString($kk, $default, $s);
						}
					} else {
						$s = trim($s, ' ' . chr(194) . chr(160));
						$str[$k] = vRequest::vmSpecialChars(strip_tags(vRequest::vmSpecialChars_decode($s)));
						//$str[$k] = self::getString($k, $default, $str);
					}

				}
				return $str;
			} else {
				// replace no-break-space 'whitespace'
				$str = trim($str, ' ' . chr(194) . chr(160));
				$str = vRequest::vmSpecialChars(strip_tags(vRequest::vmSpecialChars_decode($str)));
				return $str;
			}
		}

	}

	public static function recurseFilterText(&$tmp){

		foreach($tmp as $k =>$v){
			if(is_array($v)){
				self::recurseFilterText($v);
			} else {
				$tmp[$k] = JComponentHelper::filterText($v);
			}
		}
	}

	/**
	 * - Encodes all characters that has a numerical value <32.
	 * - keeps "secure" html
	 */
	public static function getHtml($name, $default = '', $input = 0){
		$tmp = self::get($name, $default,FILTER_UNSAFE_RAW,FILTER_FLAG_NO_ENCODE, $input);

		if(is_array($tmp)){
			self::recurseFilterText($tmp);
			return $tmp;
		} else {
			return JComponentHelper::filterText($tmp);
		}
	}

	public static function getEmail($name, $default = ''){
		return self::get($name, $default, FILTER_VALIDATE_EMAIL,FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
	}

	public static function filterUrl($url){

		if(!is_array($url)){
			$url = urldecode($url);
		} else {
			foreach($url as $k => $u){
				$url[$k] = self::filterUrl($u);
			}
		}
		$url = strip_tags($url);

		//$url = self::filter($url,FILTER_SANITIZE_URL,'');
		return self::filter($url,FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_ENCODE_LOW);
	}

	public static $request = null;
	public static $get = null;
	public static $filteredVars = array();

	/**
	 * Main filter function, called by the others with set Parameters
	 * The standard filter is non restrictiv.
	 *
	 * @author Max Milbers
	 * @param $name
	 * @param null $default
	 * @param int $filter
	 * @param int $flags
	 * @return mixed|null
	 */
	public static function get($name, $default = false, $filter = FILTER_UNSAFE_RAW, $flags = FILTER_FLAG_NO_ENCODE, $source = 0){
		vmSetStartTime('getfilter');

		if($name === ''){
			vmTrace('empty name in vRequest::get', FALSE, 5);
			return $default;
		} else {

			if($source!==0 and is_array($source)){
				$sourceVar = $source;
				if(isset($sourceVar[$name])) {
					$v = $sourceVar[$name];
					$v = self::filter($v,$filter,$flags);
				} else {
					$v = $default;
				}
				return $v;
			}

			$key = $filter.'.'.$flags;
			if(isset(self::$filteredVars[$source][$name][$key])){
				//vmTime('Using cache '.$name,'getfilter');
				return self::$filteredVars[$source][$name][$key];
			}

			if($source==0){

				if(!isset(self::$request)){
					vRequest::setRouterVars();
				}
				$sourceVar = self::$request;

			} else if($source=='GET') {
				
				if(!isset(self::$get)){
					vRequest::setRouterVars();
				}
				$sourceVar = self::$get;

			} else if ($source=='POST') {
				$sourceVar = $_POST;
			} else if(!empty($source)) {
				$sourceVar = $source;
			}

			if(isset($sourceVar[$name])) {
				$v = $sourceVar[$name];
				$v = self::filter($v,$filter,$flags);
				self::$filteredVars[$source][$name][$key] = $v;
			} else {
				$v = $default;  //default must not be cached
			}

			//vmdebug('Set cache',self::$filteredVars[$source]);
			//vmTime('Get filter '.$key,'getfilter');
			return $v;

		}
	}

	public static $routerSet = false;

	public static function setRouterVars(){

		/*if(self::$routerSet) {
			vmTrace('setRouterVars called more than once');
		} else {
			vmTrace('setRouterVars called FIRST TIME');
		}*/
		$routerDebug = VmConfig::get('debug_enable_router',0);
		if($routerDebug)
			vmdebug('Set router vars Input Get, Post', $_GET, $_POST, $_REQUEST);

		self::$get = $_GET;
		if(VmConfig::isSiteByApp()) {
			$Jinput = JFactory::getApplication()->input;
			//We get the data for the requested data send method GET, POST
			$input = $Jinput->getInputForRequestMethod();
			$inputM = $input->getArray();

			//We need this array to get the menuItem data
			$inputData = $Jinput->getArray();
			//vmdebug('Set router vars $inputData, $inputM', $inputData, $inputM);
			$inputM = array_merge($inputData, $inputM);

			//So we override now the standard Request with the Data of Jinput by requested method
			//vmdebug('Set router vars array_merge $_REQUEST, $inputM', $_REQUEST, $inputM);
			self::$request = array_merge($_REQUEST, $inputM);
		} else {
			self::$request = $_REQUEST;
		}

		if($routerDebug)
			vmdebug('Set router vars self::$get, Post, self::$request', self::$get, $_POST, self::$request);

		self::$filteredVars[0] = array();
		if(isset(self::$filteredVars['POST'])) self::$filteredVars['POST'] = array();
		if(isset(self::$filteredVars['GET'])) self::$filteredVars['GET'] = array();

		self::$routerSet = true;

	}

	public static function filter($var, $filter, $flags, $array=false){
		if($array or is_array($var)){
			if(!is_array($var)) $var = array($var);
			self::recurseFilter($var, $filter, $flags);
			return $var;
		}
		else {
			return filter_var($var, $filter, $flags);
		}
	}

	public static function recurseFilter(&$var, $filter, $flags = FILTER_FLAG_STRIP_LOW){

		$toDrop = array();
		foreach($var as $k=>&$v){
			if(!empty($k) and !is_numeric($k)){
				$t = filter_var($k, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
				if($t!=$k){
					//$var[$t] = $v;
					$toDrop[] = $k;
					//unset($var[$k]);
					continue;
					VmEcho::$echoDebug = 1;
					vmdebug('unset invalid key',$k,$t);
				}
			}
			if(!empty($v)){
				if( is_array($v) ){	//and count($v)>1){
					self::recurseFilter($v, $filter, $flags);
				} else {
					$v = filter_var($v, $filter, $flags);
				}
			}
		}
		foreach($toDrop as $k){
			unset($var[$k]);
		}
	}

	/**
	 * Gets the request and filters it directly. It uses the standard php function filter_var_array,
	 * The standard filter allows all chars, also the special ones. But removes dangerous html tags.
	 *
	 * @author Max Milbers
	 * @param array $filter
	 * @return mixed cleaned $_REQUEST
	 */
	public static function getRequest( $filter = FILTER_SANITIZE_SPECIAL_CHARS, $flags = FILTER_FLAG_ENCODE_LOW ){

		if (self::$request===null) {
			self::setRouterVars();
		}
		$source = self::$request;

		return self::filter($source, $filter, $flags,true);
	}
	
	public static function getPost( $filter = FILTER_SANITIZE_SPECIAL_CHARS, $flags = FILTER_FLAG_ENCODE_LOW ){
		return self::filter($_POST, $filter, $flags,true);
	}
	
	public static function getGet( $filter = FILTER_SANITIZE_SPECIAL_CHARS, $flags = FILTER_FLAG_ENCODE_LOW ){

		if (self::$get===null) {
			self::setRouterVars();
		}
		$source = self::$get;

		return self::filter($source, $filter, $flags,true);
	}
	
	public static function getFiles( $name, $filter = FILTER_SANITIZE_SPECIAL_CHARS, $flags = FILTER_FLAG_STRIP_LOW){
		if(empty($_FILES[$name])) return false;
		return  self::filter($_FILES[$name], $filter, $flags);
	}

	public static function setVar($name, $value = null){

		$tmp = null;
		if(isset(self::$filteredVars[0][$name])){
			$tmp = current(self::$filteredVars[0][$name]);
			self::$filteredVars[0][$name] = array();
			if(isset(self::$filteredVars['POST'])) self::$filteredVars['POST'][$name] = array();
			if(isset(self::$filteredVars['GET'])) self::$filteredVars['GET'][$name] = array();
		} else if(isset($_REQUEST[$name])){
			$tmp = self::filter ($_REQUEST[$name], FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW);
		}

		$_REQUEST[$name] = $value;
		self::$request[$name] = $value;

		return $tmp;

	}

	public static function vmSpecialChars($c){
		if (version_compare(phpversion(), '5.4.0', '<')) {
			// php version isn't high enough
			$c = htmlspecialchars ($c,ENT_QUOTES,'UTF-8',false);	//ENT_SUBSTITUTE only for php5.4 and higher
		} else {
			$c = htmlspecialchars ($c,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false);
		}
		return $c;
	}

	public static function vmSpecialChars_decode($c){
		if (version_compare(phpversion(), '5.4.0', '<')) {
			// php version isn't high enough
			$c = htmlspecialchars_decode ($c,ENT_QUOTES);	//ENT_SUBSTITUTE only for php5.4 and higher
		} else {
			$c = htmlspecialchars_decode ($c,ENT_QUOTES|ENT_SUBSTITUTE);
		}
		return $c;
	}

	public static function vmHtmlEntities($str){
		return htmlentities($str, ENT_COMPAT, "UTF-8", false);
	}

	/**
	 * Checks for a form token in the request.
	 *
	 * @return  boolean  True if token valid
	 */
	public static function vmCheckToken($redirectMsg=0){

		$token = self::getFormToken();

		if (!self::uword($token, false)){

			if ($rToken = self::uword('token', false)){
				if($rToken == $token){
					return true;
				}
			}

			$session = JFactory::getSession();

			if ($session->isNew()){
				// Redirect to login screen.
				$app = JFactory::getApplication();
				vmInfo('JLIB_ENVIRONMENT_SESSION_EXPIRED');
				$app->redirect(JRoute::_('index.php'));
				$app->close();
				return false;
			}
			else {
				if($redirectMsg===0){
					$redirectMsg = 'Invalid Token, in ' . vRequest::getCmd('options') .' view='.vRequest::getCmd('view'). ' task='.vRequest::getCmd('task');
					//jexit('Invalid Token, in ' . vRequest::getCmd('options') .' view='.vRequest::getCmd('view'). ' task='.vRequest::getCmd('task'));
				}
				// Redirect to login screen.
				$app = JFactory::getApplication();
				$session->close();
				vmWarn($redirectMsg);
				$app->redirect(JRoute::_('index.php'));
				$app->close();
				return false;
			}
		}
		else {
			return true;
		}
	}

	public static function getFormToken($fNew = false){

		$sess = JFactory::getSession();
		$user = JFactory::getUser();

		if(empty($user->id)) $user->id = 0;

		$token = $sess->get('session.token');
		if ($token === null || $fNew) {
			$token = vmCrypt::getToken();
			$sess->set('session.token', $token);
		}
		$hash = self::getHash($user->id . $token);

		return $hash;
	}

	public static function getHash($seed) {
		return md5(VmConfig::getSecret() . $seed);
	}
}