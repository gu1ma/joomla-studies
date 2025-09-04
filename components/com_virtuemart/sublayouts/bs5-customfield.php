<?php

/**
 *
 * renders a customfield
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2015 - 2022 The VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @version $Id: customfield.php 8024 2014-06-12 15:08:59Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

class VirtueMartCustomFieldRenderer {

	static function renderCustomfieldsFE(&$product,&$customfields,$virtuemart_category_id){

		static $calculator = false;
		if(!$calculator){
			$calculator = calculationHelper::getInstance ();
		}

		$selectList = array();

		$dynChilds = 1;

		static $currency = false;
		if(!$currency){
			$currency = CurrencyDisplay::getInstance ();
		}

		foreach($customfields as $key => $customfield){


			if(!isset($customfield->display))$customfield->display = '';

			$calculator->_product = $product;

			if ($customfield->field_type == "E") {

				vDispatcher::importVMPlugins ('vmcustom');
				$ret = vDispatcher::trigger ('plgVmOnDisplayProductFEVM3', array(&$product, &$customfields[$key]));
				continue;
			}

			$fieldname = 'field['.$product->virtuemart_product_id.'][' . $customfield->virtuemart_customfield_id . '][customfield_value]';
			$customProductDataName = 'customProductData['.$product->virtuemart_product_id.']['.$customfield->virtuemart_custom_id.']';

			//This is a kind of fallback, setting default of custom if there is no value of the productcustom
			$customfield->customfield_value = (!isset($customfield->customfield_value) or $customfield->customfield_value==='') ? $customfield->custom_value : $customfield->customfield_value;

			$type = $customfield->field_type;

			$idTag = 'customProductData_'.(int)$product->virtuemart_product_id.'_'.$customfield->virtuemart_customfield_id;
			$idTag = VmHtml::ensureUniqueId($idTag);

			$emptyOption = new stdClass();
			$emptyOption->text = vmText::_ ('COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT');
			$emptyOption->value = 0;
			switch ($type) {

				case 'C':
					$html = '';

					$dropdowns = array();

					if(isset($customfield->options->{$product->virtuemart_product_id})){
						$productSelection = $customfield->options->{$product->virtuemart_product_id};
					} else {
						$productSelection = false;
					}

					$stockhandle = VmConfig::get('stockhandle_products', false) && $product->product_stockhandle ? $product->product_stockhandle : VmConfig::get('stockhandle','none');
					$extra = ' and ( published = "1" ';
					if($stockhandle == 'disableit_children'){
						$extra .= ' AND (`product_in_stock` - `product_ordered`) > "0" ';
					}
					$extra .= ')';

					$productModel = VmModel::getModel ('product');
					$avail = $productModel->getProductChildIds($customfield->virtuemart_product_id, $extra);
					if(!in_array($customfield->virtuemart_product_id,$avail)){
						array_unshift($avail,$customfield->virtuemart_product_id);
					}

					foreach($customfield->options as $product_id=>$variants){
						static $counter = 0;
						if(!in_array($product_id,$avail)){
							vmdebug('$customfield->options Product to ignore, continue ',$product_id);
							continue;
						}

						foreach($variants as $k => $variant){

							if(!isset($dropdowns[$k]) or !is_array($dropdowns[$k])) $dropdowns[$k] = array();
							if(!in_array($variant,$dropdowns[$k])  ){

								if($k==0 or !$productSelection){
									$dropdowns[$k][] = $variant;
								} else{
									if($productSelection[$k-1] == $variants[$k-1]) {
										$break = false;
										for( $h = 1; $h<=$k; $h++ ) {
											if($productSelection[$h - 1] != $variants[$h - 1]) {
												$break = true;
											}
										}
										if(!$break) {
											$dropdowns[$k][] = $variant;
										}
									}
								}
							}
						}
					}

					$class = 'vm-chzn-select form-select';
					$selectType = 'select.genericlist';

					if(!empty($customfield->selectType)){
						$selectType = 'select.radiolist';
						$class = '';
						$dom = '';
						$attribs = array('class'=>'cvselection cvradio no-vm-bind');
					} else {
						vmJsApi::chosenDropDowns();
						$dom = 'select';
						$attribs = array('class'=>$class.' cvselection cvdrop no-vm-bind');
					}

					$view = 'productdetails';
					$attribs['data-reload'] = '1';
					$view = $viewJs = vRequest::getCmd('view','productdetails');

					if(VmConfig::get ('jdynupdate', TRUE)){

						if($view == 'productdetails' or ($customfield->browseajax and $view == 'category')){
							$attribs['data-dynamic-update'] = '1';
							unset($attribs['data-reload']);
						} else {
							$viewJs = 'productdetails';
						}
					}

					$Itemid = vRequest::getInt('Itemid',''); // '&Itemid=127';
					if(!empty($Itemid)){
						$Itemid = '&Itemid='.$Itemid;
					}

					//create array for js
					$jsArray = array();

					$url = '';

					if($view == 'productdetails' and $customfield->withImage){
						$withImage = true;
						$mediaM = VmModel::getModel('media');
						$tablePM = $mediaM->getTable('product_medias');
						$html .= '<div class="child-Image-Container row align-items-end gy-3 mb-5">';
					} else {
						$withImage = false;
					}
					foreach($customfield->options as $prodId=>$variants){

						if(!in_array($prodId,$avail)){continue;}

						$imagLabel = '';
						foreach ($variants as $labelText) {
							if (empty($labelText)) {
								$imagLabel = '&nbsp;';
							} else {
								$imagLabel = empty($imagLabel) ? $imagLabel . $labelText  : $imagLabel . ' - ' . $labelText;
							}

						}

						$url = Route::_('index.php?option=com_virtuemart&view='.$viewJs.'&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id='.$prodId.$Itemid,false);
						$jsArray[] = '["'.$url.'","'.implode('","',$variants).'"]';
						if($withImage){

							if($prodId == $product->virtuemart_product_id) continue;

							$images = array();
							$images[] = $tablePM->load($prodId);

							if(!isset($images[0]) and isset($product->virtuemart_media_id[0])){
								$images[0] = $product->virtuemart_media_id[0];  //If the image is missing, the parent has it
							}
							if(isset($images[0])){
								$media = $mediaM->createMediaByIds($images[0]);
								$html .= '<div class="col-3 text-center">';
								$html .= '<a href="'.$url.'" >';
								$html .= $media[0]->displayMediaThumb(array ('class' => 'child-Images img-fluid img-thumbnail'), false, '', true, false, false, VmConfig::get('img_width',90), VmConfig::get('img_height',90));
								$html .= '<div>' . $imagLabel . '</div>';
								$html .= '</a>';
								$html .= '</div>';
							}
						}

					}

					if ($view == 'productdetails' and $customfield->withImage) {
						$html .= '</div>';
					}

					foreach($customfield->selectoptions as $k => $soption){

						$html .= '<div class="custom_field_C_container mb-2">';
						$options = array();
						$selected = false;
						$idTagK = VmHtml::ensureUniqueId($idTag);

						if(isset($dropdowns[$k])){
							foreach($dropdowns[$k] as $i=> $elem){

								$elem = trim((string)$elem);
								$text = $elem;

								if($soption->clabel!='' and in_array($soption->voption,VirtueMartModelCustomfields::$dimensions) ){
									$rd = $soption->clabel;
									if(is_numeric($rd) and is_numeric($elem)){
										$text = number_format(round((float)$elem,(int)$rd),$rd);
									}
									//vmdebug('($dropdowns[$k] in DIMENSION value = '.$elem.' r='.$rd.' '.$text);
								} else if  ($soption->voption === 'clabels' and $soption->clabel!='') {
									$text = vmText::_($elem);
								}

								if(empty($elem)){
									$text = vmText::_('COM_VIRTUEMART_LIST_EMPTY_OPTION');
								}
								$o = new stdClass();
								$o->value = $elem;
								$o->text = $text;
								$o->id = VmHtml::ensureUniqueId($idTagK.'-'.$i);
								$options[] = $o;

								if($productSelection and $productSelection[$k] == $elem){
									$selected = $elem;
								}
							}
						}


						if(empty($selected)){
							$product->orderable=false;
						}

						if($customfield->showlabels){
							if( in_array($soption->voption,VirtueMartModelCustomfields::$dimensions) ){
								$soption->slabel = vmText::_('COM_VIRTUEMART_'.strtoupper($soption->voption));
							} else if(!empty($soption->clabel) and !in_array($soption->voption,VirtueMartModelCustomfields::$dimensions) ){
								$soption->slabel = vmText::_($soption->clabel);
							}
							if(isset($soption->slabel)){
								$html .= '<label class="vm-cmv-label form-label mb-1" for="' . $idTagK . '">'.$soption->slabel.'</label>';
							}

						}

						$attribs['data-cvsel'] = 'field' . $customfield->virtuemart_customfield_id ;
						$fname = $fieldname.'['.$k.']';

						if(empty($customfield->selectType)){
							$html .= VmHtml::genericlist( $options, $fname, $attribs , "value", "text", $selected, $idTagK);
						} else {
							$html .= VmHtml::JRadiolist ( $options, $fname, $attribs , "value", "text", $selected, $idTagK);
						}

						$html .= '</div>';
					}

					vmJsApi::addJScript('cvfind');

					$BrowserNewState =  '';
					if($view != 'productdetails'){
						$BrowserNewState = 'Virtuemart.setBrowserState = false;';
					}

					$jsVariants = implode(',',$jsArray);

					$selector = $dom."[data-cvsel=\"".$attribs['data-cvsel']."\"]";
					$hash = md5($selector);
					$j = "jQuery(document).ready(function($) {
							".$BrowserNewState."
							$('".$selector."').off('change',Virtuemart.cvFind);
							$('".$selector."').on('change', { variants:[".$jsVariants."] },Virtuemart.cvFind);
						});";
					vmJsApi::addJScript('cvselvars'.$hash,$j,true,false,false,$hash);

					//Now we need just the JS to reload the correct product
					$customfield->display = $html;

					break;

				case 'A':

					$html = '';

					$productModel = VmModel::getModel ('product');

					//Note by Jeremy Magne (Daycounts) 2013-08-31
					//Previously the the product model is loaded but we need to ensure the correct product id is set because the getUncategorizedChildren does not get the product id as parameter.
					//In case the product model was previously loaded, by a related product for example, this would generate wrong uncategorized children list
					$productModel->setId($customfield->virtuemart_product_id);

					$uncatChildren = $productModel->getUncategorizedChildren ($customfield->withParent);

					$options = array();

					$selected = vRequest::getInt ('virtuemart_product_id',0);
					$selectedFound = false;

					$view = 'productdetails';
					$attribs['data-reload'] = '1';
					$attribs['data-cvselection'] = 'true';
					if(VmConfig::get ('jdynupdate', TRUE)){
						$attribs['url'] = 'value';
						$view = vRequest::getCmd('view','productdetails');
						if($view == 'productdetails' or ($customfield->browseajax and $view == 'category')){
							$attribs['data-dynamic-update'] = '1';
							unset($attribs['data-reload']);
						} else {
							$view = 'productdetails';
						}
					}

					$Itemid = vRequest::getInt('Itemid',''); // '&Itemid=127';
					if(!empty($Itemid)){
						$Itemid = '&Itemid='.$Itemid;
					}

					if(!$customfield->withParent){
						$options[0] = $emptyOption;
						$options[0]->value = Route::_ ('index.php?option=com_virtuemart&view='.$view.'&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $customfield->virtuemart_product_id,FALSE);
						//$options[0] = array('value' => Route::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $customfield->virtuemart_product_id,FALSE), 'text' => vmText::_ ('COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT'));
					}

					$parentStock = 0;
					if($uncatChildren){
						foreach ($uncatChildren as $k => $child) {
							/*if(!isset($child[$customfield->customfield_value])){
								vmdebug('The child has no value at index '.$customfield->customfield_value,$customfield,$child);
							} else {*/

							$productChild = $productModel->getProduct((int)$child,true);

							if(!$productChild) continue;
							if(!isset($productChild->{$customfield->customfield_value})){
								vmdebug('The child has no value at index '.$child);
								continue;
							}
							$available = $productChild->product_in_stock - $productChild->product_ordered;
							if(VmConfig::get('stockhandle','none')=='disableit_children' and $available <= 0){
								continue;
							}
							$parentStock += $available;
							$priceStr = '';
							if($customfield->wPrice){
								//$product = $productModel->getProductSingle((int)$child['virtuemart_product_id'],false);
								$productPrices = $calculator->getProductPrices ($productChild);
								$priceStr =  ' (' . $currency->priceDisplay ($productPrices['salesPrice']) . ')';
							}
							$options[] = array('value' => Route::_ ('index.php?option=com_virtuemart&view='.$view.'&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $productChild->virtuemart_product_id,false), 'text' => $productChild->{$customfield->customfield_value}.$priceStr);

							if($selected==$child){
								$selectedFound = true;
								vmdebug($customfield->virtuemart_product_id.' $selectedFound by vRequest '.$selected);
							}
							//vmdebug('$child productId ',$child['virtuemart_product_id'],$customfield->customfield_value,$child);
							//}
						}
					}

					if(!$selectedFound){
						$pos = array_search($customfield->virtuemart_product_id, $product->allIds);
						if(isset($product->allIds[$pos-1])){
							$selected = $product->allIds[$pos-1];
							//vmdebug($customfield->virtuemart_product_id.' Set selected to - 1 allIds['.($pos-1).'] = '.$selected.' and count '.$dynChilds);
							//break;
						} elseif(isset($product->allIds[$pos])){
							$selected = $product->allIds[$pos];
							//vmdebug($customfield->virtuemart_product_id.' Set selected to allIds['.$pos.'] = '.$selected.' and count '.$dynChilds);
						} else {
							$selected = $customfield->virtuemart_product_id;
							//vmdebug($customfield->virtuemart_product_id.' Set selected to $customfield->virtuemart_product_id ',$selected,$product->allIds);
						}
					}

					$url = 'index.php?option=com_virtuemart&view='.$view.'&virtuemart_category_id='.
					$virtuemart_category_id .'&virtuemart_product_id='. $selected;
					$attribs['option.key.toHtml'] = false;
					$attribs['id'] = '[';//$idTag;


					$och = '';
					if(!empty($attribs['data-reload'])){
						$och = ' onchange="window.top.location.href=this.options[this.selectedIndex].value" data-reload=1';
						unset($attribs['data-reload']);
					} else {
						$och = ' data-dynamic-update="1" url="value"';
						unset($attribs['data-dynamic-update']);
					}

					$attribs['list.attr'] = 'size="1" class="vm-chzn-select form-select no-vm-bind avselection"'.$och;
					$attribs['list.translate'] = false;
					$attribs['option.key'] = 'value';
					$attribs['option.text'] = 'text';
					$attribs['list.select'] = Route::_ ($url,false);


					$html .= HTMLHelper::_ ('select.genericlist', $options, $fieldname, $attribs);

					vmJsApi::chosenDropDowns();

					if($customfield->parentOrderable==0){
						if($product->virtuemart_product_id==$customfield->virtuemart_product_id){
							$product->orderable = false;
							$product->product_in_stock = $parentStock;
						}
					}

					$dynChilds++;
					$customfield->display = $html;

					vmJsApi::addJScript('cvfind');

					$BrowserNewState =  '';
					if($view != 'productdetails'){
						$BrowserNewState = 'Virtuemart.setBrowserState = false;';
					}

					if($customfield->browseajax){
						$j = "jQuery(document).ready(function($) {
							".$BrowserNewState."
							$('select.avselection').off('change',Virtuemart.avFind);
							$('select.avselection').on('change',{},Virtuemart.avFind);
						});";
						vmJsApi::addJScript('avselvars',$j,true);
					}
					break;

				/*Date variant*/
				case 'D':
					if(empty($customfield->custom_value)) $customfield->custom_value = 'LC2';
					//Customer selects date
					if($customfield->is_input){
						$date = '';
						if(!empty($customfield->customfield_value)){
							try{
								$date = new DateTime();
								$date->add(new DateInterval($customfield->customfield_value));
								$date = $date->format('Y-m-d');
							} catch (Exception $e){
								$date = $customfield->customfield_value;
							}
						}
						$yearRange = '';
						if(!empty($customfield->yearRangeStart)){
							$d = new DateTime();
							$d->add(new DateInterval('P'.$customfield->yearRangeStart.'Y'));
							$d = $d->format('Y');
							$yearRange = $d;
						} else {
							$yearRange = date('Y');
						}

						if(!empty($customfield->yearRangePeriod)){
							$d = new DateTime();
							$d->add(new DateInterval('P'.$customfield->yearRangePeriod.'Y'));
							$d = $d->format('Y');
							$yearRange .= ':'.$d;
						} else {
							$yearRange .= ':1';
						}

						//$yearRange = '2018:2020';
						$customfield->display =  '<span class="product_custom_date">' . vmJsApi::jDate ($date,$customProductDataName.'[' . $customfield->virtuemart_customfield_id . ']', NULL, TRUE, $yearRange) . '</span>'; //vmJsApi::jDate($field->custom_value, 'field['.$row.'][custom_value]','field_'.$row.'_customvalue').$priceInput;
					}
					//Customer just sees a date
					else {
						$customfield->display =  '<span class="product_custom_date">' . vmJsApi::date ($customfield->customfield_value, $customfield->custom_value, TRUE) . '</span>';
					}

					break;
				/* text area or editor No vmText, only displayed in BE */

				case 'X':
					$customfield->display = $customfield->customfield_params;

					break;
				case 'B':
				case 'Y':
					$customfield->display =  $customfield->customfield_value;

					break;


				/* string or integer */
				case 'S':
				case 'M':
				case 'PB':
					if(VmConfig::get('hideEmptyCustomfields',false) and empty($customfield->customfield_value)) break;
					//vmdebug('Example for params ',$customfield);
					if(isset($customfield->selectType)){
						if(empty($customfield->selectType)){
							$selectType = 'select.genericlist';
							if(!empty($customfield->is_input)){
								vmJsApi::chosenDropDowns();
								$class = 'class="vm-chzn-select form-select"';
								$idTag = '[';
							}
						} else {
							$selectType = 'select.radiolist';
							$class = '';
						}
					} else {
						if($type== 'M'){
							$selectType = 'select.radiolist';
							$class = '';
						} else {
							$selectType = 'select.genericlist';
							if(!empty($customfield->is_input)){
								vmJsApi::chosenDropDowns();
								$class = 'class="vm-chzn-select form-select"';
								$idTag = '[';
							}
						}
					}

					if($customfield->is_list and $customfield->is_list!=2){

						if(!empty($customfield->is_input)){

							$options = array();

							if($customfield->addEmpty){
								$options[0] = $emptyOption;
							}

							$values = explode (';', $customfield->custom_value);

							foreach ($values as $val) {

								//if($val == 0 and $customfield->addEmpty){
									//continue;
								//}
								if($type == 'M'){
									$tmp = array('value' => $val, 'text' => VirtueMartModelCustomfields::displayCustomMedia ($val,'product',$customfield->width,$customfield->height));
								} else {
									$tmp = array('value' => $val, 'text' => vmText::_($val));
								}
								$options[] = (object)$tmp;
							}
							$currentValue = $customfield->customfield_value;

							$customfield->display = HTMLHelper::_ ($selectType, $options, $customProductDataName.'[' . $customfield->virtuemart_customfield_id . ']', $class, 'value', 'text', $currentValue,$idTag);
						} else {
							if($type == 'M'){
								$customfield->display =  VirtueMartModelCustomfields::displayCustomMedia ($customfield->customfield_value,'product',$customfield->width,$customfield->height);
							} else {
								$customfield->display =  vmText::_ ($customfield->customfield_value);
							}
						}
					} else {

						if(!empty($customfield->is_input)){
							$presetValue = null;
							$presetValueFb = null;
							if(!isset($selectList[$customfield->virtuemart_custom_id])) {
								$selectList[$customfield->virtuemart_custom_id] = $key;
								if($customfield->addEmpty){
									if ($selectType == 'select.radiolist' && $type == 'M') {
										// We need the empty radio in separate html for display reasons. See line 678
									} else {
										if(empty($customfields[$selectList[$customfield->virtuemart_custom_id]]->options)){
											$customfields[$selectList[$customfield->virtuemart_custom_id]]->options[0] = $emptyOption;
											$customfields[$selectList[$customfield->virtuemart_custom_id]]->options[0]->virtuemart_customfield_id = $emptyOption->value;
											$presetValue = $emptyOption->value;
											vmdebug('$presetvalue by $emptyOption '.$customfield->virtuemart_custom_id);
											//$customfields[$selectList[$customfield->virtuemart_custom_id]]->options['nix'] = array('virtuemart_customfield_id' => 'none', 'text' => vmText::_ ('COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT'));
										}
									}
								}

								$tmpField = clone($customfield);
								$tmpField->options = null;
								$customfield->options[$customfield->virtuemart_customfield_id] = $tmpField;

								$customfield->customProductDataName = $customProductDataName;

							} else {
								$customfields[$selectList[$customfield->virtuemart_custom_id]]->options[$customfield->virtuemart_customfield_id] = $customfield;
								unset($customfields[$key]);

							}

							if (isset($customfields[$selectList[$customfield->virtuemart_custom_id]])) {
								$default = reset($customfields[$selectList[$customfield->virtuemart_custom_id]]->options);//vmdebug('list with empty option',$default, $customfields[$selectList[$customfield->virtuemart_custom_id]]->options);
							}
							else {
								$customfields[$selectList[$customfield->virtuemart_custom_id]] = new stdClass();
								$customfields[$selectList[$customfield->virtuemart_custom_id]]->options = array();
								$default = new stdClass();
							}
							if(!isset($default->customfield_value)){
								$default->customfield_value = '';
							}
							foreach ($customfields[$selectList[$customfield->virtuemart_custom_id]]->options as $k => $productCustom) {

								if($k == 0){
									//$productCustom->text =
									$presetValueFb = $productCustom->customfield_value;
								} else {
									if(!isset($productCustom->customfield_price)) $productCustom->customfield_price = 0.0;
									if(!isset($productCustom->customfield_value)) $productCustom->customfield_value = '';
									$price = VirtueMartModelCustomfields::renderCustomfieldPrice($productCustom, $product, $calculator);
									if($type == 'M'){
										$productCustom->text = '<span class="vm-img-cf-container d-block border-bottom pb-1 mb-1">' . VirtueMartModelCustomfields::displayCustomMedia ($productCustom->customfield_value,'product',$customfield->width,$customfield->height) .'</span><span class="vm-img-cf-price d-block">' . $price . '</span>';
									} else if($type == 'PB') {
										$productB = VmModel::getModel('product')->getProduct($productCustom->bundle_product_id);
										/*$images[0] = $productB->virtuemart_media_id[0];
										$width = isset($customfield->width)? $customfield->width : VmConfig::get('img_width', 0);
										$height = isset($customfield->height)? $customfield->height : VmConfig::get('img_height', 0);
										$thumb = '';
										//if(!empty($width) or !empty($height)){
											$thumb = VirtueMartModelCustomfields::displayCustomMedia($images[0], 'product', $width, $height ).' ';
										//}*/
										$productCustom->text = /*$thumb . ' ' . */$productB->product_name . ' ' . $price;
									} else {
										$trValue = vmText::_($productCustom->customfield_value);
										if($productCustom->customfield_value!=$trValue and strpos($trValue,'%1')!==false){
											$productCustom->text = vmText::sprintf($productCustom->customfield_value,$price);
										} else {
											$productCustom->text = $trValue.' '.$price;
										}
									}

								}
								$customfields[$selectList[$customfield->virtuemart_custom_id]]->options[$k] = $productCustom;

							}


							if(!isset($presetValue)){
								//$presetValue = $default->customfield_value;
								foreach($customfields[$selectList[$customfield->virtuemart_custom_id]]->options as $option){

									if(!empty($option->virtuemart_custom_id) and (int)$option->virtuemart_custom_id==87){
										vmdebug('HMMMMMMMMMMMMMMMMMMMMMMMMMMM $option->customfield_id== ',$option->virtuemart_customfield_id);
									}
									if(empty($option->customfield_price) or $option->customfield_price=='0.000000'){
										if(isset($option->value)){
											$presetValue = $option->value;
											//vmdebug('Was geht ? ',$presetValue);
										} else {
											$presetValue = $option->virtuemart_customfield_id;

										}
										//$presetValue = isset($option->value)?$option->value:$option->virtuemart_customfield_id;
										//vmdebug('empty($option->customfield_price) '.$presetValue,$option);
										break;
									} else {
										//vmdebug('NOT empty($option->customfield_price) ',$option->customfield_price);
									}
								}
							}

							if(!isset($presetValue)){
								if(isset($presetValueFb)){
									$presetValue = $presetValueFb;
								} else {
									$presetValue = $default->customfield_value;
								}
								// $default->customfield_value;
								//vmdebug('HMMMMMMMMMMMMMMMMMMMMMMMMMMM $option->customfield_id== ',$presetValue,$option->virtuemart_customfield_id);
							}

							$customfields[$selectList[$customfield->virtuemart_custom_id]]->display = HTMLHelper::_ ($selectType, $customfields[$selectList[$customfield->virtuemart_custom_id]]->options,
							$customfields[$selectList[$customfield->virtuemart_custom_id]]->customProductDataName,
							$class, 'virtuemart_customfield_id', 'text', $presetValue, $idTag);

							if ($selectType == 'select.radiolist' && $customfield->addEmpty && $type == 'M') {
								$radioTitle = '<div class="form-check ms-2"><input class="form-check-input" id="' . $idTag . '" type="radio" name="' . $customfields[$selectList[$customfield->virtuemart_custom_id]]->customProductDataName . '" value="0" checked /><label class="form-check-label" for="' . $idTag . '">' . $emptyOption->text . '</label></div>';
								$customfields[$selectList[$customfield->virtuemart_custom_id]]->display = $radioTitle . $customfields[$selectList[$customfield->virtuemart_custom_id]]->display;
							}

						} else {
							if($type == 'M'){
								$customfield->display = VirtueMartModelCustomfields::displayCustomMedia ($customfield->customfield_value,'product',$customfield->width,$customfield->height);
							} else {
								$customfield->display = vmText::_ ($customfield->customfield_value);
							}
						}
					}

					break;

				// Property
				case 'P':
					//$customfield->display = vmText::_ ('COM_VIRTUEMART_'.strtoupper($customfield->customfield_value));
					$attr = $customfield->customfield_value;
					$lkey = 'COM_VIRTUEMART_'.strtoupper($customfield->customfield_value).'_FE';
					$trValue = vmText::_ ($lkey);
					$options[] = array('value' => 'product_length', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
					$options[] = array('value' => 'product_width', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
					$options[] = array('value' => 'product_height', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
					$options[] = array('value' => 'product_weight', 'text' => vmText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));

					$dim = '';

					if($attr == 'product_length' or $attr == 'product_width' or $attr == 'product_height'){
						$dim = $product->product_lwh_uom;
					} else if($attr == 'product_weight') {
						$dim = $product->product_weight_uom;
					}
					if(!isset($product->{$attr})){
						logInfo('customfield.php: case P, property '.$attr.' does not exists. virtuemart_custom_id: '.$customfield->virtuemart_custom_id);
						break;
					}
					$val = $product->{$attr};
					if($customfield->round!=0){
						if(empty($customfield->digits)) $customfield->digits = 0;
						$val = $currency->getFormattedNumber($val,$customfield->digits);
					}
					if($lkey!=$trValue and strpos($trValue,'%1')!==false) {
						$customfield->display = vmText::sprintf( $customfield->customfield_value, $val , $dim );
					} else if($lkey!=$trValue) {
						$customfield->display = $trValue.' '.$val;
					} else {
						$customfield->display = vmText::_ ('COM_VIRTUEMART_'.strtoupper($customfield->customfield_value)).' '.$val.$dim;
					}

					break;
				case 'Z':
					if(empty($customfield->customfield_value)) break;
					$html = '';
					$q = 'SELECT * FROM `#__virtuemart_categories_' . VmConfig::$vmlang . '` as l INNER JOIN `#__virtuemart_categories` AS c ON (l.`virtuemart_category_id`=c.`virtuemart_category_id`) WHERE `published`=1 AND l.`virtuemart_category_id`= "' . (int)$customfield->customfield_value . '" ';
					$db = Factory::getDBO();
					$db->setQuery ($q);
					if ($category = $db->loadObject ()) {

						if(empty($category->virtuemart_category_id)) break;

						$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_category_medias`WHERE `virtuemart_category_id`= "' . $category->virtuemart_category_id . '" ';
						$db->setQuery ($q);
						$thumb = '';
						if ($media_id = $db->loadResult ()) {
							$thumb = VirtueMartModelCustomfields::displayCustomMedia ($media_id,'category',$customfield->width,$customfield->height);
						}
						$customfield->display = HTMLHelper::link (Route::_ ('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id), $thumb . ' ' . $category->category_name, array('title' => $category->category_name));
					}

					break;


				case 'RC':
					$pModel = VmModel::getModel('product');
					if(!empty($customfield->customfield_value)){
						$prodIds = explode(',', $customfield->customfield_value);
						foreach($prodIds as $pId){
							$related = $pModel->getProduct((int)$pId,TRUE,$customfield->wPrice,FALSE,1);
							if(!$related){
								vmError('Custom related Product with id '.$pId.' not found');
								continue;
							} else if($related->published){
								$customfield->display .= VirtueMartCustomFieldRenderer::renderRelatedProduct($customfield,$related);
							}

						}
					}
					break;
				case 'R':
					$customfield->customfield_value = (int) $customfield->customfield_value;

					if(empty($customfield->customfield_value)){
						vmError('Related Product customfield with id ' . $customfield->viartuemart_customfield_id . ' has no value');
						$customfield->display = 'customfield related product has no value';
						return;
					}
					$pModel = VmModel::getModel('product');
					$related = $pModel->getProduct((int)$customfield->customfield_value,TRUE,$customfield->wPrice,FALSE,1);

					if(!$related) {
						vmError('Related Product with id ' . $customfield->customfield_value . ' not found');
						break;
					} else if($related->published){
						$customfield->display = VirtueMartCustomFieldRenderer::renderRelatedProduct($customfield,$related);
						vmdebug('Rendered related product '.(int)$customfield->customfield_value);
					}


					break;
			}

			//$viewData['customfields'][$key] = $customfield;
			//vmdebug('my customfields '.$type,$viewData['customfields'][$k]->display);
		}

	}

	static function renderRelatedProduct($customfield,$related){


		$thumb = '';
		if($customfield->wImage) {
			if(!empty( $related->virtuemart_media_id[0] )) {
				$thumb = VirtueMartModelCustomfields::displayCustomMedia( $related->virtuemart_media_id[0], 'product', $customfield->width, $customfield->height ).' ';
			} else {
				$thumb = VirtueMartModelCustomfields::displayCustomMedia( 0, 'product', $customfield->width, $customfield->height ).' ';
			}
		}

		if($customfield->waddtocart){
			if (!empty($related->customfields)) {

				$customfieldsModel = VmModel::getModel ('customfields');
				if(empty($customfield->from)) {
					$customfield->from = $related->virtuemart_product_id;
					$customfieldsModel -> displayProductCustomfieldFE ($related, $related->customfields);
				} else if($customfield->from!=$related->virtuemart_product_id){
					$customfieldsModel -> displayProductCustomfieldFE ($related, $related->customfields);
				}

			}
			$isCustomVariant = false;
			if (!empty($related->customfields)) {
				foreach ($related->customfields as $k => $custom) {
					if($custom->field_type == 'C' and $custom->virtuemart_product_id != (int)$customfield->customfield_value){
						$isCustomVariant = $custom;
					}
					if (!empty($custom->layout_pos)) {
						$related->customfieldsSorted[$custom->layout_pos][] = $custom;
					} else {
						$related->customfieldsSorted['normal'][] = $custom;
					}
					unset($related->customfields);
				}

			}
		}

		return shopFunctionsF::renderVmSubLayout('related',array('customfield'=>$customfield,'related'=>$related, 'thumb'=>$thumb));


	}

	static function renderCustomfieldsCart($product, $html, $trigger){
		if(isset($product->param)){
			vmTrace('param found, seek and destroy');
			return false;
		}
		$row = 0;

		$variantmods = isset($product -> customProductData)? $product->customProductData: $product->product_attribute;

		if(!is_array($variantmods)){
			$variantmods = vmJsApi::safe_json_decode($variantmods,true);
			if(!is_array($variantmods)){
				vmdebug('renderCustomfieldsCart decoding $variantmods returns not array '.$variantmods);
				$variantmods = array();
			}
		}

		//We let that here as Fallback
		if(empty($product->customfields)){

			$productDB = VmModel::getModel('product')->getProduct($product->virtuemart_product_id);
			if($productDB and !empty($productDB->customfields)){

				$product->customfields = $productDB->customfields;
			} else {
				$product->customfields = array();
			}
		}
		//vmdebug('renderCustomfieldsCart $variantmods',$variantmods);
		$productCustoms = array();
		foreach( (array)$product->customfields as $prodcustom){

			//We just add the customfields to be shown in the cart to the variantmods
			if(is_object($prodcustom)){

				//We need this here to ensure that broken customdata of order items is shown updated info, or at least displayed,
				if($prodcustom->is_cart_attribute or $prodcustom->is_input){

					//The problem here is that a normal value and an array can be valid. The function should complement, or update the product. So we are not allowed
					//to override existing values. When the $variantmods array is not set for the key, then we add an array, when the customproto is used more than one time
					if(!isset($variantmods[$prodcustom->virtuemart_custom_id])){
						$variantmods[$prodcustom->virtuemart_custom_id][$prodcustom->virtuemart_customfield_id] = $prodcustom->virtuemart_customfield_id;
					}
					//the missing values are added with an own key.
					if( is_array($variantmods[$prodcustom->virtuemart_custom_id]) and
						!isset($variantmods[$prodcustom->virtuemart_custom_id][$prodcustom->virtuemart_customfield_id]) ){

						$variantmods[$prodcustom->virtuemart_custom_id][$prodcustom->virtuemart_customfield_id] = $prodcustom->virtuemart_customfield_id;
					}
				}

				$productCustoms[$prodcustom->virtuemart_customfield_id] = $prodcustom;
			}
		}

		foreach ( (array)$variantmods as $i => $customfield_ids) {

			if(!is_array($customfield_ids)){
				$customfield_ids = array( $customfield_ids =>false);
			}

			foreach($customfield_ids as $customfield_id=>$params){

				if(empty($productCustoms) or !isset($productCustoms[$customfield_id])){
					//vmdebug('renderCustomfieldsCart continue',$customfield_id,$productCustoms);
					continue;
				}
				$productCustom = $productCustoms[$customfield_id];
				//vmdebug('displayProductCustomfieldSelected ',$customfield_id,$productCustom);
				//The stored result in vm2.0.14 looks like this {"48":{"textinput":{"comment":"test"}}}
				//and now {"32":[{"invala":"100"}]}
				if (!empty($productCustom)) {
					$otag = ' <span class="product-field-type-' . $productCustom->field_type . '">';
					$tmp = '';
					if ($productCustom->field_type == "E") {

						vDispatcher::importVMPlugins ('vmcustom');
						vDispatcher::trigger ($trigger.'VM3', array(&$product, &$productCustom, &$tmp));
					}
					else {
						$value = '';

						if (($productCustom->field_type == 'G')) {
							$db = Factory::getDBO ();
							$db->setQuery ('SELECT  `product_name` FROM `#__virtuemart_products_' . VmConfig::$vmlang . '` WHERE virtuemart_product_id=' . (int)$productCustom->customfield_value);
							$child = $db->loadObject ();
							$value = $child->product_name;
						}
						elseif (($productCustom->field_type == 'M')) {
							$customFieldModel = VmModel::getModel('customfields');
							$value = $customFieldModel->displayCustomMedia ($productCustom->customfield_value,'product',$productCustom->width,$productCustom->height,VirtueMartModelCustomfields::$useAbsUrls);
						}
						elseif (($productCustom->field_type == 'S')) {

							if($productCustom->is_list and $productCustom->is_input){
								if($productCustom->is_list==2){
									$value = vmText::_($productCustom->customfield_value);
								} else {
									$value = vmText::_($params);
								}

							} else {
								$value = vmText::_($productCustom->customfield_value);
							}
						}
						elseif (($productCustom->field_type == 'PB')) {
							///vmdebug('$productCustom->customfield_value',$productCustom);
							$productDB = VmModel::getModel('product')->getProduct($productCustom->bundle_product_id);
							//my suggestion, we could even add the price here
							$value = $productDB->product_name. ' ('.$productDB->product_sku .') - '.$productDB->virtuemart_product_id;
						}
						elseif (($productCustom->field_type == 'A')) {
							if(!property_exists($product,$productCustom->customfield_value)){
								$productDB = VmModel::getModel('product')->getProduct($product->virtuemart_product_id);
								if($productDB){
									$attr = $productCustom->customfield_value;
									$product->{$attr} = $productDB->{$attr};
								}
							}
							$value = vmText::_( $product->{$productCustom->customfield_value} );
						}
						elseif (($productCustom->field_type == 'C')) {

							foreach($productCustom->options->{$product->virtuemart_product_id} as $k=>$option){
								$value .= '<span> ';
								if(!empty($productCustom->selectoptions[$k]->clabel) and in_array($productCustom->selectoptions[$k]->voption,VirtueMartModelCustomfields::$dimensions)){
									$value .= vmText::_('COM_VIRTUEMART_'.$productCustom->selectoptions[$k]->voption);
									$rd = $productCustom->selectoptions[$k]->clabel;
									if(is_numeric($rd) and is_numeric($option)){
										$value .= ' '.number_format(round((float)$option,(int)$rd),$rd);
									}
								} else {
									if(!empty($productCustom->selectoptions[$k]->clabel)) $value .= vmText::_($productCustom->selectoptions[$k]->clabel);
									$value .= ' '.vmText::_($option).' ';
								}
								$value .= '</span><br />';
							}
							$value = trim($value);
							if(!empty($value)){
								$html .= $otag.$value.'</span><br />';
							}

							continue;
						}
						elseif (($productCustom->field_type == 'D')) {
							//vmdebug('my date product customfield',$productCustom);
							if($productCustom->is_input){
								$value = $params;
							} else {
								$value = $productCustom->customfield_value;
							}

						}
						else {
							$value = vmText::_($productCustom->customfield_value);
						}
						$trTitle = vmText::_($productCustom->custom_title);
						$tmp = '';

						if($productCustom->custom_title!=$trTitle and strpos($trTitle,'%1')!==false){
							$tmp .= vmText::sprintf($productCustom->custom_title,$value);
						} else {
							$tmp .= $trTitle.' '.$value;
						}
					}
					if(!empty($tmp)){
						$html .= $otag.$tmp.'</span><br />';
					}


				}
				else {
					foreach ((array)$customfield_id as $key => $value) {
						$html .= '<br />Couldnt find customfield' . ($key ? '<span>' . $key . ' </span>' : '') . $value;
					}
					vmdebug ('customFieldDisplay, $productCustom is EMPTY '.$customfield_id);
				}
			}

		}

		return $html . '</div>';
	}
}

