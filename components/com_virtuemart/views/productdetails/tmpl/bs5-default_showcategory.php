<?php

/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen

 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2012 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default_showcategory.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined ( '_JEXEC' ) or die ( 'Restricted access' );

use Joomla\CMS\Router\Route;
?>

<?php if (!empty($this->category->children) && VmConfig::get('showcategory', 0)) : ?>
	<?php $bscol = ' col-lg-' . floor ( 12 / VmConfig::get('categories_per_row', 3)); ?>
	<div class="vm-category-subcategories mb-3">
		<h2 class="vm-section-title pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_SUBCATEGORIES'); ?></h2>
		<div class="row gy-4">
			<?php foreach ($this->category->children as $category) : ?>
				<?php $caturl = Route::_ ( 'index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id , FALSE); ?>
				<div class="vm-subcategory col-6 col-md-4<?php echo $bscol; ?> text-center">
					<a href="<?php echo $caturl ?>" title="<?php echo vmText::_($category->category_name) ?>">
						<?php echo $category->images[0]->displayMediaThumb('class="browseCategoryImage img-fluid mb-3"',false); ?>
						<h2 class="vm-subcategory-title fw-normal pt-2 border-top"><?php echo vmText::_($category->category_name) ?></h2>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>