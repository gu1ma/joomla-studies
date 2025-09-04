<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2022 VirtueMart Team. All rights reserved by the author.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id:$
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;

/**
 * Model for VirtueMart Customs Fields
 *
 * @package        VirtueMart
 */
class VirtueMartModelCustomfields extends VmModel {

	/** @var array For roundable values */
	static $dimensions = array('product_length','product_width','product_height','product_weight');
	static $useAbsUrls = false;
	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 *
	 * @author Max Milbers
	 */
	function __construct () {

		parent::__construct ('virtuemart_customfield_id');
		$this->setMainTable ('product_customfields');
	}

	/**
	 * Gets a single custom by virtuemart_customfield_id
	 *
	 * @param string $type
	 * @param string $mime mime type of custom, use for exampel image
	 * @return customobject
	 */
	function getCustomfield ($id = 0) {

		return $this->getData($id);

	}

	public static function getProductCustomSelectFieldList(){

		$q = 'SELECT c.`virtuemart_custom_id`, c.`custom_parent_id`, c.`virtuemart_vendor_id`, c.`custom_jplugin_id`, c.`custom_element`, c.`admin_only`, c.`custom_title`, c.`show_title` , c.`custom_tip`,
		c.`custom_value`, c.`custom_desc`, c.`field_type`, c.`is_list`, c.`is_hidden`, c.`is_cart_attribute`, c.`is_input`, c.`searchable`, c.`layout_pos`, c.`custom_params`, c.`shared`, c.`published`, c.`ordering`, c.`virtuemart_shoppergroup_id`, ';
		$q .= 'field.`virtuemart_customfield_id`, field.`virtuemart_product_id`, field.`customfield_value`, field.`customfield_price`,
		field.`customfield_params`, field.`published` as fpublished, field.`override`, field.`disabler`, field.`noninheritable`, field.`ordering`,
		field.`product_sku`, field.`product_gtin`, field.`product_mpn`
		FROM `#__virtuemart_customs` AS c LEFT JOIN `#__virtuemart_product_customfields` AS field ON c.`virtuemart_custom_id` = field.`virtuemart_custom_id` ';
		return $q;
	}


	public static function getCustomEmbeddedProductCustomField($virtuemart_customfield_id){

		static $_customFieldById = array();

		if(!isset($_customFieldById[$virtuemart_customfield_id])){
			$db= JFactory::getDBO ();
			$q = VirtueMartModelCustomfields::getProductCustomSelectFieldList();
			if($virtuemart_customfield_id){
				$q .= ' WHERE `virtuemart_customfield_id` ="' . (int)$virtuemart_customfield_id . '"';
			}
			$db->setQuery ($q);
			$_customFieldById[$virtuemart_customfield_id] = $db->loadObject ();
			if($_customFieldById[$virtuemart_customfield_id]){
				VirtueMartModelCustomfields::bindCustomEmbeddedFieldParams($_customFieldById[$virtuemart_customfield_id],$_customFieldById[$virtuemart_customfield_id]->field_type);
			}
		}

		return $_customFieldById[$virtuemart_customfield_id];
	}

	function getCustomEmbeddedProductCustomFields(array $productIds, $virtuemart_custom_id = 0, $cartattribute = -1, $forcefront = FALSE){

		if(empty($productIds)){
			//vmTrace('Empty product ids in getCustomEmbeddedProductCustomFields? '.implode(',', $productIds));
			return false;
		}

		$app = JFactory::getApplication();
		$db= JFactory::getDBO ();
		$q = VirtueMartModelCustomfields::getProductCustomSelectFieldList();

		static $_customFieldByProductId = array();

		$mainPrId = reset($productIds);

		$productIdsOrig = $productIds;
		$productCustomsCached = array();
		foreach($productIds as $k=>$productId){
			$hkey = (int)$productId.'_'.$virtuemart_custom_id;//.'_'.$cartattribute;
			//vmdebug('getCustomEmbeddedProductCustomFields $hkey '.$hkey);
			if(isset($_customFieldByProductId[$hkey])){

				//Must be cloned!
				foreach($_customFieldByProductId[$hkey] as $ccust){
					if(!empty($ccust)){
						$productCustomsCached[] = clone($ccust);
					}
				}
				//vmdebug('unset($productIds[$k] ',$productIds);
				unset($productIds[$k]);
			}
		}
		//vmdebug('getCustomEmbeddedProductCustomFields my cache '.$virtuemart_custom_id,$productIds);
		$isSite = VmConfig::isSite();

		if(!empty($productIds)){

			if(is_array($productIds) and count($productIds)>0){
				$q .= 'WHERE field.`virtuemart_product_id` IN ('.implode(',', $productIds).')';
			} else if(!empty($productIds)){
				$q .= 'WHERE field.`virtuemart_product_id` = "'.$productIds.'" ';
			}

			if(!empty($virtuemart_custom_id)){
				if(is_numeric($virtuemart_custom_id)){
					$q .= ' AND c.`virtuemart_custom_id`= "' . (int)$virtuemart_custom_id.'" ';
				} else {
					$virtuemart_custom_id = substr($virtuemart_custom_id,0,2); //just in case
					$q .= ' AND c.`field_type`= "' .$virtuemart_custom_id.'" ';
				}
			}
			//if(!empty($cartattribute)){
				//$q .= ' AND ( `is_cart_attribute` = 1 OR `is_input` = 1 OR `searchable` = 1) ';
			//}
			if($forcefront or $isSite){
				$q .= ' AND c.`published` = "1" ';
				$forcefront = true;
			}

			if(!empty($virtuemart_custom_id) and $virtuemart_custom_id!==0){
				$q .= ' ORDER BY field.`ordering` ASC';
			} else {
				/*if($forcefront or $app->isSite()){
					$q .= ' GROUP BY c.`virtuemart_custom_id`';
				}*/

				$q .= ' ORDER BY field.`ordering` ASC';
			}

			$db->setQuery ($q);
			try {
				$productCustoms = $db->loadObjectList ();
			} catch (Exception $e){
				vmError('getCustomEmbeddedProductCustomFields error in query '.$e->getMessage());
			}
			//vmdebug('getCustomEmbeddedProductCustomFields my $productCustoms '.$virtuemart_custom_id,$productCustoms);
			if($productCustoms and is_array($productCustoms)){

				foreach($productCustoms as $customfield){
					$hkey = (int)$customfield->virtuemart_product_id.'_'.$virtuemart_custom_id;//.'_'.$cartattribute;
					$_customFieldByProductId[$hkey][] = $customfield;

					$deleteKey = array_search($customfield->virtuemart_product_id, $productIds);
					if($deleteKey!==false) unset($productIds[$deleteKey]);
				}

				foreach($productIds as $id){
					$hkey = (int)$id.'_'.$virtuemart_custom_id;//.'_'.$cartattribute;
					$_customFieldByProductId[$hkey] = array();
				}
				$productCustoms = array_merge($productCustomsCached,$productCustoms);

			} else {
				foreach($productIds as $id){
					$hkey = (int)$id.'_'.$virtuemart_custom_id;//.'_'.$cartattribute;
					$_customFieldByProductId[$hkey] = array();
				}
				//$_customFieldByProductId[$hkey] = array();
				$productCustoms = $productCustomsCached;
			}

		} else {
			$productCustoms = $productCustomsCached;
		}

		//vmdebug('getCustomEmbeddedProductCustomFields my cache '.$virtuemart_custom_id,$_customFieldByProductId);

		if($productCustoms){

			if($cartattribute<0){
				$cartattribute = 0;
			}

			$customfield_ids = array();
			$customfield_override_ids = array();

			foreach($productCustoms as $field){

				if($cartattribute==1 and (!$field->is_cart_attribute and !$field->is_input and !$field->searchable) ){
					continue;
				}
				if($field->override!=0){
					$customfield_override_ids[] = $field->override;
				} else if ($field->disabler!=0) {

					$customfield_override_ids[] = $field->disabler;
					if($isSite){
						$customfield_override_ids[] = $field->virtuemart_customfield_id;
					}

				} else if($field->noninheritable!=0 and $field->virtuemart_product_id!=$mainPrId){

					$customfield_override_ids[] = $field->noninheritable;
				}

				$customfield_ids[] = $field->virtuemart_customfield_id;
			}
			$virtuemart_customfield_ids = array_unique( array_diff($customfield_ids,$customfield_override_ids));

			$virtuemart_shoppergroup_id = VirtueMartModelProduct::getCurrentUserShopperGrps();

			foreach ($productCustoms as $k =>$field) {

				if($isSite and !empty($field->virtuemart_shoppergroup_id)){

					if(!is_array($field->virtuemart_shoppergroup_id))$field->virtuemart_shoppergroup_id = explode(',', $field->virtuemart_shoppergroup_id);
					$diff = array_intersect($virtuemart_shoppergroup_id, $field->virtuemart_shoppergroup_id);

					if(count($diff)==0){
						unset($productCustoms[$k]);
						continue;
					}
				}

				if(in_array($field->virtuemart_customfield_id,$virtuemart_customfield_ids)){

					if($forcefront and $field->disabler){
						unset($productCustoms[$k]);
					} else {
						VirtueMartModelCustomfields::bindCustomEmbeddedFieldParams($productCustoms[$k],$field->field_type);
					}

				} else{
					unset($productCustoms[$k]);
				}
			}
			//vmdebug('my $productCustoms getCustomEmbeddedProductCustomFields',$productCustoms);
			return $productCustoms;
		} else {
			//vmTrace('No customfields for '.implode(',', $productIdsOrig));
			return array();
		}
	}


	static function bindCustomEmbeddedFieldParams(&$obj,$fieldtype){

		if ($obj->field_type == 'E') {
			if(!empty($obj->virtuemart_custom_id)){
				static $varsToPushPlg = array();
				if(isset($varsToPushPlg[$obj->virtuemart_custom_id])){
					$obj->_varsToPushParam = $varsToPushPlg[$obj->virtuemart_custom_id];
				} else {
					JPluginHelper::importPlugin ('vmcustom');
                    vDispatcher::directTrigger('vmcustom', $obj->custom_element, 'plgVmDeclarePluginParamsCustomVM3', array(&$obj), false);
					$varsToPushPlg[$obj->virtuemart_custom_id] = false;
					if(!empty($obj->_varsToPushParam)){
						$varsToPushPlg[$obj->virtuemart_custom_id] = $obj->_varsToPushParam;
					}
				}
			}
		} else {
			$obj->_varsToPushParam = VirtueMartModelCustom::getVarsToPush($fieldtype);
		}

		if(!empty($obj->_varsToPushParam)){
			VmTable::bindParameterable($obj,'custom_params',$obj->_varsToPushParam);

			$obj ->_xParams = 'customfield_params';
			VmTable::bindParameterable($obj,$obj->_xParams,$obj->_varsToPushParam);
		}

	}


	private function sortChildIds ($product_id, $childIds, $options, $sorted=array()){

		static $asorted = array();
		//vmdebug('sortChildIds',$product_id, $childIds);
		if(!empty($options)){
			foreach($options as $id => $v){
				if(empty($id)) continue;
				if($product_id!=$id){
					$sorted[] = array('parent_id'=>$product_id,'vm_product_id'=>$id);
					$asorted[$id] = 1;
				}

			}

		}

		foreach($childIds as $childIdKey => $childs){
			if(!is_array($childs)){

				if(empty($asorted[$childs])){
					$sorted[] = array('parent_id'=>$product_id,'vm_product_id'=>$childs);
				}

				if(isset($childIds[$childs]) and is_array($childIds[$childs])){
					$sorted = self::sortChildIds($childs, $childIds[$childs], $options, $sorted);
					//unset($childIds[$childs]);
				}
			} else {
				//$sorted = self::sortChildIds($childIdKey, $childs, $sorted);
			}
		}
		return $sorted;
	}


	private function renderProductChildLine($i,$line,$field,$productModel,$row,$showSku){

		if(empty($line['vm_product_id'])) return 'empty vm_product_id';
		$child = $productModel->getProductSingle($line['vm_product_id'],false);
		if(!$child) return 'Could not find product with id '.$line['vm_product_id'];
		if(empty($child->allPrices)){
			$child->allPrices[$child->selectedPrice]['product_price'] = '';
			$child->allPrices[$child->selectedPrice]['virtuemart_product_price_id'] = '';
		}


		$readonly = '';
		$classBox = 'class="inputbox"';
		if($line['parent_id'] == $line['vm_product_id']){
			$readonly = 'readonly="readonly"';
			$classBox = 'class="readonly"';
		}
		$linkLabel = $line['parent_id'] .'->'. $line['vm_product_id'].' ';
		$html = '<tr class="row'.(($i+1)%2).' removable">';
		$html .= '<td>'.JHTML::_('link', JRoute::_('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id='.$child->virtuemart_product_id), $linkLabel.$child->slug, array('title' => vmText::_('COM_VIRTUEMART_EDIT').' '.$child->slug)).'<span class="vmicon vmicon-16-move"></span></td>';
		if($showSku) $html .= '<td><input '.$readonly.' '.$classBox.' type="text" name="childs['.$child->virtuemart_product_id.'][product_sku]" id="child'.$child->virtuemart_product_id.'product_sku" size="20" maxlength="64" value="'.$child->product_sku .'" /></td>';
		$html .= '<td><input '.$readonly.' '.$classBox.' type="text" name="childs['.$child->virtuemart_product_id.'][product_gtin]" id="child'.$child->virtuemart_product_id.'product_gtin" size="13" maxlength="13" value="'.$child->product_gtin .'" /></td>';
		/*$html .= 	'<input type="hidden" name="childs['.$child->virtuemart_product_id .'][product_name]" id="child'.$child->virtuemart_product_id .'product_name" value="'.$child->product_name .'" />
					<input type="hidden" name="childs['.$child->virtuemart_product_id .'][slug]" id="child'.$child->virtuemart_product_id .'slug" value="'.$child->slug .'" />
					<input type="hidden" name="childs['.$child->virtuemart_product_id .'][product_parent_id]" id="child'.$child->virtuemart_product_id .'parent" value="'.$child->product_parent_id .'" />';
		*/
		//$html .= 	$child->product_name .'</td>';
		//$html .=	'<td>'.$child->allPrices[$child->selectedPrice]['product_price'] .'</td>';
		$html .= '<td><input '.$readonly.' '.$classBox.' style="width:80px;" type="text" name="childs['.$child->virtuemart_product_id.'][mprices][product_price][]" size="5" value="'. trim(trim($child->allPrices[$child->selectedPrice]['product_price'],'0'),'.') .'" />
		<input type="hidden" name="childs['. $child->virtuemart_product_id .'][mprices][virtuemart_product_price_id][]" value="'. $child->allPrices[$child->selectedPrice]['virtuemart_product_price_id'] .'"  ></td>';

		//We dont want to update always the stock, this would lead to wrong stocks, if the store has activity, while the vendor is editing the product
		//$html .= '<td><input '.$readonly.' '.$class.' type="text" name="childs['.$child->virtuemart_product_id.'][product_in_stock]" id="child'.$child->virtuemart_product_id.'product_in_stock" size="3" maxlength="6" value="'.$child->product_in_stock .'" /></td>';
		//$html .= '<td><input '.$readonly.' '.$class.' type="text" name="childs['.$child->virtuemart_product_id.'][product_in_stock]" id="child'.$child->virtuemart_product_id.'product_in_stock" size="3" maxlength="6" value="'.$child->product_in_stock .'" /></td>';
		$html .= '<td>'.$child->product_in_stock .'</td>';
		$html .= '<td>'.$child->product_ordered.'</td>';

		$product_id = $line['vm_product_id'];
		if(empty($field->selectoptions)) $field->selectoptions = array();

		foreach($field->selectoptions as $k=>$selectoption){

			$charCount = 0;
			$class = array();

			$options = explode("\n",$selectoption->values);
			foreach($options as $opt){
				$charCount = max($charCount,strlen($opt));
			}

			if($charCount){
				if($charCount<5) $charCount = 5;
				if($charCount<20) {
					$width = $charCount * 12;
				} else {
					$width = 20 * 12;
				}
			} else {
				$width = 100;
			}

			if($selectoption->voption=='clabels'){
				$name = 'field[' . $row . '][options]['.$product_id.']['.$k.']';
				$myoption = false;
				if(isset($field->options->{$product_id})){
					$myoption = $field->options->{$product_id};
				}

				if($myoption and is_array($myoption) and isset($myoption[$k])){
					$value = trim($myoption[$k]);
				} else {
					$value = '';
				}

				$idTag = 'cvarl.'.$product_id.'s'.$k;
			} else {
				$name = 'childs['.$product_id .']['.$selectoption->voption.']';
				$value = trim($child->{$selectoption->voption});
				$idTag = 'cvard.'.$product_id.'s'.$k;
				$class = array('class'=>'cvard');
			}

			$class['style'] = 'width:'.$width .'px';

			if(count($selectoption->comboptions)>0){
				$html .= '<td>'.JHtml::_ ('select.genericlist', $selectoption->comboptions, $name, $class, 'value', 'text',
				$value ,$idTag);
				if($selectoption->voption!='clabels'){
					$html .= '<input type="hidden" name="field[' . $row . '][options]['.$product_id.']['.$k.']" value="'.$value .'" />';
				}
				$html .= '</td>';
			}
		}
		$html .= '</tr>';
		return $html;
	}

	/**
	 * @author Max Milbers
	 * @param $field
	 * @param $product_id
	 * @param $row
	 */
	public function displayProductCustomfieldBE ($field, $product, $row) {

		//This is a kind of fallback, setting default of custom if there is no value of the productcustom
		$field->customfield_value = (!isset($field->customfield_value) or $field->customfield_value==='') ? $field->custom_value : $field->customfield_value;
		$field->customfield_price = empty($field->customfield_price) ? 0 : $field->customfield_price;

		if(is_object($product)){
			$product_id = $product->virtuemart_product_id;
			$virtuemart_vendor_id = $product->virtuemart_vendor_id;
		} else {

			$product_id = $product;
			$virtuemart_vendor_id = vmAccess::isSuperVendor();
			vmdebug('displayProductCustomfieldBE product was not object, use for productId '.$product_id.' and $virtuemart_vendor_id = '.$virtuemart_vendor_id);
		}
		//vmdebug('displayProductCustomfieldBE',$product_id,$field,$virtuemart_vendor_id,$product);
		//the option "is_cart_attribute" gives the possibility to set a price, there is no sense to set a price,
		//if the custom is not stored in the order.
		if ($field->is_input or $field->field_type == 'PB' ) {
			$vendor_model = VmModel::getModel('vendor');
			$vendor = $vendor_model->getVendor($virtuemart_vendor_id);
			$currency_model = VmModel::getModel('currency');
			$vendor_currency = $currency_model->getCurrency($vendor->vendor_currency);

			$priceInput = '<span style="white-space: nowrap;"><input type="text" size="12" style="text-align:right;" value="' . $field->customfield_price . '" name="field[' . $row . '][customfield_price]" /> '.$vendor_currency->currency_symbol."</span>";
		}
		else {
			$priceInput = ' ';
		}

		$serials = '';
		if(!empty($field->is_input)){
			if(isset($field->product_sku)){
				$serials = '<td><span style="white-space: nowrap;">'.vmText::_('COM_VIRTUEMART_PRODUCT_SKU').'<input type="text" size="12" style="text-align:right;" value="' . $field->product_sku . '" name="field[' . $row . '][product_sku]" /> </span></td>';
			}
			if(isset($field->product_gtin)){
				$serials .= '<td><span style="white-space: nowrap;">'.vmText::_('COM_VIRTUEMART_PRODUCT_GTIN').'<input type="text" size="12" style="text-align:right;" value="' . $field->product_gtin . '" name="field[' . $row . '][product_gtin]" /> </span></td>';
			}
			if(isset($field->product_mpn)){
				$serials .= '<td><span style="white-space: nowrap;">'.vmText::_('COM_VIRTUEMART_PRODUCT_MPN').'<input type="text" size="12" style="text-align:right;" value="' . $field->product_mpn . '" name="field[' . $row . '][product_mpn]" /> </span></td>';
			}
		}



		switch ($field->field_type) {

			case 'C':
				//vmdebug('displayProductCustomfieldBE $field',$field);
				//if(!isset($field->withParent)) $field->withParent = 0;
				//if(!isset($field->parentOrderable)) $field->parentOrderable = 0;
				//vmdebug('displayProductCustomfieldBE',$field,$product);

				if(!empty($product->product_parent_id) and $product->product_parent_id==$field->virtuemart_product_id){
					return 'controlled by parent';
				}

				$html = '';
				//$html = vmText::_('COM_VIRTUEMART_CUSTOM_WP').VmHTML::checkbox('field[' . $row . '][withParent]',$field->withParent,1,0,'');
				//$html .= vmText::_('COM_VIRTUEMART_CUSTOM_PO').VmHTML::checkbox('field[' . $row . '][parentOrderable]',$field->parentOrderable,1,0,'').'<br />';

				if(empty($field->selectoptions) or (is_object($field->selectoptions) and count(get_object_vars($field->selectoptions))==0)){
					$selectOption = new stdClass();	//The json conversts it anyway in an object, so suitable to use an object here
					$selectOption->voption = 'product_name';
					$selectOption->slabel = '';
					$selectOption->clabel = '';
					$selectOption->canonical = 0;
					$selectOption->values = '';
					$c = 0;
					$field->selectoptions = new stdClass();
					$field->selectoptions->{$c} = $selectOption;
					$field->options = new stdClass();

				} else if(is_array($field->selectoptions)){
					$field->selectoptions = (object)$field->selectoptions;
				}


				if(!empty($field->options) and is_array($field->options)){
					$field->options = (object)$field->options;
				}


				$optAttr = array();

				$optAttr[] = array('value' => '' ,'text' =>vmText::_('COM_VIRTUEMART_LIST_EMPTY_OPTION'));
				$optAttr[] = array('value' => 'product_name' ,'text' =>vmText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME'));
				$optAttr[] = array('value' => 'product_sku', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_SKU'));
				$optAttr[] = array('value' => 'slug', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_ALIAS'));
				$optAttr[] = array('value' => 'product_length', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
				$optAttr[] = array('value' => 'product_width', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
				$optAttr[] = array('value' => 'product_height', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
				$optAttr[] = array('value' => 'product_weight', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));
				$optAttr[] = array('value' => 'clabels', 'text' => vmText::_ ('COM_VIRTUEMART_CLABELS'));


				$productModel = VmModel::getModel('product');

				$childIds = array();
				$sorted = array();

				$productModel->getAllProductChildIds($product_id,$childIds);

				if(isset($childIds[$product_id])){
					$sorted = self::sortChildIds($product_id,$childIds[$product_id],$field->options);
				}

				array_unshift($sorted,  array('parent_id' => $product_id, 'vm_product_id' => $product_id));

				$showSku = true;

				$k = 0;
				if(empty($field->selectoptions)) $field->selectoptions = array();
				foreach($field->selectoptions as $k=>&$soption){
					$options = array();

					$added = array();

					if($soption->voption!='clabels'){

						foreach($sorted as $i=>$vmProductId){
							if(empty($vmProductId) or $vmProductId['vm_product_id']==$product_id){
								continue;
							}
							$product = $productModel->getProductSingle($vmProductId['vm_product_id'],false);

							if(empty($product->virtuemart_vendor_id) and empty($product->slug)){
								unset($sorted[$i]);
								continue;
							}

							$voption = trim($product->{$soption->voption});

							if(!empty($voption)) {
								$found = false;
								//Guys, dont tell me about in_array or array_search, it does not work here
								foreach($added as $add){
									if($add == $voption){
										$found = true;
									}
								}
								if(!$found){
									$added[] = $voption;
								}
							}
						}

						if($soption->voption=='product_sku'){
							$showSku = false;
						}
					}

					if(!empty($soption->values)){
						$values = explode("\n",$soption->values);
						foreach($values as $value){
							$found = false;
							$value = trim($value);
							foreach($added as $add){
								if($add == $value){
									$found = true;
								}
							}
							if(!$found){
								$added[] = $value;
							}
						}
					}

					$soption->values = implode("\n",$added);
					$options[] = array('value' => '' ,'text' =>vmText::_('COM_VIRTUEMART_LIST_EMPTY_OPTION'));
					foreach($added as $value){
						$options[] = array('value' => $value ,'text' =>$value);
					}

					$soption->comboptions = $options;
					if(!isset($soption->clabel)) $soption->clabel = '';
					$soption->slabel = empty($soption->clabel)? vmText::_('COM_VIRTUEMART_'.strtoupper($soption->voption)): vmText::_($soption->clabel);

					if($k==0) {
						$html .='<div class="ramification-row unremovable">';
					} else {
						$html .='<div class="ramification-row removable">';
					}

					$idTag = 'selectoptions'.$k;
					$html .= JHtml::_ ('select.genericlist', $optAttr, 'field[' . $row . '][selectoptions]['.$k.'][voption]', '', 'value', 'text', $soption->voption,$idTag) ;

					$html .= '<input type="text" value="' . $soption->clabel . '" name="field[' . $row . '][selectoptions]['.$k.'][clabel]" style="line-height:2em;margin:5px 5px 0;" />';
					$html .= '<textarea name="field[' . $row . '][selectoptions]['.$k.'][values]" rows="5" cols="35" style="float:none;margin:5px 5px 0;" >'.$soption->values.'</textarea>';

					if($k>0){
						$html .='<span class="vmicon vmicon-16-remove 4remove"></span>';
					} else {

					}
					$html .='</div>';
				}
				$idTag = 'selectoptions'.++$k;
				$html .= '<fieldset style="background-color:#F9F9F9;">
					<legend>'. vmText::_('COM_VIRTUEMART_CUSTOM_RAMB_NEW').'</legend>
					<div id="new_ramification">';
				//$html .= JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][selectoptions]['.$k.'][voption]', '', 'value', 'text', 'product_name',$idTag) ;
				//$html .= '<input type="text" value="" name="field[' . $row . '][selectoptions]['.$k.'][slabel]" />';

				$html .= JHtml::_ ('select.genericlist', $optAttr, 'voption', '', 'value', 'text', 'product_name','voption') ;
				$html .= '<input type="text" value="" id="vlabel" name="vlabel" />';

				$html .= '<span id="new_ramification_bt"><span class="icon-nofloat vmicon vmicon-16-new"></span>'. vmText::_('COM_VIRTUEMART_ADD').'</span>
					</div>
				</fieldset>';

				vmJsApi::addJScript('new_ramification',"
	jQuery(document).ready(function($) {
		$('#new_ramification_bt').click(function() {
			var voption = $('#voption').val();
			var label = $('#vlabel').val();
			form = document.getElementById('adminForm');
			var newdiv = document.createElement('div');
			newdiv.innerHTML = '<input type=\"text\" value=\"'+voption+'\" name=\"field[" . $row . "][selectoptions][".$k."][voption]\" /><input type=\"text\" value=\"'+label+'\" name=\"field[" . $row . "][selectoptions][".$k."][clabel]\" />';
			form.appendChild(newdiv);

			form.task.value = 'apply';
			form.submit();
			return false;
		});
	});
	");
				$html .= '<div class="matrix-desc-container">';
				$html .= '<div class="create-matrix-input"><p class="">Create all child product possibilities (Beta).<br>Tick after adding all ramificatons and Save.</br></p><p>'
					. VmHtml::checkbox( 'field[' . $row . '][set_matrix]',  'Create Matrix')
					. '</p><p>Or add single child products with the button below.</p></div>';
				$html .= '<div class="multivariant-desc">' . vmText::_('COM_VIRTUEMART_CUSTOM_CV_DESC') . '</div></div>';
				$html .= '<style>
.ramification-row {
    padding: 3px 5px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 0;
}
.matrix-desc-container {
    padding: 3px 5px;
    margin-bottom: 10px;
    border: 1px solid #f00;
}
.create-matrix-input {
    display: inline-block;
    max-width: 20%;
    padding: 0 50px 0 0;
    vertical-align: top;
}
.multivariant-desc {
    display: inline-block;
    max-width: 65%;
}
.virtuemart-admin-area fieldset {
    border-radius: 0;
}
</style>';
				if ($product_id) {
					$link=JRoute::_('index.php?option=com_virtuemart&view=product&task=createChild&virtuemart_product_id='.$product_id.'&'.JSession::getFormToken().'=1&target=parent' );
					$add_child_button="";
				} else {
					$link="";
					$add_child_button=" not-active";
				}

				$html .= '<div class="button2-left '.$add_child_button.' btn-wrapper">
						<div class="blank">';
				if ($link) {
					$html .= '<a href="'. $link .'" class="btn btn-small">';
				} else {
					$html .= '<span class="hasTooltip" title="'.vmText::_ ('COM_VIRTUEMART_PRODUCT_ADD_CHILD_TIP').'">';
				}
				$html .= vmText::_('COM_VIRTUEMART_PRODUCT_ADD_CHILD');
				if ($link) {
					$html .= '</a>';
				} else{
					$html .= '</span>';
				}
				$html .= '</div>
					</div><div class="clear"></div>';
				//vmdebug('my $field->selectoptions',$field->selectoptions,$field->options);
				$html .= '<table id="mvo">';
				$html .= '<thead>';
				$html .= '<tr>
<th style="text-align: left !important;width:130px;">#</th>';
				if($showSku){
					$html .= '<th style="text-align: left !important;width:90px;">'.vmText::_('COM_VIRTUEMART_PRODUCT_SKU').'</th>';
				}
				$html .= '<th style="text-align: left !important;width:80px;">'. vmText::_('COM_VIRTUEMART_PRODUCT_GTIN').'</th>
<th style="text-align: left !important;" width="5%">'.vmText::_('COM_VIRTUEMART_PRODUCT_FORM_PRICE_COST').'</th>
<th style="text-align: left !important;width:30px;">'.vmText::_('COM_VIRTUEMART_PRODUCT_FORM_IN_STOCK').'</th>
<th style="text-align: left !important;width:30px;">'.vmText::_('COM_VIRTUEMART_PRODUCT_FORM_ORDERED_STOCK').'</th>';
				foreach($field->selectoptions as $k=>$option){
					$html .= '<th>'.vmText::_('COM_VIRTUEMART_'.strtoupper($option->voption)).'</th>';
				}
				$html .= '</tr>';
				$html .= '</thead>';
				$html .= '<tbody id="syncro">';

				$i=0;
				if($sorted and is_array($sorted) ){
					//$first = 0;
					foreach($sorted as $i=>$line){

						/*if($first == 1){    //We remove with this the "Select option" option, for non parent dropdowns
							foreach($field->selectoptions as $comboOpt){
								array_shift($comboOpt->comboptions);
							}
						}*/
						$html .= self::renderProductChildLine($i,$line,$field,$productModel,$row,$showSku);
						//$first++;
					}
				}

				$html .= '</tbody>';
				$html .= '</table>';

				$jsCsort = "

	jQuery(document).ready(function($){
		$('#syncro').sortable({cursorAt: { top: 0, left: 0 },handle: '.vmicon-16-move'});
});
";
				vmJsApi::addJScript('cvSort',$jsCsort);

				//vmdebug('Get child ids for ',$product_id,$childIds,$field);
				return $html;
				// 					return 'Automatic Childvariant creation (later you can choose here attributes to show, now product name) </td><td>';
				break;
			case 'A':
				//vmdebug('displayProductCustomfieldBE $field',$field);
				if(!isset($field->withParent)) $field->withParent = 0;
				if(!isset($field->parentOrderable)) $field->parentOrderable = 0;
				//vmdebug('displayProductCustomfieldBE',$field);
				$html = '</td><td>' . vmText::_('COM_VIRTUEMART_CUSTOM_WP').VmHTML::checkbox('field[' . $row . '][withParent]',$field->withParent,1,0,'').'<br />';
				$html .= vmText::_('COM_VIRTUEMART_CUSTOM_PO').VmHTML::checkbox('field[' . $row . '][parentOrderable]',$field->parentOrderable,1,0,'');

				$options = array();
				$options[] = array('value' => 'product_name' ,'text' =>vmText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME'));
				$options[] = array('value' => 'product_sku', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_SKU'));
				$options[] = array('value' => 'slug', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_ALIAS'));
				$options[] = array('value' => 'product_s_desc', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_S_DESC'));
				$options[] = array('value' => 'product_length', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
				$options[] = array('value' => 'product_width', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
				$options[] = array('value' => 'product_height', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
				$options[] = array('value' => 'product_weight', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));

				$html .= JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value) ;
				return $html;
				// 					return 'Automatic Childvariant creation (later you can choose here attributes to show, now product name) </td><td>';
				break;
			/* string or integer */
			case 'B':
				return $priceInput . '</td><td>' . JHTML::_ ('select.booleanlist', 'field[' . $row . '][customfield_value]', 'class="inputbox"', $field->customfield_value)  ;
				break;
			case 'S':

				if($field->is_list){
					$options = array();
					$values = explode (';', $field->custom_value);

					foreach ($values as $key => $val) {
						$options[] = array('value' => $val, 'text' => $val);
					}

					$currentValue = $field->customfield_value;
					$translate = (vmLanguage::$langCount>1)? true:false;
					return $priceInput . '</td><td>'.JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', NULL, 'value', 'text', $currentValue, false, $translate).$serials ;
				} else{
					if(vmText::_($field->customfield_value)!=$field->customfield_value) {
						$serials = '<span  style="max-width: 100px">'. vmText::_($field->customfield_value).'</span>'.$serials;
					}
					return $priceInput . '</td><td><input type="text" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />'.$serials;
					break;
				}

				break;
			// Property
			case 'P':
				$options = array();
				$options[] = array('value' => 'product_name' ,'text' =>vmText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME'));
				$options[] = array('value' => 'product_sku', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_SKU'));
				$options[] = array('value' => 'slug', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_ALIAS'));
				$options[] = array('value' => 'product_length', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
				$options[] = array('value' => 'product_width', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
				$options[] = array('value' => 'product_height', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
				$options[] = array('value' => 'product_weight', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));
				$options[] = array('value' => 'product_unit', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_UNIT'));

				$html = '</td><td>'.JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value) ;
				if($field->round){
					$html .= '<input type="text" value="' . $field->digits . '" name="field[' . $row . '][round]" />';
				}

				return $html.$serials;
			/* parent hint, this is a GROUP and should be G not P*/
			case 'G':
				return $field->customfield_value . '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" /></td><td>';
				break;
			/* image */
			case 'M':

				if($field->is_list and $field->is_input){

					$html = $priceInput . '</td><td>is list ';

					$values = explode (';', $field->custom_value);
					foreach($values as $val){
						$html .= $this->displayCustomMedia ($val,'product');
					}
					return $html.$serials;
				} else {
					if(empty($field->custom_value)){
						$q = 'SELECT `virtuemart_media_id` as value,`file_title` as text FROM `#__virtuemart_medias` WHERE `published`=1
					AND (`virtuemart_vendor_id`= "' . $virtuemart_vendor_id . '" OR `shared` = "1" ) ORDER BY `file_title` ';
						$db = JFactory::getDBO();
						$db->setQuery ($q);
						$options = $db->loadObjectList ();
					} else {
						$values = explode (';', $field->custom_value);
						$mM = VmModel::getModel('media');

						foreach ($values as $key => $val) {
							if(empty($val)) continue;
							$file = $mM->getFile($val);
							if(empty($file->file_type)){
								vmAdminInfo('The media customfield "'.$field->custom_title.'" with custom_id = '.$field->virtuemart_custom_id.' tries to load a non existing media with id = '.$val);
								continue;
							}
							$tmp = array('value' => $val, 'text' => $file->file_name);
							$options[] = (object)$tmp;
						}
					}

					return $priceInput . '</td><td>' . JHtml::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value).$serials;
				}

				break;

			case 'D':
				return $priceInput . '</td><td>' . vmJsApi::jDate ($field->customfield_value, 'field[' . $row . '][customfield_value]', 'field_' . $row . '_customvalue') ;
				break;

			//'X'=>'COM_VIRTUEMART_CUSTOM_EDITOR',
			case 'X':
				$user = Factory::getUser();
				$editor = Editor::getInstance($user->getParam('editor', Factory::getConfig()->get('editor')));
				//$editor = JFactory::getEditor();
				return  $priceInput . '</td><td>'.$editor->display('field['.$row.'][customfield_params]',$field->customfield_params, '550', '400', '60', '20', false);
				break;
			//'Y'=>'COM_VIRTUEMART_CUSTOM_TEXTAREA'
			case 'Y':
				return $priceInput . '</td><td><textarea id="field[' . $row . '][customfield_value]" name="field[' . $row . '][customfield_value]" class="inputbox" cols=80 rows=6 >' . $field->customfield_value . '</textarea>';
				//return '<input type="text" value="'.$field->customfield_value.'" name="field['.$row.'][customfield_value]" /></td><td>'.$priceInput;
				break;
			/*Extended by plugin*/
			case 'E':

				$html = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />';

				//vmdebug('displayProductCustomfieldBE $field',$field);
				vDispatcher::importVMPlugins ('vmcustom', $field->custom_element);
				$retValue = '';
				vDispatcher::trigger('plgVmOnProductEdit', array($field, $product_id, &$row, &$retValue));

				return $html . $priceInput   . '</td><td>'. $retValue;
				break;

			/* related category*/
			case 'Z':
				if (empty($field->customfield_value)) {
					return '';
				} // special case it's category ID !
				$categoryModel = VmModel::getModel('category');
				$category = $categoryModel->getCategory($field->customfield_value);

				$db = JFactory::getDBO();
				if ($category) {
					$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_category_medias` WHERE `virtuemart_category_id`= "' . (int)$field->customfield_value . '" ';
					$db->setQuery ($q);
					$thumb = '';
					if ($media_id = $db->loadResult ()) {
						$thumb = $this->displayCustomMedia ($media_id,'category');
					}

					$display = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />';
					$display .= '<span class="custom_related_image">'.$thumb.'</span><span class="custom_related_title">';
					$display .= JHtml::link ('index.php?option=com_virtuemart&view=category&task=edit&cid=' . (int)$field->customfield_value, $category->category_name, array('title' => $category->category_name,'target'=>'blank')).'</span>';
					return $display;
				}
				else {
					return 'no result for related category $product_id = '.$product_id.' and category id '.$field->customfield_value;
				}
				break;
			case 'PB':
				$html = $priceInput . '</td><td>';
				$pricingAr = $field->_varsToPushParam['multiplyPrice'][3]['options'];

				foreach ($pricingAr as $key => $val) {
					$options[] = array('value' => $key, 'text' => vmText::_($val));
				}

				$html .= JHtml::_('select.genericlist',  $options, 'field[' . $row . '][multiplyPrice]', 'class="inputbox" style="width:150px"   ', 'value', 'text', $field->multiplyPrice);

				JLoader::register('JFormFieldProduct', JPATH_ROOT.'/administrator/components/com_virtuemart/fields/product.php');

				$field->bundle_category_id = explode(',',$field->bundle_category_id);
				//vmdebug('my category and product id in bundle',$field->bundle_category_id,$field->bundle_product_id);
				$html .= JHtml::_('select.genericlist',  JFormFieldProduct::_getProducts($field->bundle_category_id), 'field[' . $row . '][bundle_product_id]', 'class="inputbox" style="width:250px"', 'value', 'text', $field->bundle_product_id);

				return $html;
				break;
			/* related product*/
			case 'R':
				if (!$product_id) {
					return '';
				}

				$pModel = VmModel::getModel('product');
				$related = $pModel->getProduct((int)$field->customfield_value,TRUE,FALSE,FALSE,1);
				if (!empty($related->virtuemart_media_id[0])) {
					$thumb = $this->displayCustomMedia ($related->virtuemart_media_id[0]).' ';
				} else {
					$thumb = $this->displayCustomMedia (0).' ';
				}
				$display = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value][]" />';
				$display .= '<span class="custom_related_image">'.$thumb.'</span>';
				if($related){
					$display .= '<span class="custom_related_title">'.JHtml::link ('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id=' . $related->virtuemart_product_id , $related->product_name, array('title' => $related->product_name,'target'=>'blank')).'</span>';
				}



				return $display;
			case 'RC':

				$prodIds = explode(',', $field->customfield_value);
				$prodStr = '';
				$relateds = array();
				$pModel = VmModel::getModel('product');
				foreach($prodIds as $pid){
					if(empty($pid)) continue;

					$related = $pModel->getProduct((int)$pid,TRUE,FALSE,FALSE,1);
					if(!$related) continue;


					/*if(VmConfig::get('backendTemplate', true)){
						//$pModel->attachMedia($related);
						//$relateds[] = $related;

					} */
					//else {
						if (!empty($related->virtuemart_media_id[0])) {
							$thumb = $this->displayCustomMedia ($related->virtuemart_media_id[0]).' ';
						} else {
							$thumb = $this->displayCustomMedia (0).' ';
						}

						if($pid==$related->product_parent_id){
							$title = vmText::_('COM_VIRTUEMART_CUSTOM_INHERITED').'</br>';
						}

						$prodStr .= '<div class="vm_thumb_image">
									
									<span class="vmicon vmicon-16-move"></span>
									<div class="vmicon vmicon-16-remove 4remove"></div>';
						$prodStr .= '<span><input type="hidden" value="' . $related->virtuemart_product_id . '" name="field[' . $row . '][customfield_value][]" />';
						$prodStr .= '<span class="custom_related_image">'.$thumb.'</span>';
						if($related){
							$prodStr .= '<span class="custom_related_title">'.JHtml::link ('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id=' . $related->virtuemart_product_id , $related->product_name, array('title' => $related->product_name,'target'=>'blank')).'</span>';
						}
						$prodStr .= '</span></div>';
					//}


				}

/*
					JLoader::register('VirtuemartViewProduct', VMPATH_ADMIN.'/views/product/view.html.php');
					$view = new VirtuemartViewProduct();
					$view->addTemplatePath(VMPATH_ROOT .'/administrator/templates/vmadmin/html/com_virtuemart/product');
					$pModel->addImages($relateds,1);
					$view->relatedType = "products";
					$view->relatedDatas = $relateds;
					$view->relatedIcon = 'product';
					//vmEcho::$echoDebug=true;
					//vmdebug('my field',$field);
					$view->virtuemart_custom_id = $field->virtuemart_custom_id;
					$view->setLayout('product_edit');
					$display = $view->loadTemplate('custom_relatedcf');
// the template is the same for categories and products
					$js = "
	var template = jQuery('#vmuikit-js-relatedcf-template').html()
	var rendered = Mustache.render(template,
			{
				'relatedDatas': " . json_encode($relateds) . " ,
			}
	)
	jQuery('#vmuikit-js-related".$view->relatedType."-container-".$field->virtuemart_custom_id."').html(rendered)
";

					vmJsApi::addJScript('related'.$view->relatedType.'.'.".$field->virtuemart_custom_id.".'.mustache', $js);
					$view->relatedType = "";
					$view->relatedDatas = array();

				} else {//*/
					$display = '<legend>'. vmText::_('COM_VIRTUEMART_RELATED_PRODUCTS').'</legend>
'.  vmText::_('COM_VIRTUEMART_PRODUCT_RELATED_SEARCH') .'
<div class="jsonSuggestResults" style="width: auto;">
	<input type="text" size="40" name="searchRelatedCustom" class="vmjs-relatedproductsSearch" value="" data-row="'.$row.'" />
	<button class="reset-value btn">'. vmText::_('COM_VIRTUEMART_RESET') .'</button>
	<label class="checkbox"><input type="checkbox" name="showchilds" value="0" />'. vmText::_('COM_VIRTUEMART_CATEGORIES_RELATED_SEARCH_CHILDS') .'</label>
</div>
<div class="custom_products" class="ui-sortable">'.   $prodStr .'</div>';

				//}
				return $display;
		}
	}


	static $customfieldRenderer = true;
	/**
	 * @author Max Milbers
	 * @param $product
	 * @param $customfield
	 */
	public static function displayProductCustomfieldFE (&$product, &$customfields) {

		$session = JFactory::getSession ();
		$virtuemart_category_id = $session->get ('vmlastvisitedcategoryid', 0, 'vm');



		if(self::$customfieldRenderer){

			$lPath = VmView::getVmSubLayoutPath ('customfield');
			if($lPath){
				require ($lPath);
			} else {
				vmdebug('displayProductCustomfieldFE layout not found customfield');
			}

			if(class_exists('VirtueMartCustomFieldRenderer')) {
				self::$customfieldRenderer = false;
			} else {
				vmError('VirtueMartCustomFieldRenderer not found');
			}

		}

		VirtueMartCustomFieldRenderer::renderCustomfieldsFE($product, $customfields, $virtuemart_category_id);


	}
	/**
	 * There are too many functions doing almost the same for my taste
	 * the results are sometimes slighty different and makes it hard to work with it, therefore here the function for future proxy use
	 *
	 */
	static public function displayProductCustomfieldSelected ($product, $html, $trigger) {

		if(self::$customfieldRenderer){
			self::$customfieldRenderer = false;

			$lPath = VmView::getVmSubLayoutPath ('customfield');

			if($lPath){
				require ($lPath);
			} else {
				vmdebug('displayProductCustomfieldFE layout not found customfield');
			}
		}

		return VirtueMartCustomFieldRenderer::renderCustomfieldsCart($product, $html, $trigger);
	}


	/**
	 * TODO This is html and view stuff and MUST NOT be in the model, notice by Max
	 * render custom fields display cart module FE
	 */
	static public function CustomsFieldCartModDisplay ($product) {
		return self::displayProductCustomfieldSelected ($product, '<div class="vm-customfield-mod">', 'plgVmOnViewCartModule');
	}

	/**
	 * render custom fields display cart FE
	 */
	static public function CustomsFieldCartDisplay ($product) {
		return self::displayProductCustomfieldSelected ($product, '<div class="vm-customfield-cart">', 'plgVmOnViewCart');
	}

	/**
	 * render custom fields display order BE/FE
	 */
	static public function CustomsFieldOrderDisplay ($item, $view = 'FE', $absUrl = FALSE) {
		if(empty($item->virtuemart_product_id)) return false;
		if (!empty($item->product_attribute)) {
			$item->customProductData = vmJsApi::safe_json_decode ($item->product_attribute, TRUE);
		}
		return self::displayProductCustomfieldSelected ($item, '<div class="vm-customfield-cart">', 'plgVmDisplayInOrder' . $view);
	}

	static function displayCustomMedia ($media_id, $table = 'product', $width = false, $height = false, $absUrl = false, $attribs = '') {

		$data = VmTable::getInstance('Medias', 'Table', array('dbo'=>JFactory::getDbo()));
		if(!empty($media_id)) {
			$data->load ((int)$media_id);
		}

		if(!empty($data->file_type)){
			$table = $data->file_type;
		}

		//in case there is no media, set the extension to an image, to show "no media set"
		if(empty($data->file_url)){
			$data->file_url = '.jpg';
		}
		$media = VmMediaHandler::createMedia ($data, $table);

		return $media->displayMediaThumb ($attribs, FALSE, '', TRUE, TRUE, $absUrl, $width, $height);
	}

	/**
	 * @deprecated 3.6
	 * @param $customPrice
	 * @param $currency
	 * @param $calculator
	 * @return string
	 */
	static function _getCustomPrice($customPrice, $currency, $calculator) {
		if ((float)$customPrice) {
			$price = strip_tags ($currency->priceDisplay ($calculator->calculateCustomPriceWithTax ($customPrice)));
			if ($customPrice >0) {
				$price ="+".$price;
			}
		}
		else {
			$price = ($customPrice === '') ? '' :  vmText::sprintf('COM_VIRTUEMART_CART_PRICE_FREE',$currency->getSymbol());
		}
		return $price;
	}

	static function renderCustomfieldPrice($productCustom,$product,$calculator){


		if(!empty($productCustom->multiplyPrice) and $productCustom->multiplyPrice == 'free'){
			$customPrice = '';
		} else {
			$customPrice = self::getCustomFieldPriceModificator($productCustom,$product);
		}

		if ( (float)$customPrice) {

			if ($customPrice > 0) {
				$sign = vmText::_('COM_VM_PLUS');
			} else {
				$sign = vmText::_('COM_VM_MINUS');
			}
			$calculator->setProduct($product);
			$priceV = $calculator->calculateCustomPriceWithTax ($customPrice);
			$priceV = strip_tags ($calculator->_currencyDisplay->priceDisplay ( $priceV ));
			if ($customPrice < 0) {
				$priceV = trim($priceV,'-');
			}
			if(empty($productCustom->multiplyPrice)){

				$price = vmText::sprintf('COM_VM_CUSTOMFIELD_VARIANT_PRICE',$sign,$priceV);
			} else {

				$v = trim($productCustom->customfield_price,0);
				$v = trim($v,'.');
				$price = vmText::sprintf('COM_VM_CUSTOMFIELD_VARIANT_PERCENTAGE',$sign,$v,$priceV);

			}

		}
		else {
			$price = ($customPrice === '') ? '' :  vmText::sprintf('COM_VIRTUEMART_CART_PRICE_FREE',$calculator->_currencyDisplay->getSymbol());
		}
		return $price;
	}

	static function getCustomFieldPriceModificator($productCustom,$product){

		if(empty($productCustom->multiplyPrice)){
			$p = 0.0;
			if($productCustom->field_type == 'PB'){
				$p = floatval(VmModel::getModel('product')->getProduct($productCustom->bundle_product_id)->prices['product_price']);
			}
			$p += floatval($productCustom->customfield_price);
		} else {

			$product->modificatorSum = floatval($product->modificatorSum);

			if($productCustom->multiplyPrice == 'base_productprice' or $productCustom->multiplyPrice == 'base_variantprice') {
				if ($productCustom->field_type == 'PB') {
					$pVirt = VmModel::getModel('product')->getProduct($productCustom->bundle_product_id)->prices['product_price'];
				} else {
					$pVirt = $product->allPrices[$product->selectedPrice]['product_price'];
				}

				//vmdebug('my virtual costs', $pVirt);
				if ($productCustom->multiplyPrice == 'base_variantprice') {
					$pVirt += $product->modificatorSum;
				}

				$pVirt = floatval($pVirt);
				$productCustom->customfield_price = floatval($productCustom->customfield_price);

				if ($productCustom->field_type == 'PB') {
					$p = $pVirt + $pVirt * $productCustom->customfield_price * 0.01;
				} else {
					$p = $pVirt * $productCustom->customfield_price * 0.01;
				}

			} else if($productCustom->multiplyPrice == 'free'){
				$p = 0.0;
			} else {	//base_modificatorprice
				$p = $product->modificatorSum * $productCustom->customfield_price * 0.01;
			}
		}
		return $p;
	}

	/**
	 * @param $product
	 * @param $variants ids of the selected variants
	 * @return float
	 */
	public function calculateModificators(&$product, $cart = null) {

		if (!isset($product->modificatorSum)){

			$product->modificatorSum = 0.0;
			if(!empty($product->customfields)) {
				foreach( $product->customfields as $k => $productCustom ) {
					$selected = -1;

					if(isset($product->cart_item_id)) {

						if($cart === null) $cart = VirtueMartCart::getCart();

						if(isset($cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])) {
							$selected = $cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id];

						} else if(isset($cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id])) {
							if($cart->cartProductsData[$product->cart_item_id]['customProductData'][$productCustom->virtuemart_custom_id] == $productCustom->virtuemart_customfield_id) {
								$selected = $productCustom->virtuemart_customfield_id;    //= 1;

							}
						} else if(isset ($product->customProductData[$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])){
							$selected = $product->customProductData[$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id];
						}

					} else {

						$pluginFields = vRequest::getVar( 'customProductData', false );

						if($pluginFields == false and isset($product->customPlugin)) {
							$pluginFields = vmJsApi::safe_json_decode( $product->customPlugin, TRUE );
						}

						if(isset($pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])) {
							$selected = $pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id];
						} else if(isset($pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id])) {
							if($pluginFields[$product->virtuemart_product_id][$productCustom->virtuemart_custom_id] == $productCustom->virtuemart_customfield_id) {
								$selected = 1;
							}
						}
					}

					if($selected === -1) {
						continue;
					}

					if(!empty($productCustom) and $productCustom->field_type == 'E') {
						vDispatcher::importVMPlugins( 'vmcustom' );
						vDispatcher::trigger( 'plgVmPrepareCartProduct', array(&$product, &$product->customfields[$k], $selected, &$product->modificatorSum) );
					} else {
						if($productCustom->customfield_price) {

							$product->modificatorSum += self::getCustomFieldPriceModificator($productCustom,$product);
						}
					}
				}
			}
		}

		return $product->modificatorSum;
	}


	/** Save and delete from database
	* all product custom_fields and xref
	@ var   $table	: the xref table(eg. product,category ...)
	@array $data	: array of customfields
	@int     $id		: The concerned id (eg. product_id)
	*/
	public function storeProductCustomfields($table, $datas, $id) {

		vRequest::vmCheckToken('Invalid token in storeProductCustomfields');
		//Sanitize id
		$id = (int)$id;

		//Table whitelist
		$tableWhiteList = array('product','category','manufacturer');
		if(!in_array($table,$tableWhiteList)) return false;

		// Get old IDS
		$db = JFactory::getDBO();
		$db->setQuery( 'SELECT * FROM `#__virtuemart_'.$table.'_customfields` as `PC` WHERE `PC`.virtuemart_'.$table.'_id ='.$id );

		$oldCustomfields = $db->loadAssocList('virtuemart_customfield_id');
		$old_customfield_ids = array_keys($oldCustomfields);

		if (!empty( $datas['field'])) {

			foreach($datas['field'] as $key => $fields){

				if(!empty($datas['field'][$key]['virtuemart_product_id']) and (int)$datas['field'][$key]['virtuemart_product_id']!=$id){
					//vmdebug('The field is from the parent',$fields);
					$fields['override'] = !empty($fields['override'])?(int)$fields['override']:0;
					$fields['disabler'] = !empty($fields['disabler'])?(int)$fields['disabler']:0;

					if($fields['override']!=0 or $fields['disabler']!=0){
						if($fields['override']!=0){
							$fields['override'] = $fields['virtuemart_customfield_id'];
						}
						if($fields['disabler']!=0){
							$fields['disabler'] = $fields['virtuemart_customfield_id'];
						}

						if(!empty($fields['virtuemart_customfield_id']) and empty($oldCustomfields[$fields['virtuemart_customfield_id']]['virtuemart_customfield_id'])){
							//vmdebug('It is set now as override, store it as clone, therefore set the virtuemart_customfield_id = 0');
							$fields['virtuemart_customfield_id'] = 0;
						}
					}
					else {
						//vmdebug('there is no override/disabler set',$fields,$oldCustomfields[$fields['virtuemart_customfield_id']]);
						//we do not store customfields inherited by the parent, therefore
						$key = array_search($fields['virtuemart_customfield_id'], $old_customfield_ids );
						if ($key !== false ){
							unset( $old_customfield_ids[ $key ] );
						}
						continue;
					}
				}
				else {
					//vmdebug('The field is from the current product',$fields);
					if(empty($fields['override']) and empty($fields['disabler']) and !empty($fields['virtuemart_customfield_id']) and (!empty($oldCustomfields[$fields['virtuemart_customfield_id']]['disabler']) or !empty($oldCustomfields[$fields['virtuemart_customfield_id']]['override']) )){
						//vmdebug('Remove customfield override/disabler',$fields['virtuemart_customfield_id']);
						$old_customfield_ids[] = $fields['virtuemart_customfield_id'];
					}
				}

				if(!empty($fields['field_type']) and $fields['field_type']=='C' and !isset($datas['clone']) ){

					$cM = VmModel::getModel('custom');
					$c = $cM->getCustom($fields['virtuemart_custom_id'],'');

					if(!empty($fields['set_matrix'])){

						$productModel = VmModel::getModel ('product');
						$avail = $productModel->getProductChildIds($id);

						foreach($fields['selectoptions'] as $kv => $selectoptions){
							if(!empty($selectoptions['values'])){
								$values[$kv] = preg_split('/\r\n|\r|\n/', $selectoptions['values'],5);
							}
						}

						vmdebug('my values',$values,$avail);

						$parentCombo = $fields['options'][$id];
						$myMatrix = array();
						$size = 1;
						//$parentMatrix = array();
						//Yes, also this may get better written, but I am just happy that it works this way.
						foreach ($values as $variantKey => $optArray) {
							$size = $size * sizeof($optArray);
							//$level++;
							foreach ($optArray as $option) {
								//vmdebug('myMatrix $k=>$option',$option);
								if($variantKey==0){
									$myMatrix[$option] = null;
									//$parentMatrix[$parentCombo[0]] = null;
								} else if($variantKey == 1){

									$myMatrix = self::writeValuesToKeysAddValueArray($myMatrix,$option);

									//$parentMatrix[$parentCombo[0]][$parentCombo[1]]= null;
								} else if($variantKey == 2){
									foreach($myMatrix as $k1 => &$option1){
										$option1 = self::writeValuesToKeysAddValueArray($option1,$option);
									}
									//$parentMatrix[$parentCombo[0]][$parentCombo[1]][$parentCombo[2]]= null;
								} else if($variantKey == 3){
									foreach($myMatrix as $k1 => &$option1){
										foreach($option1 as $k2 => &$option2){
											$option2 = self::writeValuesToKeysAddValueArray($option2,$option);
										}
									}
									//$parentMatrix[$parentCombo[0]][$parentCombo[1]][$parentCombo[2]][$parentCombo[3]]= null;
								} else if($variantKey == 4){
									foreach($myMatrix as $k1 => &$option1){
										foreach($option1 as $k2 => &$option2){
											foreach($option2 as $k3 => &$option3){
												$option3 = self::writeValuesToKeysAddValueArray($option3,$option);
											}
										}
									}
									//$parentMatrix[$parentCombo[0]][$parentCombo[1]][$parentCombo[2]][$parentCombo[3]][$parentCombo[4]]= null;
								}
							}
						}
						vmdebug('myMatrix 3',$size,$myMatrix);

						vmdebug('myMatrix orig $fields',$fields['options']);
						$parentComboSerialized = serialize($parentCombo);
						//reset($avail);
						for((int)$i=0;$i<$size;$i++){
							if(empty($avail)){
								$childId = $productModel->createChild($id);
							} else {
								$childId = $avail[$i];
								unset($avail[$i]);
							}
							vmdebug('$childId after unset'.$i,$childId,$avail);
							$combo = self::writeCombos($myMatrix);

							if(serialize($combo) == $parentComboSerialized){
								$combo = self::writeCombos($myMatrix);
								//vmdebug('myMatrix $combo equals $parentCombo',$combo, $parentCombo);
								$size--;
							} else {
								//vmdebug('myMatrix $combo NOT equal',serialize($combo), $parentComboSerialized);
							}
							$fields['options'][$childId] = $combo;
						}
						vmdebug('myMatrix $fields',$fields['options'],$avail);

					}
					//The idea was here to store the images directly. Maybe just the ids.
					/*if(!empty($c->withImage)){
						$mediaM = VmModel::getModel('media');
						$tablePM = $mediaM->getTable('product_medias');
						foreach($fields['options'] as $prodId => $lvalue){
							$images = $tablePM->load($prodId);
							if(isset($images[0])){
								$media = $mediaM->createMediaByIds($images[0]);
								$fields['images'][$prodId] = $media[0]->getFileUrlThumb();
							}

						}
					}*/

					//Set tags on extra customfield
					if(!empty($c->sCustomId)){

						$sCustId = $c->sCustomId;
						$labels = array();
						foreach($fields['selectoptions'] as $k => $option){
							if (is_object($option)) {
								$option = Joomla\Utilities\ArrayHelper::fromObject($option);
							}
							if($option['voption'] == 'clabels' and !empty($option['clabel'])){
								$labels[$k] = $option['clabel'];
							}
						}

						foreach($fields['options'] as $prodId => $lvalue){

							if($prodId == $id) continue;
							$db->setQuery( 'SELECT `virtuemart_customfield_id` FROM `#__virtuemart_'.$table.'_customfields` as `PC` WHERE `PC`.virtuemart_'.$table.'_id ="'.$prodId.'" AND `virtuemart_custom_id`="'.(int)$sCustId.'" '  );
							$strIds = $db->loadColumn();
							$i=0;
							foreach($lvalue as $k=>$value) {

								if(!empty($labels[$k])) {
									$ts = array();
									$ts['field_type'] = 'S';
									$ts['virtuemart_product_id'] = (int)$prodId;
									$ts['virtuemart_custom_id'] = (int)$sCustId;
									if(isset($strIds[$i])){
										$ts['virtuemart_customfield_id'] = (int)$strIds[$i];
										unset( $strIds[$i++] );
									}
									$ts['customfield_value'] = $value;

									$tableCustomfields = $this->getTable($table.'_customfields');
									$tableCustomfields->bindChecknStore($ts);
								}
							}

							if(count($strIds)>0){
								// delete old unused Customfields
								$db->setQuery( 'DELETE FROM `#__virtuemart_'.$table.'_customfields` WHERE `virtuemart_customfield_id` in ("'.implode('","', $strIds ).'") ');
								$db->execute();
							}
						}
					}
					//vmdebug('Executing',$id,$fields);
				}

				if (!empty($datas['customfield_params'][$key]) and !isset($datas['clone']) ) {
					if (array_key_exists( $key,$datas['customfield_params'])) {
						$fields = array_merge ((array)$fields, (array)$datas['customfield_params'][$key]);
					}
				}
				$fields['virtuemart_'.$table.'_id'] = $id;

				if(!empty($fields['field_type']) and ( $fields['field_type']=='RC' or $fields['field_type']=='R' ) and !isset($datas['clone']) ){
					if(is_array($fields['customfield_value'])){
						$fields['customfield_value'] = implode(',',$fields['customfield_value']);
					}
				}

				$this->storeProductCustomfield('product', $fields);

				$datas['field'][$key] = $fields;

				$keyOld = array_search($fields['virtuemart_customfield_id'], $old_customfield_ids );
				if ($keyOld !== false ) unset( $old_customfield_ids[ $keyOld ] );
			}
		} else {
			//vmdebug('storeProductCustomfields nothing to store');
		}

		vDispatcher::importVMPlugins('vmcustom');

		//vmdebug('Delete $old_customfield_ids',$old_customfield_ids);

		if ( count($old_customfield_ids) ) {
			// call the plugins to delete their records
			foreach ($old_customfield_ids as $old_customfield_id) {
				vDispatcher::trigger('plgVmOnCustomfieldRemove', array($oldCustomfields[$old_customfield_id]));
			}
			// delete old unused Customfields
			$db->setQuery( 'DELETE FROM `#__virtuemart_'.$table.'_customfields` WHERE `virtuemart_customfield_id` in ("'.implode('","', $old_customfield_ids ).'") ');
			$db->execute();
			//vmdebug('Deleted $old_customfield_ids',$old_customfield_ids);
		}



		if (isset($datas['customfield_params']) and is_array($datas['customfield_params'])) {
			foreach ($datas['field'] as $key => $pfield ) {
				if($pfield['field_type']=="E" and !empty($pfield['custom_element'])){
					vDispatcher::directTrigger( 'vmcustom',$pfield['custom_element'], 'plgVmOnStoreProduct', array($datas, $datas['customfield_params'][$key], $old_customfield_ids, $key ));
				}
			}
		}

	}

	static public function writeValuesToKeysAddValueArray($myMatrix,$option){
		$myMatrix1 = $myMatrix;
		foreach($myMatrix as $k=>$option1){
			$myMatrix1[$k][$option] = null;
		}
		return $myMatrix1;
	}

	/**
	 * This function creates the different combinations for the Multivariants.
	 * Yes, this function could be written more abstract and smarter, but so long any tries took almost the same size and were not working.
	 * @author Max Milbers
	 * @param $myMatrix
	 * @return array
	 */


	static public function writeCombos(&$myMatrix){

		$comboArray = array();
		foreach($myMatrix as $option => $options){
			$comboArray[] = $option;    //1

			if(is_array($options) and !empty($options)){

				foreach($options as $option1 => $options1){
					$comboArray[] = $option1;   //2

					if(is_array($options1) and !empty($options1)){

						foreach($options1 as $option2 => $options2){
							$comboArray[] = $option2;   //3

							if(is_array($options2) and !empty($options2)){

								foreach($options2 as $option3 => $options3){
									$comboArray[] = $option3;   //4

									if(is_array($options3) and !empty($options3)){
										foreach($options3 as $option4 => $options4) {
											$comboArray[] = $option4;   //5

											unset($myMatrix[$option][$option1][$option2][$option3][$option4]);

											if(empty($myMatrix[$option][$option1][$option2][$option3])){
												unset($myMatrix[$option][$option1][$option2][$option3]);
												if(empty($myMatrix[$option][$option1][$option2])){
													unset($myMatrix[$option][$option1][$option2]);
													if(empty($myMatrix[$option][$option1])){
														unset($myMatrix[$option][$option1]);
														if(empty($myMatrix[$option])){
															unset($myMatrix[$option]);
														}
													}
												}
											}


											vmdebug('level 5', $comboArray);
											return $comboArray;
										}


									} else {
										unset($myMatrix[$option][$option1][$option2][$option3]);
										if(empty($myMatrix[$option][$option1][$option2])){
											unset($myMatrix[$option][$option1][$option2]);
											if(empty($myMatrix[$option][$option1])){
												unset($myMatrix[$option][$option1]);
												if(empty($myMatrix[$option])){
													unset($myMatrix[$option]);
												}
											}
										}

										vmdebug('level 4', $comboArray);
										return $comboArray;
									}

								}

							} else {
								unset($myMatrix[$option][$option1][$option2]);
								if(empty($myMatrix[$option][$option1])){
									unset($myMatrix[$option][$option1]);
									if(empty($myMatrix[$option])){
										unset($myMatrix[$option]);
									}
								}
								vmdebug('level 3', $comboArray);
								return $comboArray;

							}

						}

					} else {

						unset($myMatrix[$option][$option1]);
						if(empty($myMatrix[$option])){
							unset($myMatrix[$option]);
						}
						//vmdebug('level 2', $option, $comboArray);
						return $comboArray;

					}
				}

			} else {
				unset($myMatrix[$option]);
				vmdebug('level 1', $option, $comboArray);
				return $comboArray;

			}

		}
	}

	public function storeProductCustomfield($table, &$fields){

		$tableCustomfields = $this->getTable($table.'_customfields');

		$tableCustomfields->_xParams = 'customfield_params';
		VirtueMartModelCustom::setParameterableByFieldType($tableCustomfields,$fields['field_type'],$fields['custom_element'],$fields['custom_jplugin_id']);

		//We do not store default values
		$paramsTemp = array();
		foreach($tableCustomfields->_varsToPushParam as $name=>$attrib){
			if(isset($fields[$name])){
				$paramsTemp[$name] = $attrib;
			} else {
				unset($tableCustomfields->{$name});
			}
		}
		$tableCustomfields->_varsToPushParam = $paramsTemp;

		$tableCustomfields->bindChecknStore($fields);

	}

	static public function setEditCustomHidden ($customfield, $i) {

		if (!isset($customfield->virtuemart_customfield_id))
			$customfield->virtuemart_customfield_id = '0';
		if (!isset($customfield->virtuemart_product_id))
			$customfield->virtuemart_product_id = '';
		$html = '<input type="hidden" value="' . $customfield->field_type . '" name="field[' . $i . '][field_type]" />
			<input type="hidden" value="' . $customfield->custom_element . '" name="field[' . $i . '][custom_element]" />
			<input type="hidden" value="' . $customfield->custom_jplugin_id . '" name="field[' . $i . '][custom_jplugin_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_custom_id . '" name="field[' . $i . '][virtuemart_custom_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_product_id . '" name="field[' . $i . '][virtuemart_product_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_customfield_id . '" name="field[' . $i . '][virtuemart_customfield_id]" />';
			$html .= '<input class="ordering" type="hidden" value="'.$customfield->ordering.'" name="field['.$i .'][ordering]" />';
		return $html;

	}

	private $_hidden = array();

	public function addHidden ($name, $value = '') {
		$this->_hidden[$name] = $value;
	}

}
// pure php no closing tag
