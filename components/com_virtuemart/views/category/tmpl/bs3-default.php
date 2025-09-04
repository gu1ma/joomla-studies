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
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 8811 2015-03-30 23:11:08Z Milbo $
 */

defined ('_JEXEC') or die('Restricted access');
$doc = JFactory::getDocument();
$category_id  = vRequest::getInt ('virtuemart_category_id', 0);
$manufacturer_id  = vRequest::getInt ('virtuemart_manufacturer_id', 0);

if (vRequest::getInt('dynamic',false) and vRequest::getInt('virtuemart_product_id',false)) {
	if (!empty($this->products)) {
		if($this->fallback){
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
<div class="category-view">
	<?php if ( $this->category->category_name ) { ?>
	<h1 class="page-header"><?php echo vmText::_($this->category->category_name); ?></h1>
	<?php } ?>
	<?php if ($this->show_store_desc and !empty($this->vendor->vendor_store_desc)) { ?>
	<div class="vendor-store-desc">
	<?php echo $this->vendor->vendor_store_desc; ?>
	</div>
	<?php } ?>

	<?php if (!empty($this->showcategory_desc) and empty($this->keyword)) { ?>
		<?php if (!empty($this->category->category_description)) { ?>
		<div class="category_description">
			<?php echo $this->category->category_description; ?>
		</div>
		<?php } ?>
	<?php } ?>

	<?php if(!empty($this->manu_descr)) { ?>
	<div class="manufacturer-description">
		<?php echo $this->manu_descr; ?>
	</div>
	<?php } ?>

	<?php	// Show child categories
	if ($this->showcategory and empty($this->keyword) and $manufacturer_id == 0) {
		if (!empty($this->category->haschildren)) {
			echo ShopFunctionsF::renderVmSubLayout('categories',array('categories'=>$this->category->children, 'categories_per_row'=>$this->categories_per_row));
		}
	}

	if (!empty($this->products) or ($this->showsearch or $this->keyword !== false)) {
	?>
	<div class="browse-view">
		<?php
		if ($this->showsearch or $this->keyword !== false) {
		//id taken in the view.html.php could be modified
		$category_id  = vRequest::getInt ('virtuemart_category_id', 0); ?>
		<!--BEGIN Search Box -->
		<div class="virtuemart_search">
			<form action="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=category&limitstart=0', FALSE); ?>" method="get">
				<?php if(!empty($this->searchCustomList)) { ?>
				<div class="vm-search-custom-list">
					<?php echo $this->searchCustomList ?>
				</div>
				<?php } ?>

				<?php if(!empty($this->searchCustomValuesAr)) { ?>
				<div class="vm-search-custom-values">
				<?php
				echo ShopFunctionsF::renderVmSubLayoutAsGrid(
					'searchcustomvalues',
					array (
						'searchcustomvalues' => $this->searchCustomValuesAr,
						'options' => array (
						'items_per_row' => array (
							'xs' => 2,
							'sm' => 2,
							'md' => 2,
							'lg' => 2,
							'xl' => 2,
						),
						),
					)
				);
				?>
				</div>
				<?php if(count($this->searchCustomValuesAr)>1){
				?>
				<div><?php echo vmText::_('COM_VM_COMBINETAGS');
				echo VmHtml::checkbox ('combineTags', $this->combineTags, 1, 0, '', 'combineTags'); ?></div>
				<?php }
				} ?>
				<div class="vm-search-custom-search-input input-group form-group">
					<input name="keyword" class="inputbox" type="text" size="40" value="<?php echo $this->keyword ?>"/>
					<span class="input-group-btn">
						<button type="submit" class="button btn-primary"><?php echo vmText::_ ('COM_VIRTUEMART_SEARCH') ?></button>
					</span>
					<?php //echo VmHtml::checkbox ('searchAllCats', (int)$this->searchAllCats, 1, 0, 'class="changeSendForm"'); ?>
				</div>
				<div class="vm-search-descr text-warning">
					<p><?php echo vmText::_('COM_VM_SEARCH_DESC') ?></p>
				</div>
				<!-- input type="hidden" name="showsearch" value="true"/ -->
				<input type="hidden" name="view" value="category"/>
				<input type="hidden" name="option" value="com_virtuemart"/>
				<input type="hidden" name="virtuemart_category_id" value="<?php echo $category_id; ?>"/>
				<input type="hidden" name="Itemid" value="<?php echo $this->Itemid; ?>"/>
			</form>
		</div>
		<!-- End Search Box -->
		<?php }

		$j = 'jQuery(document).ready(function() {

		jQuery(".changeSendForm")
		.off("change",Virtuemart.sendCurrForm)
		.on("change",Virtuemart.sendCurrForm);
		})';

		vmJsApi::addJScript('sendFormChange',$j);
		?>

		<?php if (!empty($this->products) && $this->showproducts) { ?>
		<div class="orderby-displaynumber">
			<div class="row">
				<div class="orderlistcontainer col-sm-4">
					<label><?php echo vmText::_('COM_VIRTUEMART_ORDERBY'); ?>
					<?php echo $this->orderByList['orderby']; ?>
					</label>
				</div>
				<div class="orderlistcontainer col-sm-4">
					<?php if (!empty($this->orderByList['manufacturer'])) :?>
					<label><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL'); ?>
					<?php echo $this->orderByList['manufacturer']; ?>
					</label>
					<?php endif; ?>
				</div>
				<div class="col-sm-4 orderlistcontainer display-number text-right">
					<label><?php echo $this->vmPagination->getResultsCounter ();?>
					<?php echo str_replace( 'class="inputbox"', 'class="inputbox col-xs-4 col-xs-offset-8"', $this->vmPagination->getLimitBox ($this->category->limit_list_step)); ?>
					</label>
				</div>
				<div class="col-xs-12 vm-pagination vm-pagination-top">
					<div class="row">
						<div class="col-xs-4 text-left small text-muted"><?php echo $this->vmPagination->getPagesCounter (); ?></div>
						<div class="col-xs-8"><?php echo str_replace('<ul>', '<ul class="pagination">', $this->vmPagination->getPagesLinks ()); ?></div>
					</div>
				</div>
			</div>
		</div> <!-- end of orderby-displaynumber -->
		<?php } ?>

		<?php	if (!empty($this->products))
		{
			//revert of the fallback in the view.html.php, will be removed vm3.2
			if($this->fallback){
			$p = $this->products;
			$this->products = array();
			$this->products[0] = $p;
			vmdebug('Refallback');
			}

			echo shopFunctionsF::renderVmSubLayout($this->productsLayout,array('products'=>$this->products,'currency'=>$this->currency,'products_per_row'=>$this->perRow,'showRating'=>$this->showRating));

			}
		?>

		<?php if (!empty($this->orderByList)) { ?>
		<div class="vm-pagination vm-pagination-bottom text-center row">
			<div class="vm-page-counter col-sm-3 small text-muted"><?php echo $this->vmPagination->getPagesCounter (); ?></div>
			<div class="col-sm-9 text-right">
			<?php echo $this->vmPagination->getPagesLinks (); ?>
			</div>
		</div>
		<?php } ?>

		<?php	if ($this->keyword !== false && empty($this->products)) { ?>
		<div class="alert alert-info">
		<?php echo vmText::_ ('COM_VIRTUEMART_NO_RESULT') . ($this->keyword ? ' : (' . $this->keyword . ')' : ''); ?>
		</div>
		<?php } ?>

	</div> <!-- browse view end -->
	<?php }
	echo vmJsApi::writeJS();
	?>
</div>
<!-- end browse-view -->