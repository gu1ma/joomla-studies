<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

/** @var TYPE_NAME $viewData */
$related = $viewData['related'];
$customfield = $viewData['customfield'];
$thumb = $viewData['thumb'];

$ratingModel = VmModel::getModel('ratings');
$showRating = $ratingModel->showRating();

if (VmConfig::get('display_stock', 1)) {
	$productModel = VmModel::getModel('product');
}

$emptyStar = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
<path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"/>
</svg>';

$star = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
</svg>';
?>

<div class="vm-related-product-container d-flex flex-column h-100">
	<div class="vm-product-media-container"<?php echo VmConfig::get('img_height', 0) ? ' style="min-height:' . VmConfig::get('img_height', 0) . 'px"' : ''?>>
		<a href="<?php echo Route::_ ($related->link); ?>"><?php echo $thumb; ?></a>
	</div>

	<div class="vm-product-rating-container d-flex justify-content-between pb-2 my-3 border-bottom">
		<?php echo shopFunctionsF::renderVmSubLayout('rating', array('showRating' => $showRating, 'product' => $related)); ?>
		<?php if (VmConfig::get('display_stock', 1)) : ?>
			<?php
				$displayStock = $productModel->getStockIndicator($related);

				$squareFill = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-square-fill" viewBox="0 0 16 16">
				<path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2z"/>
				</svg>';

				$square = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-square" viewBox="0 0 16 16">
				<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
				</svg>';
			?>
			<div class="ms-auto vm-stock-status <?php echo $displayStock->stock_level; ?>" title="<?php echo $displayStock->stock_tip; ?>" data-bs-toggle="tooltip" data-bs-placement="top">
				<?php
					switch ($displayStock->stock_level) {
						case 'nostock':
							$displayStock = $squareFill . $square . $square;
							break;
						case 'lowstock':
							$displayStock = $squareFill . $squareFill . $square;
							break;
						case 'normalstock':
							$displayStock = $squareFill . $squareFill . $squareFill;
							break;
						default:
							$displayStock = $square . $square . $square;
					}

					echo $displayStock;
				?>
			</div>
		<?php endif; ?>
	</div>

	<h3 class="vm-product-title text-center pb-2 mb-0">
		<a href="<?php echo Route::_ ($related->link); ?>"><?php echo $related->product_name; ?></a>
	</h3>

	<?php if ($customfield->wDescr) : ?>
		<p class="product_s_desc text-center"><?php echo $related->product_s_desc ?></p>
	<?php endif; ?>

	<?php if ($customfield->wPrice) : ?>
		<?php $currency = calculationHelper::getInstance()->_currencyDisplay; ?>
		<div class="product-price vm-simple-price-display text-center d-flex justify-content-center align-items-center mt-auto<?php echo $related->prices['discountAmount'] ? ' vm-has-discount' : ''?>" id="productPrice<?php echo $related->virtuemart_product_id ?>">
			<?php
				if (!empty($related->prices['salesPrice'])) {
					echo $currency->createPriceDiv ('salesPrice', '', $related->prices, FALSE, FALSE, 1.0, TRUE);
				}

				if ($related->prices['discountAmount']) {
					echo $currency->createPriceDiv ('basePriceWithTax', '', $related->prices, FALSE, FALSE, 1.0, TRUE);
				}
			?>
		</div>
	<?php endif; ?>

	<?php if ($customfield->waddtocart) : ?>
		<?php echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$related, 'position' => array('ontop', 'addtocart'))); ?>
	<?php else : ?>
		<a class="btn btn-secondary w-100 mt-3" href="<?php echo Route::_ ($related->link); ?>"><?php echo vmText::_ ( 'COM_VIRTUEMART_PRODUCT_DETAILS' ); ?></a>
	<?php endif; ?>
</div>