<?php

/**
 * sublayout products
 *
 * @package	VirtueMart
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL2, see LICENSE.php
 * @version $Id: cart.php 7682 2014-02-26 17:07:20Z Milbo $
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

// Load Bootstrap tooltip
\Joomla\CMS\HTML\HTMLHelper::_('bootstrap.tooltip');

/** @var TYPE_NAME $viewData */
$products_per_row = empty($viewData['products_per_row'])? 1:$viewData['products_per_row'] ;
$currency = $viewData['currency'];
$showRating = $viewData['showRating'];

echo shopFunctionsF::renderVmSubLayout('askrecomjs');

$ItemidStr = '';
$Itemid = shopFunctionsF::getLastVisitedItemId();

if (!empty($Itemid)) {
	$ItemidStr = '&Itemid='.$Itemid;
}

$dynamic = false;

if (vRequest::getInt('dynamic',false) and vRequest::getInt('virtuemart_product_id',false)) {
	$dynamic = true;
}
?>

<?php
foreach ($viewData['products'] as $type => $products ) {

	$col = 1;
	$nb = 1;
	$row = 1;

	if ($dynamic) {
		$rowsHeight[$row]['product_s_desc'] = 1;
		$rowsHeight[$row]['price'] = 1;
		$rowsHeight[$row]['customfields'] = 1;
		$col = 2;
		$nb = 2;
	} else {
		$rowsHeight = shopFunctionsF::calculateProductRowsHeights($products,$currency,$products_per_row);

		if ((!empty($type) and count($products)>0) or (count($viewData['products'])>1 and count($products)>0)) {
			$productTitle = vmText::_('COM_VIRTUEMART_'.strtoupper($type).'_PRODUCT');
		?>
		<div class="<?php echo $type ?>-view">
			<h2 class="vm-products-type-title"><?php echo $productTitle ?></h2>
	<?php
		}
	}

	$BrowseTotalProducts = count($products);
	?>
		<div class="vm-product-grid container my-5">
			<div class="row gy-4">
				<?php foreach ( $products as $product ) : ?>
					<?php
						if (!is_object($product) or empty($product->link)) {
							vmdebug('$product is not object or link empty',$product);
							continue;
						}
					?>
					<div class="product col-md-6 col-lg-12 w-desc-<?php echo $rowsHeight[$row]['product_s_desc']; ?> pb-4 border-bottom">
						<div class="product-container row align-items-center" data-vm="product-container">
							<div class="vm-product-media-container col-lg-3 text-center d-flex flex-column justify-content-center"<?php echo VmConfig::get('img_height', 0) ? ' style="min-height:' . VmConfig::get('img_height', 0) . 'px"' : ''?>>
								<a title="<?php echo $product->product_name ?>" href="<?php echo Route::_($product->link.$ItemidStr); ?>">
									<?php echo $product->images[0]->displayMediaThumb('class="browseProductImage img-fluid"', false); ?>
								</a>
							</div>

							<div class="col-lg-5">
	 							<div class="vm-product-rating-container d-flex justify-content-between pb-2">
									<?php echo shopFunctionsF::renderVmSubLayout('rating',array('showRating'=>$showRating, 'product'=>$product)); ?>
									<?php echo shopFunctionsF::renderVmSubLayout('displaystock',array('product'=>$product)); ?>
								</div>

								<h2 class="vm-product-title pb-3 my-3 border-bottom"><?php echo HTMLHelper::link ($product->link.$ItemidStr, $product->product_name); ?></h2>

								<p class="vm-product-s-desc text-secondary">
									<?php echo shopFunctionsF::limitStringByWord ($product->product_s_desc, 60, ' ...') ?>
								</p>
							</div>

							<div class="col-lg-4">
								<?php echo shopFunctionsF::renderVmSubLayout('prices',array('product'=>$product,'currency'=>$currency)); ?>
								<?php echo shopFunctionsF::renderVmSubLayout('stockhandle',array('product'=>$product)); ?>

								<?php if (VmConfig::get('show_pcustoms', 1) && VmConfig::get('show_prices', 1)) : ?>
									<?php echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$product, 'position' => array('ontop', 'addtocart'))); ?>
								<?php else : ?>
									<?php $link = empty($product->link)? $product->canonical:$product->link; ?>
									<a class="btn btn-secondary w-100 mt-3" href="<?php echo $link; ?>"><?php echo vmText::_ ( 'COM_VIRTUEMART_PRODUCT_DETAILS' ); ?></a>
								<?php endif; ?>
							</div>
							<?php
							if ($dynamic) {
								echo vmJsApi::writeJS();
							}
							?>
						</div>
					</div>
					<?php
						$nb ++;

						if ($col == $products_per_row || $nb>$BrowseTotalProducts) {
							$col = 1;
							$row++;
						} else {
							$col ++;
						}
					?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php if ((!empty($type) and count($products)>0) or (count($viewData['products'])>1 and count($products)>0)) : ?>
		</div>
	<?php endif; ?>
<?php
}
?>