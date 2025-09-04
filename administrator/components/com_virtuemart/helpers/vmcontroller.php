<?php
defined ('_JEXEC') or die();

/**
 * abstract controller class containing get,store,delete,publish and pagination
 *
 *
 * This class provides the functions for the calculatoins
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2011 - 2019 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

class VmController extends JControllerLegacy{

	protected $_cidName = 0;
	protected $_cname = 0;

	protected $mainLangKey = null;
	protected $redirectPath = null;
	protected $document = null;
	protected $baseurl = null;

	/**
	 * Sets automatically the shortcut for the language and the redirect path
	 *
	 * @author Max Milbers
	 */
	public function __construct($cidName='cid', $config=array()) {
		parent::__construct($config);

		$this->_cidName = $cidName;

		$this->registerTask( 'add',  'edit' );
		$this->registerTask('apply','save');

		//VirtuemartController
		$this->_cname = strtolower(substr(get_class( $this ), 20));
		$this->mainLangKey = vmText::_('COM_VIRTUEMART_'.strtoupper($this->_cname));
		$this->redirectPath = 'index.php?option=com_virtuemart&view='.$this->_cname;
		$t = vRequest::getCmd( 'task');
		$task = explode ('.',$t);
		if ($task[0] == 'toggle') {
			$this->registerTask($t,'toggle');
		}

		if(VmConfig::isSiteByApp() ){

			$this->addViewPath(VMPATH_ADMIN .'/views');
			$this->basePath = VMPATH_ROOT.'/administrator/components/com_virtuemart';
		}

	}

	/**
	* Typical view method for MVC based architecture
	*
	* This function is provide as a default implementation, in most cases
	* you will need to override it in your own controllers.
	*
	* For the virtuemart core, we removed the "Get/Create the model"
	*
	* @param   boolean  $cachable   If true, the view output will be cached
	* @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	*
	* @return  JController  A JController object to support chaining.
	* @since   11.1
	*/
	public function display($cachable = false, $urlparams = false)
	{
		$this->document	= JFactory::getDocument();
		$viewType	= $this->document->getType();

		$viewName	= vRequest::getCmd('view', $this->default_view);
		$viewLayout	= vRequest::getCmd('layout', 'default');

		$view = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath));

		if(VmConfig::isSiteByApp()){
			$view->addTemplatePath(VMPATH_ADMIN.'/views/'.$viewName.'/tmpl');
		}

		// Set the layout
		$view->setLayout($viewLayout);


		$conf = JFactory::getConfig();

		// Display the view
		if ($cachable && $viewType != 'feed' && $conf->get('caching') >= 1) {
			$option	= vRequest::getCmd('option');
			$cache	= VmConfig::getCache($option, 'view');

			if (is_array($urlparams)) {
				$app = JFactory::getApplication();

				$registeredurlparams = $app->get('registeredurlparams');

				if (empty($registeredurlparams)) {
					$registeredurlparams = new stdClass;
				}

				foreach ($urlparams as $key => $value)
				{
					// Add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
					$registeredurlparams->{$key} = $value;
				}

				$app->set('registeredurlparams', $registeredurlparams);
			}

			$cache->get($view, 'display');

		}
		else {
			$view->display();
		}

		return $this;
	}


	/**
	 * Generic edit task
	 *
	 * @author Max Milbers
	 */
	function edit($layout='edit'){

		vRequest::setVar('controller', $this->_cname);
		vRequest::setVar('view', $this->_cname);
		vRequest::setVar('layout', $layout);

		$this->addViewPath(VMPATH_ADMIN .'/views');
		$document = JFactory::getDocument();
		$viewType = $document->getType();
		$view = $this->getView($this->_cname, $viewType);

		$view->setLayout($layout);

		$this->display();
	}

	function getStrByAcl(array $names, &$data=0){

		if($data==0) $data = array();

		foreach($names as $name){
			$data[$name] = vRequest::getHtml($name,'', $_POST);
		}

		return $data;
	}

	/**
	 * Generic save task
	 *
	 * @author Max Milbers
	 * @param post $data sometimes we just want to override the data to process
	 */
	function save($data = 0){

		vRequest::vmCheckToken();

		if($data===0) $data = vRequest::getPost();

		$model = $this->getModel($this->_cname);
		$id = $model->store($data);

		if(!empty($id)) {
			vmInfo('COM_VIRTUEMART_STRING_SAVED',$this->mainLangKey);
			$type = 'message';
		}

		$redir = $this->redirectPath;

		if( VmConfig::isSiteByApp()){
			$redir .= '&tmpl=component';
		}

		$task = vRequest::getCmd('task');

		if($task == 'apply'){
			$redir .= '&task=edit&'.$this->_cidName.'[]='.$id;
		}

		$this->setRedirect($redir);
	}

	/**
	 * Generic remove task
	 *
	 * @author Max Milbers
	 */
	function remove(){

		vRequest::vmCheckToken();

		$ids = vRequest::getVar($this->_cidName, vRequest::getInt('cid', array() ));

		$type = 'notice';
		if(count($ids) < 1) {
			$msg = vmText::_('COM_VIRTUEMART_SELECT_ITEM_TO_DELETE');

		} else {
			$model = $this->getModel($this->_cname);
			$ret = $model->remove($ids);

			$msg = vmText::sprintf('COM_VIRTUEMART_STRING_DELETED',$this->mainLangKey);
			if($ret==false) {
				$msg = vmText::sprintf('COM_VIRTUEMART_STRING_COULD_NOT_BE_DELETED',$this->mainLangKey);
						$type = 'error';
			}
		}

		if($type == 'error'){
			vmError($msg,$msg);
		} else{
			vmInfo($msg);
		}
		$this->setRedirect($this->redirectPath);
	}

	/**
	 * Generic cancel task
	 *
	 * @author Max Milbers
	 */
	public function cancel(){
		vmInfo('COM_VIRTUEMART_STRING_CANCELLED',$this->mainLangKey); //'COM_VIRTUEMART_OPERATION_CANCELED'
		$this->setRedirect($this->redirectPath);
	}

	/**
	 * Handle the toggle task
	 *
	 * @author Max Milbers , Patrick Kohl
	 */

	public function toggle($field = 0, $val=null){

		vRequest::vmCheckToken();

		$task = explode ('.',vRequest::getCmd( 'task'));
		if ($task[0] == 'toggle') {
			$val = (isset($task[2])) ? $task[2] : NULL;
			$field = $task[1];
		}
		$this->_cidName = vRequest::getCmd('cidName', $this->_cidName);

		$model = $this->getModel($this->_cname);
		if (!$model->toggle($field, $val, $this->_cidName, 0, $this->_cname)) {
			vmInfo('COM_VIRTUEMART_STRING_TOGGLE_ERROR',$this->mainLangKey);
		} else{
			vmInfo('COM_VIRTUEMART_STRING_TOGGLE_SUCCESS',$this->mainLangKey);
		}

		$this->setRedirect( $this->getRedirectPath());
	}

	/**
	 * Handle the publish task
	 *
	 * @author Jseros, Max Milbers
	 */
	public function publish($cidname=0,$table=0,$redirect = 0){

		vRequest::vmCheckToken();

		$model = $this->getModel($this->_cname);

		if($cidname === 0) $cidname = $this->_cidName;

		if (!$model->toggle('published', 1, $cidname, $table, $this->_cname)) {
			vmInfo('COM_VIRTUEMART_STRING_PUBLISHED_ERROR',$this->mainLangKey);
		} else{
			vmInfo('COM_VIRTUEMART_STRING_PUBLISHED_SUCCESS',$this->mainLangKey);
		}

		if($redirect === 0) $redirect = $this->redirectPath;

		$this->setRedirect( $redirect );
	}


	/**
	 * Handle the publish task
	 *
	 * @author Max Milbers, Jseros
	 */
	function unpublish($cidname=0,$table=0,$redirect = 0){

		vRequest::vmCheckToken();

		$model = $this->getModel($this->_cname);

		if($cidname === 0) $cidname = $this->_cidName;

		if (!$model->toggle('published', 0, $cidname, $table, $this->_cname)) {
			vmInfo('COM_VIRTUEMART_STRING_UNPUBLISHED_ERROR',$this->mainLangKey);
		} else{
			vmInfo('COM_VIRTUEMART_STRING_UNPUBLISHED_SUCCESS',$this->mainLangKey);
		}

		if($redirect === 0) $redirect = $this->redirectPath;

		$this->setRedirect( $redirect);
	}

	function orderup() {

		vRequest::vmCheckToken();

		$model = $this->getModel($this->_cname);
		$model->move(-1);
		vmInfo('COM_VIRTUEMART_STRING_ORDER_UP_SUCCESS',$this->mainLangKey);
		$this->setRedirect( $this->redirectPath);
	}

	function orderdown() {

		vRequest::vmCheckToken();

		$model = $this->getModel($this->_cname);
		$model->move(1);
		vmInfo('COM_VIRTUEMART_STRING_ORDER_DOWN_SUCCESS',$this->mainLangKey);
		$this->setRedirect( $this->redirectPath);
	}

	function saveOrder() {

		vRequest::vmCheckToken();

		$cid 	= vRequest::getInt( $this->_cidName, vRequest::getInt('cid', array() ) );
		$order 	= vRequest::getInt( 'order', array() );

		$model = $this->getModel($this->_cname);
		if (!$model->saveOrder($cid, $order)) {
			$msg = 'error';
		} else {
			if(!VmConfig::isSite() and VmConfig::showDebug()){
				vmInfo('COM_VIRTUEMART_NEW_ORDERING_SAVEDF',$this->mainLangKey);
			} else {
				vmInfo('COM_VIRTUEMART_NEW_ORDERING_SAVED');
			}

		}
		$this->setRedirect( $this->redirectPath);
	}

	/**
	 * This function just overwrites the standard joomla function, using our standard class VmModel
	 * for this
	 * @see JController::getModel()
	 */
	public function getModel($name = '', $prefix = '', $config = array()){

		if(empty($name)) $name = false;
		return VmModel::getModel($name);
	}

	function getRedirectPath(){

		$rView = vRequest::getCmd('rview', $this->_cname);
		$rTask = vRequest::getCmd('rtask', false);
		$rlayout = vRequest::getCmd('rlayout', false);
		$rId = vRequest::getInt($this->_cidName,false);

		$p = 'index.php?option=com_virtuemart&view='.$rView;
		if($rTask){
			$p .= '&task='.$rTask;
		}
		if($rlayout){
			$p .= '&layout='.$rlayout;
		}
		if($rId){
			if(is_array($rId)){
				reset($rId);
				$rId = current($rId);
			}
			$p .= '&'.$this->_cidName.'='.$rId;
		}

		return $p;
	}

}