<?php
defined('_JEXEC') or die('');
/**
 * abstract controller class containing get,store,delete,publish and pagination
 *
 *
 * This class provides the functions for the calculatoins
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2011 - 2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 * @copyright  (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * http://virtuemart.net
 */
// Load the view framework

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;

jimport( 'joomla.application.component.view');
// Load default helpers

class VmView extends JViewLegacy{

	var $isMail = false;
	var $isPdf = false;
	var $writeJs = true;
	var $useSSL = 0;
	static protected $bs = null;
	static protected $override = null;

	function __construct($config = array()){

		if(!isset(VmView::$bs)){
			VmView::$bs = VmConfig::get('bootstrap','bs5');
			VmView::$override = VmConfig::get('useLayoutOverrides',1);
			if(VmConfig::$_debug){
				$msg = '';
				if(!empty(VmView::$override)){
					$msg = 'VmView loaded with override on';
				}
				if(!empty(VmView::$bs)){
					$msg .= ' bootstrap version '. VmView::$bs;
				}
				if(!empty($msg)){
					vmdebug($msg);
				}
			}

		}
		parent::__construct($config);
	}

	/**
	 * @depreacted
	 * @param string $key
	 * @param mixed $val
	 * @return bool|void
	 */
	public function assignRef($key, &$val) {
		$this->{$key} =& $val; 
	}
	
	public function display($tpl = null) {


		if(JVM_VERSION>3){

			$app = Factory::getApplication();

			if ($this->option) {
				$component = $this->option;
			} else {
				$component = ApplicationHelper::getComponentName();
			}

			$context = $component . '.' . $this->getName();

			$app->getDispatcher()->dispatch(
				'onBeforeDisplay',
				AbstractEvent::create(
					'onBeforeDisplay',
					[
						'eventClass' => 'Joomla\CMS\Event\View\DisplayEvent',
						'subject'    => $this,
						'extension'  => $context,
					]
				)
			);
		}


		if($this->isMail or $this->isPdf){
			$this->writeJs = false;
		}
		$this->useSSL = vmURI::useSSL();


		if(!VmView::$override){
			//we just add the default again, so it is first in queque
			$this->addTemplatePath(VMPATH_ROOT .'/components/com_virtuemart/views/'.$this->_name.'/tmpl');
		}

		$result = $this->loadTemplate($tpl);
		if ($result instanceof Exception) {
			return $result;
		}

		if(JVM_VERSION>3){
			$eventResult = $app->getDispatcher()->dispatch(
				'onAfterDisplay',
				AbstractEvent::create(
					'onAfterDisplay',
					[
						'eventClass' => 'Joomla\CMS\Event\View\DisplayEvent',
						'subject'    => $this,
						'extension'  => $context,
						'source'     => $result,
					]
				)
			);

			$eventResult->getArgument('used', false);
		}


		echo $result;
		if($this->writeJs){
			self::withKeepAlive();
			vmJsApi::vmVariables();
			echo vmJsApi::writeJS();
		}

	}

	public function withKeepAlive(){

		$cart = VirtueMartCart::getCart();
		if(!empty($cart->cartProductsData)){
			vmJsApi::keepAlive(1,4);
		}
	}

	/**
	 * Renders sublayouts
	 *
	 * @author Max Milbers
	 * @param $name
	 * @param int $viewData viewdata for the rendered sublayout, do not remove
	 * @return string
	 */
	public function renderVmSubLayout($name=0,$viewData=0){

		if ($name === 0) {
			$name = $this->_name;
		}
		
		$lPath = self::getVmSubLayoutPath ($name);
		//vmdebug('renderVmSubLayout layout '.$name,lPath);
		if($lPath){
			if($viewData!==0 and is_array($viewData)){
				foreach($viewData as $k => $v){
					if ('_' != substr($k, 0, 1) and !isset($this->{$k})) {
						$this->{$k} = $v;
					}
				}
			}
			ob_start ();
			include ($lPath);
			return ob_get_clean();
		} else {
			vmdebug('renderVmSubLayout layout not found '.$name);
			return 'Sublayout not found '.$name;
		}

	}



	static public function getVmSubLayoutPath($name) {

		static $layouts = array();

		if (isset($layouts[$name])) {
			return $layouts[$name];
		} else {
			$vmStyle  = VmTemplate::loadVmTemplateStyle();
			$template = $vmStyle['template'];

			// get the template and default paths for the layout if the site template has a layout override, use it
			$tP  = VMPATH_ROOT .'/templates/'. $template .'/html/com_virtuemart/sublayouts/';
			$tPp = !empty($vmStyle['parent']) ? VMPATH_ROOT .'/templates/'. $vmStyle['parent'] .'/html/com_virtuemart/sublayouts/' : null;
			$nP  = VMPATH_SITE .'/sublayouts/';

			if (!isset(VmView::$bs)) {
				VmView::$bs		  = VmConfig::get('bootstrap','bs5');
				VmView::$override = VmConfig::get('useLayoutOverrides', 1);

				if (VmConfig::$_debug) {
					$msg = '';
					
					if (!empty(VmView::$override)) {
						$msg = 'VmView loaded with override';
					}
					
					if (!empty(VmView::$bs)) {
						$msg .= ' bootstrap version '. VmView::$bs;
					}
					
					if (!empty($msg)) {
						vmdebug($msg);
					}
				}
			}

			if (VmView::$bs!=='') {
				$bsLayout = VmView::$bs . '-' . $name;
				
				if (VmView::$override and JFile::exists($tP . $bsLayout . '.php')) {
					$layouts[$name] = $tP . $bsLayout . '.php';
					//vmdebug(' getVmSubLayoutPath using '.VmView::$bs.' tmpl layout override ',$layouts[$name]);
					return $layouts[$name];
				} elseif ($tPp and VmView::$override and JFile::exists($tPp . $bsLayout . '.php')) {
					$layouts[$name] = $tPp . $bsLayout . '.php';
					return $layouts[$name];
				}
			}

			//If a normal template overrides exists, use the template override
			if (VmView::$override and JFile::exists($tP. $name .'.php')) {
				$layouts[$name] = $tP . $name . '.php';
				//vmdebug(' getVmSubLayoutPath using tmpl layout override ',$layouts[$name]);
				return $layouts[$name];
			} elseif ($tPp and VmView::$override and JFile::exists($tPp . $name . '.php')) {
				$layouts[$name] = $tPp . $name . '.php';
				return $layouts[$name];
			}

			if (VmView::$bs!=='') {
				if (JFile::exists($nP. $bsLayout . '.php')) {
					$layouts[$name] = $nP. $bsLayout . '.php';
					//vmdebug(' getVmSubLayoutPath using '.VmView::$bs.' core layout ',$layouts[$name]);
					return $layouts[$name];
				}
			}

			if (JFile::exists($nP. $name . '.php')) {
				$layouts[$name] = $nP. $name .'.php';
				//vmdebug(' getVmSubLayoutPath using standard core ',$layouts[$name]);
			} else {
				$layouts[$name] = false;
				//vmEcho::$echoDebug = true;
				//vmdebug(' getVmSubLayoutPath layout NOOOT found ',$lName);
				vmError('getVmSubLayoutPath layout '.$name.' not found ');
			}

			return $layouts[$name];
		}
	}

	public function setLayoutAndSub($layout, $sub){

		$previous = $this->_layout;

		if (strpos($layout, ':') === false)
		{
			$this->_layout = $layout;
		}
		else
		{
			// Convert parameter to array based on :
			$temp = explode(':', $layout);
			$this->_layout = $temp[1];

			// Set layout template
			$this->_layoutTemplate = $temp[0];
		}

		if(VmView::$bs!==''){
			if(substr($this->_layout,0,4) == VmView::$bs.'-'){
				//$this->_layout = VmView::$bs.'-'.$this->_layout;
				$this->_layout = substr($this->_layout,4);
			} /*else {

			}*/
			$l = $this->_layout .'_'. $sub;//$this->_layout;//$this->getLayout();

			$bsLayout = VmView::$bs.'-'.$l;
			$vmStyle = VmTemplate::loadVmTemplateStyle();
			$template = $vmStyle['template'];
			//vmEcho::$echoDebug = 1;
			vmdebug('setLayoutAndSub my bootstrap layout here ',$bsLayout, $l);
			$tP = VMPATH_ROOT .'/templates/'. $template .'/html/com_virtuemart/'.$this->_name.'/';//. $bsLayout .'.php';
			$nP = VMPATH_SITE .'/views/'.$this->_name.'/tmpl/'. $bsLayout . '.php';

			if( VmView::$override and JFile::exists($tP. $bsLayout .'.php') ){
				$this->_layout =  VmView::$bs.'-'.$this->_layout;
				vmdebug('setLayoutAndSub I use a layout BOOTSTRAP '.VmView::$bs.' by template override',$bsLayout);
			} else if ( VmView::$override and JFile::exists ($tP. $l .'.php') ) {
				//$this->setLayout($l);
				vmdebug('I use a layout by template override', $tP.$l);
			} else if ( JFile::exists ($nP) ){
				vmdebug('I use a CORE Bootstrap layout my layout here ',$bsLayout);
				$this->_layout = VmView::$bs.'-'.$this->_layout;
			} else {
				$this->_layout = VmView::$bs.'-'.$this->_layout;
				vmdebug('No layout found, that should not happen '.$this->_name,$bsLayout);
			}

		}

		return $previous;
	}

	/**
	 * If we want to use the layout in a workflow, we need the layout without the Bootstrap prefix
	 * example bs4-default is return as default
	 * @return false|string
	 */

	public function getBaseLayout()
	{
		if(!empty(VmView::$bs)){
			if(substr($this->_layout,0,4) == VmView::$bs.'-'){
				return substr($this->_layout,4);
			}
		}

		return $this->_layout;
	}

	/**
	 * Sets the layout name to use. Adjusted to the vm system to load bsX layouts
	 * @copyright  (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
	 * @license    GNU General Public License version 2 or later; see LICENSE.txt
	 * @param   string  $layout  The layout name or a string in format <template>:<layout file>
	 *
	 * @return  string  Previous value.
	 *
	 * @since   3.0
	 */
	public function setLayout($layout)
	{
		$previous = $this->_layout;

		if (strpos($layout, ':') === false)
		{
			$this->_layout = $layout;
		}
		else
		{
			// Convert parameter to array based on :
			$temp = explode(':', $layout);
			$this->_layout = $temp[1];

			// Set layout template
			$this->_layoutTemplate = $temp[0];
		}

		if(empty($this->_name)){
			vmTrace('setLayoutAndSub empty view name that should not happen');
			return $previous;
		}

		if(VmView::$bs!==''){

			$l = $this->getLayout();
			$bsLayout = VmView::$bs.'-'.$l;

			$vmStyle = VmTemplate::loadVmTemplateStyle();
			$template = $vmStyle['template'];
			//vmdebug('setLayout my bootstrap layout here ',$bsLayout, $l);

			$tP = VMPATH_ROOT .'/templates/'. $template .'/html/com_virtuemart/'.$this->_name.'/';//. $bsLayout .'.php';
			$nP = VMPATH_SITE .'/views/'.$this->_name.'/tmpl/'. $bsLayout . '.php';

			if( VmView::$override and JFile::exists($tP. $bsLayout .'.php') ){
				$this->_layout = $bsLayout;
				vmdebug('setLayout I use a layout BOOTSTRAP '.VmView::$bs.' by template override',$bsLayout);
			} else if ( VmView::$override and JFile::exists ($tP. $l .'.php') ) {
				//$this->setLayout($l);
				vmdebug('setLayout I use a layout by template override',$l);
			} else if ( JFile::exists ($nP) ){
				vmdebug('setLayout I use a CORE Bootstrap layout my layout here ',$bsLayout);
				$this->_layout = $bsLayout;
			} else if ( JFile::exists (VMPATH_SITE .'/views/'.$this->_name.'/tmpl/'. $l . '.php') ){
				$this->_layout = $l;
			} else {
				$this->_layout = $l;
				//if($this->_name != 'login')vmdebug('No layout found, that should not happen '.$this->_name,$bsLayout,$nP,VMPATH_SITE .'/views/'.$this->_name.'/tmpl/'. $l . '.php');
				//vmTrace('setLayoutAndSub No layout found, that should not happen');
			}

		}

		return $previous;
	}

	/**
	 * Load a template file -- first look in the templates folder for an override
	 *
	 * @param   string  $tpl  The name of the template source file; automatically searches the template paths and compiles as needed.
	 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
	 * @license     GNU General Public License version 2 or later; see LICENSE.txt
	 * @return  string  The output of the the template script.
	 *
	 * @since   3.2
	 * @throws  Exception
	 */
/*	public function loadTemplate($tpl = null)
	{
		// Clear prior output
		$this->_output = null;

		$template = JFactory::getApplication()->getTemplate();

		$layout = $this->getLayout();

		// Create the template file name based on the layout
		$file = isset($tpl) ? $layout . '_' . $tpl : $layout;

		// Clean the file name
		$file = preg_replace('/[^A-Z0-9_\.-]/i', '', $file);
		$tpl = isset($tpl) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $tpl) : $tpl;

		$tP = VMPATH_ROOT .'/templates/'. $template .'/html/com_virtuemart/'.$this->_name.'/';//. $bsLayout .'.php';
		$cP = VMPATH_SITE .'/views/'.$this->_name.'/tmpl/';

		if(VmView::$bs!==''){
			$bsLayout = VmView::$bs.'-'.$layout;
			$bsFile = VmView::$bs.'-'.$file;

		} else {
			$bsLayout = FALSE;
			$bsFile = FALSE;
		}

		static $setPath = array();
		if (!isset($setPath[$file])){
			//$this->_path['template'] = array();

			$filesToCheck = array();
			$filesToCheck[3] = array($cP, $file);
			if( VmView::$override ){
				$filesToCheck[1] = array($tP, $file);
			}
			if($bsFile){
				$filesToCheck[2] = array($cP, $bsFile);
				if( VmView::$override ){
					$filesToCheck[0] = array($tP, $bsFile);
				}
			}
			ksort($filesToCheck);

			foreach($filesToCheck as $fileToTest){
				if(JFile::exists($fileToTest[0]. $fileToTest[1] .'.php')){
					//$this->_path['template'][] = $fileToTest[0];
					$setPath[$file] = $fileToTest[0];
					break;
				}
			}


			//$setPath[$file] = $this->_path['template'];
			vmdebug('LoadTemplate FIRST '.$file,$this->_path['template'], $filesToCheck,$setPath);
		} /*else {
			$this->_path['template'] = $setPath[$layout];
			vmdebug('LoadTemplate $this->_path not empty',$this->_path, $template,$layout,$file,$tpl);
		}*/


		// Load the language file for the template
/*		$lang = JFactory::getLanguage();
		$lang->load('tpl_' . $template, JPATH_BASE, null, false, true)
		|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", null, false, true);

		// Prevents adding path twise
		/*		if (empty($this->_path['template']))
				{
					// Adding template paths
					$this->paths->top();
					$defaultPath = $this->paths->current();
					$this->paths->next();
					$templatePath = $this->paths->current();
					$this->_path['template'] = array($defaultPath, $templatePath);
				}*/

		//vmdebug('loadTemplate my $this->path ', $tP, $cP, $this->_path);

/*		$customPathList = $this->_path['template'];
		array_unshift($customPathList,$setPath[$layout]);
		vmdebug('Template unshift '.$file,$this->_path['template'], $customPathList);
		// Load the template script
		jimport('joomla.filesystem.path');
		$filetofind = $this->_createFileName('template', array('name' => $file));
		$this->_template = JPath::find($customPathList, $filetofind);

		// If alternate layout can't be found, fall back to default layout
		if ($this->_template == false)
		{
			$filetofind = $this->_createFileName('', array('name' => 'default' . (isset($tpl) ? '_' . $tpl : $tpl)));
			$this->_template = JPath::find($this->_path['template'], $filetofind);
		}

		if ($this->_template != false)
		{
			// Unset so as not to introduce into template scope
			unset($tpl, $file);

			// Never allow a 'this' property
			if (isset($this->this))
			{
				unset($this->this);
			}

			// Start capturing output into a buffer
			ob_start();

			// Include the requested template filename in the local scope
			// (this will execute the view logic).
			include $this->_template;

			// Done with the requested template; get the buffer and
			// clear it.
			$this->_output = ob_get_contents();
			ob_end_clean();

			return $this->_output;
		}
		else
		{
			throw new Exception(JText::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $this->_template), 500);
		}
	}
*/
	function prepareContinueLink($product=false){

		$virtuemart_category_id = shopFunctionsF::getLastVisitedCategoryId ();
		$categoryStr = '';

		if (empty($virtuemart_category_id) and $product) {
			$virtuemart_category_id = $product->canonCatId;
			vmdebug('Using product canon cat ',$virtuemart_category_id);
		}

		$ItemidStr = '';
		$Itemid = shopFunctionsF::getLastVisitedItemId();
		if(!empty($Itemid)){
			$ItemidStr = '&Itemid='.$Itemid;
		}

		$itemStr = '';
		$backTo = VmConfig::get('continueBackToCategory','category');
		if($backTo=='category' or $backTo=='both'){
			if ($virtuemart_category_id) {
				$itemStr = '&view=category&virtuemart_category_id=' . $virtuemart_category_id;
			}
			//$this->continue_link = JRoute::_('index.php?option=com_virtuemart' . $itemStr.$ItemidStr);
			$this->continue_cat_link = JRoute::_('index.php?option=com_virtuemart' . $itemStr.$ItemidStr);
		}
		if($backTo=='product' or $backTo=='both'){
			$cart = VirtueMartCart::getCart();
			if($cart->lastAddedProduct){
				$virtuemart_lastproduct_id = $cart->lastAddedProduct;
			} else {
				$virtuemart_lastproduct_ids = shopFunctionsF::getRecentProductIds();
				$virtuemart_lastproduct_id = current($virtuemart_lastproduct_ids);
			}

			if ($virtuemart_lastproduct_id) {
				$itemStr = '&view=productdetails&virtuemart_product_id=' . $virtuemart_lastproduct_id;
			}
			//$this->continue_link = JRoute::_('index.php?option=com_virtuemart' . $itemStr.$ItemidStr);
			$this->continue_prod_link = JRoute::_('index.php?option=com_virtuemart' . $itemStr.$ItemidStr);
		}


		if(VmConfig::get('sef_for_cart_links', false)){
			$this->useSSL = vmURI::useSSL();

			$this->continue_link = JRoute::_('index.php?option=com_virtuemart' . $itemStr.$ItemidStr);
			$this->cart_link = JRoute::_('index.php?option=com_virtuemart&view=cart',false,$this->useSSL);
		} else {
			$lang = '';
			if(VmLanguage::$jLangCount>1 and !empty(VmConfig::$vmlangSef)){
				$lang = '&lang='.VmConfig::$vmlangSef;
			}

			$this->continue_link = JURI::root() .'index.php?option=com_virtuemart' . $itemStr.$lang.$ItemidStr;

			$juri = JUri::getInstance();
			$uri = $juri->toString(array( 'host', 'port'));

			$scheme = $juri->toString(array( 'scheme'));
			$scheme = substr($scheme,0,-3);
			if($scheme!='https' and $this->useSSL){
				$scheme .='s';
			}
			$this->cart_link = $scheme.'://'.$uri. JURI::root(true).'/index.php?option=com_virtuemart&view=cart'.$lang;
		}

		$this->continue_link_html = '<a class="continue_link" href="' . $this->continue_link . '">' . vmText::_ ('COM_VIRTUEMART_CONTINUE_SHOPPING') . '</a>';

		return;
	}

	function linkIcon( $link, $altText, $boutonName, $verifyConfigValue = false, $modal = true, $use_icon = true, $use_text = false, $class = ''){
		if ($verifyConfigValue) {
			if ( !VmConfig::get($verifyConfigValue, 0) ) return '';
		}
		$folder = 'media/system/images/'; //shouldn't be root slash before media, as it automatically tells to look in root directory, for media/system/ which is wrong it should append to root directory.
		$text='';
		if ( $use_icon ) $text .= JHtml::_('image', $folder.$boutonName.'.png',  vmText::_($altText), null, false, false); //$folder shouldn't be as alt text, here it is: image(string $file, string $alt, mixed $attribs = null, boolean $relative = false, mixed $path_rel = false) : string, you should change first false to true if images are in templates media folder
		if ( $use_text ) $text .= '&nbsp;'. vmText::_($altText);
		if ( $text=='' )  $text .= '&nbsp;'. vmText::_($altText);
		if ($modal) return '<a '.$class.' class="modal" rel="{handler: \'iframe\', size: {x: 700, y: 550}}" title="'. vmText::_($altText).'" href="'.JRoute::_($link, FALSE).'">'.$text.'</a>';
		else 		return '<a '.$class.' title="'. vmText::_($altText).'" href="'.JRoute::_($link, FALSE).'">'.$text.'</a>';
	}

	public function escape($var) {
		return htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
	}

}