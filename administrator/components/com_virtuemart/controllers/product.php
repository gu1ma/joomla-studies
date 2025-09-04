<?php
/**
 *
 * Product controller
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2022 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: product.php 10979 2024-03-18 08:28:46Z  $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Product Controller
 *
 * @package    VirtueMart
 * @author
 */
class VirtuemartControllerProduct extends VmController {

	/**
	 * Method to display the view
	 *
	 * @access	public
	 * @author
	 */
	function __construct() {
		parent::__construct('virtuemart_product_id');
		$this->addViewPath( VMPATH_ADMIN .'/views');
	}


	/**
	 * Shows the product add/edit screen
	 */
	public function edit($layout='edit') {
		parent::edit('product_edit');
	}

	/**
	 * We want to allow html so we need to overwrite some request data
	 *
	 * @author Max Milbers
	 */
	function save($data = 0){

		if($data===0)$data = vRequest::getPost();

		$this->getStrByAcl(array('product_name','product_desc','product_s_desc','customtitle', 'customfield_params'),$data);

		if(isset($data['field'])){
			$data['field'] = vRequest::getHtml('field');
		}

		if(isset($data['childs'])){
			foreach($data['childs'] as $k=>$v){
				if($n = vRequest::getHtml('product_name',false, $data['childs'][$k])){
					$data['childs'][$k]['product_name'] = $n;
				}
			}
		}

		parent::save($data);
	}

	function saveJS(){

		vRequest::vmCheckToken();

		$model = VmModel::getModel($this->_cname);

		$data = vRequest::getPost();
		$id = $model->store($data);

		$msg = 'failed';
		if(!empty($id)) {
			$msg = vmText::sprintf('COM_VIRTUEMART_STRING_SAVED',$this->mainLangKey);
			$type = 'message';
		}
		else $type = 'error';

		$json['msg'] = $msg;
		if ($id) {
			$json['product_id'] = $id;

			$json['ok'] = 1 ;
		} else {
			$json['ok'] = 0 ;

		}
		echo vmJsApi::safe_json_encode($json);
		jExit();

	}

	/**
	 * This task creates a child by a given product id
	 *
	 * @author Max Milbers
	 */
	public function createChild(){

		vRequest::vmCheckToken();

		$app = Jfactory::getApplication();

		$model = VmModel::getModel('product');

		$cids = vRequest::getInt($this->_cidName, vRequest::getInt('virtuemart_product_id',vRequest::getInt('product_parent_id',false)));
		if(!is_array($cids) and $cids > 0){
			$cids = array($cids);
		} else {
			$cids = array_unique($cids);
		}
		$target = vRequest::getCmd('target',false);

		$msgtype = 'info';
		foreach($cids as $cid){
			$cid = (int) $cid;
			if ($id=$model->createChild($cid)){
				$msg = vmText::_('COM_VIRTUEMART_PRODUCT_CHILD_CREATED_SUCCESSFULLY');


				if($target=='parent'){
					$redirect = 'index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$cid;
				} else {
					$redirect = 'index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$id;
				}

			} else {
				$msg = vmText::_('COM_VIRTUEMART_PRODUCT_NO_CHILD_CREATED_SUCCESSFULLY');
				$msgtype = 'error';
				$redirect = 'index.php?option=com_virtuemart&view=product';
			}
		}
		if($msgtype == 'error'){
			vmError($msg,$msg);
		} else {
			vmInfo($msg);
		}
		$app->redirect($redirect);

	}


	public function massxref_sgrps(){

		$this->massxref('massxref');
	}

	public function massxref_sgrps_exe(){

		$virtuemart_shoppergroup_ids = vRequest::getInt('virtuemart_shoppergroup_id', array() );
		$massxref_task = vRequest::getCmd('massxref_task', 'add' );

		$session = JFactory::getSession();
		$pids = vmJsApi::safe_json_decode($session->get('vm_product_ids', array(), 'vm'),true);

		$productModel = VmModel::getModel('product');

		foreach($pids as $pid){
			if ($massxref_task != 'replace') {
				$db = JFactory::getDBO ();
				$db->setQuery (' SELECT `virtuemart_shoppergroup_id` FROM `#__virtuemart_product_shoppergroups` WHERE `virtuemart_product_id` =' . (int)$pid);
				$pcats = $db->loadColumn ();
				if ($massxref_task == 'add') {
					$massxref_sgrps = array_unique(array_merge($virtuemart_shoppergroup_ids, $pcats));
				} else if ($massxref_task == 'remove') {
					$massxref_sgrps = array_diff($pcats, $virtuemart_shoppergroup_ids);
				}
			} else {
				$massxref_sgrps = $virtuemart_shoppergroup_ids;
			}
			$has_sgrps = !empty($massxref_sgrps);
			$data = array('virtuemart_product_id' => $pid, 'virtuemart_shoppergroup_id' => $massxref_sgrps, 'has_shoppergroups' => $has_sgrps);
			$data = $productModel->updateXrefAndChildTables ($data, 'product_shoppergroups');
		}

		$this->massxref('massxref_sgrps');
	}

	public function massxref_cats(){
		$this->massxref('massxref');
	}

	public function massxref_cats_exe(){

		$virtuemart_cat_ids = vRequest::getInt('cid', array() );
		$massxref_task = vRequest::getCmd('massxref_task', 'add' );

		$session = JFactory::getSession();
		$pids = vmJsApi::safe_json_decode($session->get('vm_product_ids', array(), 'vm'),true);

		$productModel = VmModel::getModel('product');

		foreach($pids as $pid){
			if ($massxref_task != 'replace') {
				$db = JFactory::getDBO ();
				$db->setQuery (' SELECT `virtuemart_category_id` FROM `#__virtuemart_product_categories` WHERE `virtuemart_product_id` =' . (int)$pid);
				$pcats = $db->loadColumn ();
				if ($massxref_task == 'add') {
					$massxref_cats = array_unique(array_merge($virtuemart_cat_ids, $pcats));
				} else if ($massxref_task == 'remove') {
					$massxref_cats = array_diff($pcats, $virtuemart_cat_ids);
				}
			} else {
				$massxref_cats = $virtuemart_cat_ids;
			}
			$has_categories = !empty($massxref_cats);
			$data = array('virtuemart_product_id' => $pid, 'virtuemart_category_id' => $massxref_cats, 'has_categories' => $has_categories);
			$data = $productModel->updateXrefAndChildTables ($data, 'product_categories',TRUE);
		}

		$this->massxref('massxref_cats');
	}

	public function massxref($layoutName){

		vRequest::vmCheckToken();

		$cids = vRequest::getInt('virtuemart_product_id');

		if(empty($cids)){
			$session = JFactory::getSession();
			$cids = vmJsApi::safe_json_decode($session->get('vm_product_ids', '', 'vm'),true);
		} else {
			$session = JFactory::getSession();
			$session->set('vm_product_ids', json_encode($cids),'vm');
			$session->set('reset_pag', true,'vm');

		}

		if(!empty($cids)){
			$q = 'SELECT `product_name` FROM `#__virtuemart_products_' . VmConfig::$vmlang . '` ';
			$q .= ' WHERE `virtuemart_product_id` IN (' . implode(',', $cids) . ')';

			$db = JFactory::getDbo();
			$db->setQuery($q);

			$productNames = $db->loadColumn();

			vmInfo('COM_VIRTUEMART_PRODUCT_XREF_NAMES',implode(', ',$productNames));
		}

		$this->addViewPath(VMPATH_ADMIN .'/views');
		$document = JFactory::getDocument();
		$viewType = $document->getType();
		$view = $this->getView($this->_cname, $viewType);

		$view->setLayout($layoutName);

		$view->display();
	}

	/**
	 * Clone a product
	 *
	 * @author Max Milbers
	 */
	public function CloneProduct() {
		$app = Jfactory::getApplication();

		$model = VmModel::getModel('product');
		$msgtype = '';

		$cids = vRequest::getInt($this->_cidName, vRequest::getInt('virtuemart_product_id'));
		if(is_array($cids)){
			$cids = array_unique($cids);
		} else {
			$cids = (array)$cids;
		}
		$msg = '';
		foreach($cids as $cid){
			$cid = (int) $cid;
			if ($cid and $l=$model->createClone($cid)) {
				$msg .= vmText::_('COM_VIRTUEMART_PRODUCT_CLONED_SUCCESSFULLY');
			} else {
				$msg .= vmText::_('COM_VIRTUEMART_PRODUCT_NOT_CLONED_SUCCESSFULLY');
				$msgtype = 'error';
			}
		}
		if($msgtype == 'error'){
			vmError($msg,$msg);
		} else {
			vmInfo($msg);
		}
		$app->redirect('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$l);
	}

	/**
	 * Clone a product
	 *
	 * @author Max Milbers
	 */
	public function CloneProductWithChildren () {
		$app = Jfactory::getApplication();

		$model = VmModel::getModel('product');
		$msgtype = '';

		$cids = vRequest::getInt($this->_cidName, vRequest::getInt('virtuemart_product_id'));
		if(is_array($cids)){
			$cids = array_unique($cids);
		} else {
			$cids = (array)$cids;
		}
		$msg = '';
		foreach($cids as $cid){
			$cid = (int) $cid;
			if ($cid and $l=$model->createCloneWithChildren($cid)) {
				$msg .= vmText::_('COM_VIRTUEMART_PRODUCT_CLONED_SUCCESSFULLY');
			} else {
				$msg .= vmText::_('COM_VIRTUEMART_PRODUCT_NOT_CLONED_SUCCESSFULLY');
				$msgtype = 'error';
			}
		}

		if($msgtype == 'error'){
			vmError($msg,$msg);
		} else {
			vmInfo($msg);
		}
		$app->redirect('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$l);
	}

	/**
	 * Get a list of related products, categories
	 * or customfields
	 * @author Max Milbers
	 * @author Kohl Patrick
	 */
	public function getData() {
		$view = $this->getView('product', 'json');
		$view->display(NULL);
	}

	/**
	 * Add a product rating
	 * @author Max Milbers
	 */
	public function addRating() {
		$mainframe = Jfactory::getApplication();

		// Get the product ID
		$cids = vRequest::getInt($this->_cidName, vRequest::getInt('virtuemart_product_id'));
		$mainframe->redirect('index.php?option=com_virtuemart&view=ratings&task=add&virtuemart_product_id='.$cids[0]);
	}


	public function ajax_notifyUsers(){

		$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
		if(is_array($virtuemart_product_id) and count($virtuemart_product_id) > 0){
			$virtuemart_product_id = (int)$virtuemart_product_id[0];
		} else {
			$virtuemart_product_id = (int)$virtuemart_product_id;
		}

		$subject = vRequest::getVar('subject', '');
		$mailbody = vRequest::getVar('mailbody',  '');
		$max_number = (int)vRequest::getVar('max_number', '');
		
		$waitinglist = VmModel::getModel('Waitinglist');
		$waitinglist->notifyList($virtuemart_product_id,$subject,$mailbody,$max_number);
		exit;
	}
	
	public function ajax_waitinglist() {

		$virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
		if(is_array($virtuemart_product_id) && count($virtuemart_product_id) > 0){
			$virtuemart_product_id = (int)$virtuemart_product_id[0];
		} else {
			$virtuemart_product_id = (int)$virtuemart_product_id;
		}

		$waitinglistmodel = VmModel::getModel('waitinglist');
		$waitinglist = $waitinglistmodel->getWaitingusers($virtuemart_product_id);

		if(empty($waitinglist)) $waitinglist = array();
		
		echo vmJsApi::safe_json_encode($waitinglist);
		exit;

	}

	static $output_dir = null;
	static $imagepath = null;
	//multiimag upload or drag and drop options
	public function multifileimageupload() {

    $virtuemart_product_id = vRequest::getInt('virtuemart_product_id');
    if(is_array($virtuemart_product_id)) $virtuemart_product_id = reset($virtuemart_product_id);

    //If directory doesnot exists create it.
    if(self::$output_dir === null) {
      $path = VmConfig::get('media_product_path','');
      if(is_dir(JPATH_ROOT.'/'.$path)) {
        self::$output_dir = JPATH_ROOT.'/'.$path;
        self::$imagepath = $path;
      }
    }


    if(isset($_FILES["myfile"])) {
      $ret = array();

      $error =$_FILES["myfile"]["error"];

      if(!is_array($_FILES["myfile"]['name'])) { //single file

        self::moveUploadedFileAndXRef($virtuemart_product_id, $_FILES['myfile'], $ret);

      } else {
      
        $fileCount = count($_FILES["myfile"]['name']);
        for($i=0; $i < $fileCount; $i++) {
          $file = array();
          $file['name'] = $_FILES['myfile'][$i]['name'];
          $file['type'] = $_FILES['myfile'][$i]['type'];
          $file['tmp_name'] = $_FILES['myfile'][$i]['tmp_name'];
          self::moveUploadedFileAndXRef($virtuemart_product_id, $file, $ret);
        }
      }

      echo json_encode($ret); die;

    }

	}
	//multiimag upload or drag and drop options

	static function moveUploadedFileAndXRef($virtuemart_product_id, $file, &$ret){
		$RandomNum   = '';//time();
		$ImageName      = vmFile::makeSafe($file['name']);
		//$ImageType      = $_FILES['myfile']['type']; //"image/png", image/jpeg etc.
		$mediaPure = JFile::stripExt($ImageName);
		$mediaExtension = strtolower(JFile::getExt($ImageName));
		
		if(empty($mediaExtension)){

			vmError('Invalid media; no extension '.$ImageName);
			return false;
	    }

		$overwrite = false;
	    if(!$overwrite){
	      $i = 0;
	      while (file_exists(self::$output_dir.$mediaPure.'.'.$mediaExtension) and $i<20) {

	        $mediaPure = $mediaPure.rand(1,9);
	        $i++;
	      }
	    }
    
        $NewImageName = $mediaPure.'.'.$mediaExtension;
        //$uploaded_Data = JFile::upload($_FILES["myfile"]["tmp_name"], self::$output_dir.$NewImageName, false, vmAccess::manager('media.trusteduploader'));
									
		//$NewImageName = $ImageName.'-'.$RandomNum.'.'.$mediaExtension;
		$uploaded_Data = JFile::upload($file["tmp_name"], self::$output_dir.$NewImageName, false, vmAccess::manager('media.trusteduploader'));
		//move_uploaded_file($file["tmp_name"],self::$output_dir. $NewImageName);

		$file_title = $file['name'];
		$file_type = $file['type'];
		$mediaModel = VmModel::getModel('media');
		$img_file_path = self::$imagepath. $NewImageName;
		$mediaUpload = $mediaModel->saveMultiImageMedia($virtuemart_product_id, $file_title, $img_file_path, $file_type, 'product');
		$ret['$NewImageName'] =  self::$output_dir.$NewImageName;

	}
}
// pure php no closing tag
