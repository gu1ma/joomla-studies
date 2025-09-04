<?php
/**
*
* Shows the products/categories of a category
*
* @package	VirtueMart
* @subpackage
* @author Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
 * @version $Id: default.php 6104 2012-06-13 14:15:29Z alatak $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/** @var TYPE_NAME $viewData */
$categories = $viewData['categories'];

if ($categories) {

$categories_per_row = !empty($viewData['categories_per_row'])? $viewData['categories_per_row']:VmConfig::get ( 'categories_per_row', 3 );
if(empty($categories_per_row)) $categories_per_row = 3;

// Calculating Categories Per Row
$category_cellwidth = ' col-xs-6 col-md-' . floor ( 12 / $categories_per_row ) . ' col-sm-' . floor ( 12 / $categories_per_row );

$ajaxUpdate = '';
if(VmConfig::get ('ajax_category', 1)){
	$ajaxUpdate = 'data-dynamic-update="1"';
}
?>

<div class="category-view">
	<div class="row flex">
	<?php
	// Start the Output
	foreach ( $categories as $category ) {
	// Category Link
	$caturl = JRoute::_ ( 'index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id , FALSE);

	// Show Category ?>
	<div class="category <?php echo $category_cellwidth ?>">
		<div class="thumbnail">
			<a href="<?php echo $caturl ?>" <?php echo $ajaxUpdate?> >
				<div class="text-center" data-mh="image-wrapper">
				<?php echo $category->images[0]->displayMediaThumb('class="browseCategoryImage"',false); ?>
				</div>
				<div class="caption text-center" data-mh="cat-name">
					<h2 class="vm-cat-title">
					<?php echo vmText::_($category->category_name) ?>
					</h2>
				</div>
			</a>
		</div>
	</div>
	<?php } ?>
	</div>
</div>
<?php }