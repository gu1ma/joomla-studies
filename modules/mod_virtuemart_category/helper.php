<?php
defined ('_JEXEC') or  die('Direct Access to ' . basename (__FILE__) . ' is not allowed.');
/*
 * Module Helper
 * @package VirtueMart
 * @copyright (C) 2011 - 2021 The VirtueMart Team
 * @Email: max@virtuemart.net
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 * @link https://virtuemart.net
 */

class mod_virtuemart_category {

	static function displayCatsMod($module, $params, $active_category_id, $category_id, $layout){

		vmLanguage::loadJLang('mod_virtuemart_category', true);

		/* Setting */
		$categoryModel = VmModel::getModel('Category');
		$ID = str_replace('.', '_', substr(microtime(true), -8, 8));   //legacy
		$class_sfx = $params->get('class_sfx', '');
		$moduleclass_sfx = $params->get('moduleclass_sfx','');
		//$layout = $params->get('layout','default');
		//$active_category_id = vRequest::getInt('virtuemart_category_id', '0');
		$vendorId = 1;

		$level = (int)$params->get('level','2');


		if( strpos($layout, 'wall')!==FALSE ){
			$media = true;
		} else {
			$media = (int)$params->get('media', 0);
		}

		$categories = array();
		vmSetStartTime('categories');
		//VirtueMartModelCategory::rekurseCategories($vendorId, $category_id, $categories, $level, 0, 0,true, '', 'c.ordering, category_name', 'ASC', true, 0, $media);
		$categories = VirtueMartModelCategory::getCatsTree(true, $vendorId, $category_id, $level, $media);
		vmTime('my categories module time','categories');

		$parentCategories = $categoryModel->getCategoryRecurse($active_category_id,0);

		ob_start();
		/* Load tmpl default */
		require(JModuleHelper::getLayoutPath('mod_virtuemart_category',$layout));
		$output = ob_get_clean();
		echo $output;
	}
}

?>