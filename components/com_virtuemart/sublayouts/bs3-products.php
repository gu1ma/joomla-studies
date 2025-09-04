<?php
/**
 * sublayout products
 *
 * @package	VirtueMart
 * @author Max Milbers
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL2, see LICENSE.php
 * @version $Id: cart.php 7682 2014-02-26 17:07:20Z Milbo $
 */

defined('_JEXEC') or die('Restricted access');
JHtml::_('bootstrap.tooltip');

/** @var TYPE_NAME $viewData */
$products_per_row = empty($viewData['products_per_row'])? 1:$viewData['products_per_row'] ;
$currency = $viewData['currency'];
$showRating = $viewData['showRating'];
$verticalseparator = " vertical-separator";
echo shopFunctionsF::renderVmSubLayout('askrecomjs');

$dynamic = false;
if (vRequest::getInt('dynamic',false) and vRequest::getInt('virtuemart_product_id',false)) {
	$dynamic = true;
}

foreach ($viewData['products'] as $type => $products ) {

	$col = 1;
	$nb = 1;
	$row = 1;

	if($dynamic){
		$rowsHeight[$row]['product_s_desc'] = 1;
		$rowsHeight[$row]['price'] = 1;
		$rowsHeight[$row]['customfields'] = 1;
		$col = 2;
		$nb = 2;
	} else {

	$rowsHeight = shopFunctionsF::calculateProductRowsHeights($products,$currency,$products_per_row);

	if( (!empty($type) and count($products)>0) or (count($viewData['products'])>1 and count($products)>0)){
		$productTitle = vmText::_('COM_VIRTUEMART_'.strtoupper($type).'_PRODUCT'); ?>
	<div class="<?php echo $type ?>-view">
		<h3 class="page-header"><?php echo $productTitle ?></h3>
			<?php // Start the Output
		}
	}

	// Calculating Products Per Row
	$cellwidth = ' col-xs-12 col-md-'. floor ( 12 / $products_per_row ) . ' col-sm-'. floor ( 12 / $products_per_row ) . ' span' . floor ( 12 / $products_per_row );

	$BrowseTotalProducts = count($products);
	?>
		<div class="row flex">
		<?php
		foreach ( $products as $product ) {
			if(!is_object($product) or empty($product->link)) {
				vmdebug('$product is not object or link empty',$product);
				continue;
			}

			// Show Products ?>
			<div class="product vm-col<?php echo ' vm-col-' . $products_per_row . ' ' . $cellwidth ;?>" id="vm-prod-<?php echo $product->virtuemart_product_id;?>">
				<div class="thumbnail product-container" data-vm="product-container">
					<?php if ( VmConfig::get ('display_stock', 1) ||  VmConfig::get ('showRatingFor', 1) != 'none' ) { ?>
					<div class="vm-product-rating-container">
						<div class="row">
							<?php echo shopFunctionsF::renderVmSubLayout('rating',array('showRating'=>$showRating, 'product'=>$product)); ?>
							<?php if ( VmConfig::get ('display_stock', 1)) { ?>
							<div class="text-right col-md-4 pull-right">
								<span class="vmicon vm2-<?php echo $product->stock->stock_level ?> glyphicon glyphicon-signal hasTooltip" title="<?php echo $product->stock->stock_tip ?>"></span>
							</div>
							<?php } ?>
						</div>
					</div>
					<?php } ?>
					<div class="vm-product-media-container" data-mh="media-container">
						<a title="<?php echo $product->product_name ?>" href="<?php echo JRoute::_($product->link); ?>">
						<?php echo $product->images[0]->displayMediaThumb('class="browseProductImage"', false); ?>
						</a>
					</div>
					<h2 class="h5 vm-product-title text-center product-name"><?php echo JHtml::link ($product->link, $product->product_name); ?></h2>
					<?php if (!empty($product->product_s_desc)) { // Product Short Description ?>
					<div class="product_s_desc text-muted small" data-mh="sdesc-<?php echo $type ?>">
					<?php echo shopFunctionsF::limitStringByWord ($product->product_s_desc, 60, ' ...') ?>
					</div>
					<?php } ?>
					<?php echo shopFunctionsF::renderVmSubLayout('prices',array('product'=>$product,'currency'=>$currency)); ?>
					<?php if ( VmConfig::get('show_pcustoms',1) ) { ?>
					<?php echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$product,'rowHeights'=>$rowsHeight[$row], 'position' => array('ontop', 'addtocart'))); ?>
					<?php } else { ?>
					<div class="vm-details-button">
					<?php // Product Details Button
					$link = empty($product->link)? $product->canonical:$product->link;
					echo JHtml::link($link,vmText::_ ( 'COM_VIRTUEMART_PRODUCT_DETAILS' ), array ('title' => $product->product_name, 'class' => 'product-details btn btn-default btn-block margin-top-15' ) );
					?>
					</div>
					<?php } ?>
					<?php if ($dynamic) {
					echo vmJsApi::writeJS();
					} ?>
				</div>
			</div>
			<?php } ?>
		</div>
	<?php
	if( (!empty($type) and count($products)>0) or (count($viewData['products'])>1 and count($products)>0) ) { ?>
	</div>
	<?php
	}
}