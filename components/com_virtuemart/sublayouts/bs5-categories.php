<?php

/**
*
* Shows the products/categories of a category
*
* @package	VirtueMart
* @subpackage
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2020 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
 * @version $Id: default.php 6104 2012-06-13 14:15:29Z alatak $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

/** @var TYPE_NAME $viewData */
$categories = $viewData['categories'];
$categories_per_row = !empty($viewData['categories_per_row']) ? $viewData['categories_per_row'] : VmConfig::get ( 'categories_per_row', 3 );
if (empty($categories_per_row)) $categories_per_row = 3;
// Calculating Categories Per Row
$bscol = ' col-xl-' . floor ( 12 / $categories_per_row );
?>

<?php if ($categories) : ?>
	<div class="vm-category-subcategories mb-3 mb-xl-5">
		<div class="row gy-4 justify-content-center">
			<?php foreach ($categories as $category) : ?>
				<?php $caturl = Route::_ ( 'index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id , FALSE); ?>
				<div class="vm-subcategory col-6 col-md-4 col-lg-3<?php echo $bscol; ?> text-center">
					<a href="<?php echo $caturl ?>" title="<?php echo vmText::_($category->category_name) ?>">
						<?php echo $category->images[0]->displayMediaThumb('class="browseCategoryImage img-fluid mb-3"',false); ?>
						<h2 class="vm-subcategory-title fw-normal pt-2 border-top"><?php echo vmText::_($category->category_name) ?></h2>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>