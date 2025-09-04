<?php

/**
 *
 * Show the products in a category
 *
 * @package    VirtueMart
 * @subpackage
 * @author RolandD
 * @author Max Milbers
 * @todo add pagination
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10982 2024-03-18 08:58:44Z  $
 */

defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

if (vRequest::getInt('dynamic',false) and vRequest::getInt('virtuemart_product_id',false)) {
	if (!empty($this->products)) {
		if ($this->fallback) {
			$p = $this->products;
			$this->products = array();
			$this->products[0] = $p;
			vmdebug('Refallback');
		}

		echo shopFunctionsF::renderVmSubLayout($this->productsLayout,array('products'=>$this->products,'currency'=>$this->currency,'products_per_row'=>$this->perRow,'showRating'=>$this->showRating));
	}

	return ;
}
?>
<div class="category-view mb-4">
	<?php if ($this->show_store_desc and !empty($this->vendor->vendor_store_desc)) : ?>
		<div class="vm-vendor-store-desc mb-3 mb-xl-5 text-center">
			<?php echo $this->vendor->vendor_store_desc; ?>
		</div>
	<?php endif; ?>

	<?php if (!empty($this->category->category_name)) : ?>
		<h1 class="vm-category-title fw-normal text-center mb-3"><?php echo vmText::_($this->category->category_name); ?></h1>
	<?php endif; ?>

	<?php if ($this->showcategory_desc and empty($this->keyword)) : ?>
		<?php if (!empty($this->category->category_description)) : ?>
			<div class="vm-category-description text-center mb-3 mb-xl-5">
				<?php echo $this->category->category_description; ?>
			</div>
		<?php endif; ?>

		<?php if(!empty($this->manu_descr)) : ?>
			<div class="vm-manufacturer-description text-center mb-3 mb-xl-5">
				<?php echo $this->manu_descr; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($this->showcategory and empty($this->keyword) and !empty($this->category->has_children)) : ?>
		<?php echo ShopFunctionsF::renderVmSubLayout('categories',array('categories'=>$this->category->children, 'categories_per_row'=>$this->categories_per_row)); ?>
	<?php endif; ?>

	<?php if (!empty($this->products) or ($this->showsearch or $this->keyword !== false)) : ?>
		<div class="browse-view">
			<?php if ($this->showsearch or $this->keyword !== false) : ?>
				<?php
				//id taken in the view.html.php could be modified
				$category_id  = vRequest::getInt ('virtuemart_category_id', 0);
				?>

				<!--BEGIN Search Box -->
				<div class="virtuemart_search p-3 mb-3 border bg-light">
					<form class="row align-items-end" action="<?php echo Route::_ ('index.php?option=com_virtuemart&view=category&limitstart=0', FALSE); ?>" method="get">
						<?php if (!empty($this->searchCustomList)) : ?>
						<div class="vm-search-custom-list col-12">
							<?php echo $this->searchCustomList ?>
						</div>
						<?php endif; ?>

						<?php if(!empty($this->searchCustomValuesAr)) : ?>
						<div class="vm-search-custom-values<?php echo count($this->searchCustomValuesAr) <=3 ? ' col ' : ' col-12 '; ?> mb-2">
							<div class="row gx-2 gy-1 gy-xl-3 align-items-end">
								<?php echo shopFunctionsF::renderVmSubLayout('searchcustomvalues', array('searchcustomvalues' => $this->searchCustomValuesAr)); ?>

								<?php if (count($this->searchCustomValuesAr) > 1) : ?>
									<div class="<?php echo count($this->searchCustomValuesAr) <=3 ? ' col-xl-3 ' : ' col-xl-2 '; ?> col-md-4 col-6">
										<div class="form-check small ms-1">
											<?php echo VmHtml::checkbox ('combineTags', $this->combineTags, 1, 0, 'class="form-check-input"', 'combineTags'); ?>
											<label class="form-check-label" for="combineTags"><?php echo vmText::_('COM_VM_COMBINETAGS'); ?></label>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php endif; ?>

						<div class="<?php echo !empty($this->searchCustomValuesAr) && count($this->searchCustomValuesAr) <=3 ? 'col-auto ' : 'col-12 '; ?>d-flex mb-3 mb-xl-2">
							<div class="vm-search-custom-search-input input-group mt-auto">
								<input name="keyword" class="form-control" type="text" size="40" value="<?php echo $this->keyword ?>" placeholder="<?php echo vmText::_ ('COM_VIRTUEMART_SEARCH') ?>" />
								<button class="btn btn-primary"type="submit" title="<?php echo vmText::_ ('COM_VIRTUEMART_SEARCH') ?>" data-bs-toggle="tooltip">
									<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
										<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"></path>
									</svg>
								</button>
							</div>
						</div>

						<div class="col-12">
							<div class="vm-search-descr small text-end text-muted m-0"><?php echo vmText::_('COM_VM_SEARCH_DESC') ?></div>
						</div>

						<input type="hidden" name="view" value="category"/>
						<input type="hidden" name="option" value="com_virtuemart"/>
						<input type="hidden" name="virtuemart_category_id" value="<?php echo $category_id; ?>"/>
						<input type="hidden" name="Itemid" value="<?php echo $this->Itemid; ?>"/>
					</form>
				</div>
				<!-- End Search Box -->

				<?php
				$j = 'jQuery(document).ready(function($) {
						$(".changeSendForm")
							.off("change",Virtuemart.sendCurrForm)
							.on("change",Virtuemart.sendCurrForm);
				})';

				vmJsApi::addJScript('sendFormChange',$j);
				?>
			<?php endif; ?>

			<?php if (!empty($this->products) && !empty($this->orderByList)) : // Orderby-displaynumber ?>
				<div class="orderby-displaynumber pt-2 pb-3 mb-4 border-top border-bottom">
					<div class="vm-order-list row gy-2 align-items-end">
						<div class="col-6 col-md-4 col-lg-3">
							<?php echo $this->orderByList['orderby']; ?>
						</div>
						<div class="col-6 col-md-4 col-lg-3">
							<?php echo $this->orderByList['manufacturer']; ?>
						</div>
						<div class="col-md-4 col-lg-2 ms-auto display-number text-md-end">
							<label for="limit" class="form-label mb-1"><?php echo $this->vmPagination->getResultsCounter ();?></label>
							<?php echo str_replace('inputbox','form-select w-auto ms-md-auto', $this->vmPagination->getLimitBox ($this->category->limit_list_step)); ?>
						</div>
					</div>
				</div>

				<?php if ($this->vmPagination->getPagesCounter() != null) : ?>
					<div class="vm-pagination vm-pagination-top px-3 py-2 bg-light small mb-4">
						<div class="row">
							<div class="col-lg-2">
								<span class="vm-page-counter"><?php echo $this->vmPagination->getPagesCounter (); ?></span>
							</div>
							<div class="col-lg-10">
								<?php echo str_replace(array('<ul>','</ul>'), array('<nav aria-label="Page navigation"><ul class="pagination justify-content-end mb-0">','</ul></nav>'), $this->vmPagination->getPagesLinks ()); ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if (!empty($this->products)) : ?>
				<?php
				//revert of the fallback in the view.html.php, will be removed vm3.2
				if ($this->fallback) {
					$p = $this->products;
					$this->products = array();
					$this->products[0] = $p;
					vmdebug('Refallback');
				}
				?>

				<?php echo shopFunctionsF::renderVmSubLayout($this->productsLayout,array('products'=>$this->products,'currency'=>$this->currency,'products_per_row'=>$this->perRow,'showRating'=>$this->showRating)); ?>

				<?php if (!empty($this->orderByList) && $this->vmPagination->getPagesCounter() != null) : ?>
					<div class="vm-pagination vm-pagination-bottom px-3 py-2 bg-light small my-5">
						<div class="row">
							<div class="col-lg-2">
								<span class="vm-page-counter"><?php echo $this->vmPagination->getPagesCounter (); ?></span>
							</div>
							<div class="col-lg-10">
								<?php echo str_replace(array('<ul>','</ul>'), array('<nav aria-label="Page navigation"><ul class="pagination justify-content-end mb-0">','</ul></nav>'), $this->vmPagination->getPagesLinks ()); ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php elseif ($this->keyword !== false) : ?>
				<p class="alert alert-info"><?php echo vmText::_ ('COM_VIRTUEMART_NO_RESULT') . ($this->keyword ? ' : (' . $this->keyword . ')' : ''); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>