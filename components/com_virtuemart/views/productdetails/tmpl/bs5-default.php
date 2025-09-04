<?php

/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Eugen Stranz, Max Galt
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10982 2024-03-18 08:58:44Z  $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

// Load Bootstrap tooltip
\Joomla\CMS\HTML\HTMLHelper::_('bootstrap.tooltip');

/* Let's see if we found the product */
if (empty($this->product)) {
	echo vmText::_('COM_VIRTUEMART_PRODUCT_NOT_FOUND');
	echo '<br /><br />  ' . $this->continue_link_html;
	return;
}

echo shopFunctionsF::renderVmSubLayout('askrecomjs',array('product'=>$this->product));

// Get stock indicator
if (VmConfig::get('display_stock', 1)) {
	$productModel = VmModel::getModel('product');
	$displayStock = $productModel->getStockIndicator($this->product);
}

if (vRequest::getInt('print',false)) {
	vmJsApi::addJScript('vmPrint','jQuery(window).on(\'load\', function(){
		javascript:print();
		});
	');
}

// Back To Category Button
if ($this->product->virtuemart_category_id) {
	$catURL =  Route::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$this->product->virtuemart_category_id, FALSE);
	$categoryName = vmText::_($this->product->category_name);
} else {
	$catURL =  Route::_('index.php?option=com_virtuemart');
	$categoryName = vmText::_('COM_VIRTUEMART_SHOP_HOME') ;
}

// Product Packaging
$product_packaging = '';

// Edit product icon
$editIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square me-1" viewBox="0 0 16 16">
	<path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
	<path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
</svg>';
?>
<?php echo str_replace('</a>',$editIcon . vmText::_('COM_VIRTUEMART_PRODUCT_FORM_EDIT_PRODUCT') . '</a>', strip_tags($this->edit_link, '<a>')); // Product Edit Link ?>
<div class="product-container productdetails-view productdetails">
	<div class="row gy-4 mb-5">
		<div class="col-md-6 vm-product-media-container align-self-start position-sticky sticky-lg-top">
			<?php
				echo $this->loadTemplate('images');
				$count_images = count ($this->product->images);

				if ($count_images > 1) {
					echo $this->loadTemplate('images_additional');
				}
			?>
		</div>
		<div class="col-md-6 col-lg-5 offset-lg-1 vm-product-details-container position-relative">
			<?php if (VmConfig::get('product_navigation', 1)) : // Product Navigation ?>
				<div class="product-neighbours btn-group" role="group" aria-label="<?php echo vmText::_('Product Navigation'); ?>">
					<?php if (!empty($this->product->neighbours ['previous'][0])) : ?>
						<?php $prev_link = Route::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['previous'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE); ?>
						<a class="btn previous-page p-1" href="<?php echo $prev_link; ?>" rel="prev" data-dynamic-update="1" title="<?php echo $this->product->neighbours ['previous'][0]['product_name']; ?>" data-bs-toggle="tooltip">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
								<path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
							</svg>
						</a>
					<?php endif; ?>

					<?php if (!empty($this->product->neighbours ['next'][0])) : ?>
						<?php $next_link = Route::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['next'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE); ?>
						<a class="btn previous-page p-1" href="<?php echo $next_link; ?>" rel="prev" data-dynamic-update="1" title="<?php echo  $this->product->neighbours ['next'][0]['product_name']; ?>" data-bs-toggle="tooltip">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
								<path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
							</svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; // Product Navigation END ?>

			<h1 class="vm-page-title border-bottom pb-2 mb-2<?php echo VmConfig::get('product_navigation', 1) ? ' pe-5' : '';?>"><?php echo $this->product->product_name; ?></h1>

			<?php echo $this->product->event->afterDisplayTitle; // afterDisplayTitle Event  ?>

			<div class="row mb-3 mb-xl-5">
				<div class="col">
					<?php
					// Manufacturer of the Product
					if (VmConfig::get('show_manufacturers', 1) && !empty($this->product->virtuemart_manufacturer_id)) {
						echo $this->loadTemplate('manufacturer');
					}
					?>
				</div>
				<div class="col text-end">
					<?php echo shopFunctionsF::renderVmSubLayout('rating', array('showRating' => $this->showRating, 'product' => $this->product)); ?>
				</div>
			</div>

			<?php echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'normal')); ?>

			<?php if ($this->product->product_box) : ?>
				<div class="product-box pb-2 border-bottom mb-4">
					<span class="text-secondary pe-1"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_UNITS_IN_BOX'); ?></span> <?php echo $this->product->product_box; ?>
				</div>
			<?php endif; ?>

			<?php if (!empty($this->product->product_s_desc)) : ?>
				<div class="product-short-description mb-4 text-secondary">
					<?php echo nl2br($this->product->product_s_desc); ?>
				</div>
			<?php endif; ?>

			<div class="row">
				<div class="col-7">
					<?php echo shopFunctionsF::renderVmSubLayout('prices',array('product'=>$this->product,'currency'=>$this->currency)); ?>
				</div>
				<div class="col-5 d-flex justify-content-end">
					<?php if (VmConfig::get('display_stock', 1)) : ?>
						<?php
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
			</div>

			<?php echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'ontop')); ?>
			<?php echo shopFunctionsF::renderVmSubLayout('stockhandle',array('product'=>$this->product)); ?>
			<?php echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$this->product)); ?>

			<div class="vm-modal-buttons my-4 pt-2 border-top">
				<div class="row">
					<?php if (VmConfig::get('ask_question', 0) == 1) : // Ask a question about this product ?>
						<?php
						$askquestion_url = Route::_('index.php?option=com_virtuemart&view=productdetails&task=askquestion&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component', FALSE);
						$askquestion_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question" viewBox="0 0 16 16">
												<path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94"/>
											</svg>';
						?>
						<div class="ask-a-question col-auto">
							<a class="ask-a-question" href="<?php echo $askquestion_url ?>" rel="nofollow" ><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_ENQUIRY_LBL') . $askquestion_icon; ?></a>
						</div>
					<?php endif; ?>
					<?php if (VmConfig::get('show_emailfriend') || VmConfig::get('show_printicon') || VmConfig::get('pdf_icon')) : // PDF - Print - Email Icon ?>
						<div class="icons col text-end">
							<?php
								$pdfIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-pdf" viewBox="0 0 16 16">
												<path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM1.6 11.85H0v3.999h.791v-1.342h.803q.43 0 .732-.173.305-.175.463-.474a1.4 1.4 0 0 0 .161-.677q0-.375-.158-.677a1.2 1.2 0 0 0-.46-.477q-.3-.18-.732-.179m.545 1.333a.8.8 0 0 1-.085.38.57.57 0 0 1-.238.241.8.8 0 0 1-.375.082H.788V12.48h.66q.327 0 .512.181.185.183.185.522m1.217-1.333v3.999h1.46q.602 0 .998-.237a1.45 1.45 0 0 0 .595-.689q.196-.45.196-1.084 0-.63-.196-1.075a1.43 1.43 0 0 0-.589-.68q-.396-.234-1.005-.234zm.791.645h.563q.371 0 .609.152a.9.9 0 0 1 .354.454q.118.302.118.753a2.3 2.3 0 0 1-.068.592 1.1 1.1 0 0 1-.196.422.8.8 0 0 1-.334.252 1.3 1.3 0 0 1-.483.082h-.563zm3.743 1.763v1.591h-.79V11.85h2.548v.653H7.896v1.117h1.606v.638z"/>
											</svg>';
								$printIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
													<path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
													<path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
												</svg>';
								$emailIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-at" viewBox="0 0 16 16">
												<path d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2zm3.708 6.208L1 11.105V5.383zM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2z"/>
												<path d="M14.247 14.269c1.01 0 1.587-.857 1.587-2.025v-.21C15.834 10.43 14.64 9 12.52 9h-.035C10.42 9 9 10.36 9 12.432v.214C9 14.82 10.438 16 12.358 16h.044c.594 0 1.018-.074 1.237-.175v-.73c-.245.11-.673.18-1.18.18h-.044c-1.334 0-2.571-.788-2.571-2.655v-.157c0-1.657 1.058-2.724 2.64-2.724h.04c1.535 0 2.484 1.05 2.484 2.326v.118c0 .975-.324 1.39-.639 1.39-.232 0-.41-.148-.41-.42v-2.19h-.906v.569h-.03c-.084-.298-.368-.63-.954-.63-.778 0-1.259.555-1.259 1.4v.528c0 .892.49 1.434 1.26 1.434.471 0 .896-.227 1.014-.643h.043c.118.42.617.648 1.12.648m-2.453-1.588v-.227c0-.546.227-.791.573-.791.297 0 .572.192.572.708v.367c0 .573-.253.744-.564.744-.354 0-.581-.215-.581-.8Z"/>
											</svg>';
								$link = 'index.php?tmpl=component&option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->virtuemart_product_id;
								$MailLink = 'index.php?option=com_virtuemart&view=productdetails&task=recommend&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component';
							?>
							<?php if (VmConfig::get('pdf_icon')) : ?>
								<a class="pdf-btn" href="<?php echo $link . '&format=pdf';?>" title="<?php echo vmText::_('COM_VIRTUEMART_PDF'); ?>" target="_blank" data-bs-toggle="tooltip"><?php echo $pdfIcon; ?></a>
							<?php endif; ?>
							<?php if (VmConfig::get('show_printicon')) : ?>
								<a class="printModal ms-1" href="<?php echo $link . '&print=1'?>" title="<?php echo vmText::_('COM_VIRTUEMART_PRINT'); ?>" data-bs-toggle="tooltip"><?php echo $printIcon; ?></a>
							<?php endif; ?>
							<?php if (VmConfig::get('show_emailfriend')) : ?>
								<a class="recommened-to-friend ms-1" href="<?php echo $MailLink; ?>" title="<?php echo vmText::_('COM_VIRTUEMART_EMAIL'); ?>" data-bs-toggle="tooltip"><?php echo $emailIcon; ?></a>
							<?php endif; ?>
						</div>
					<?php endif; // PDF - Print - Email Icon END ?>
				</div>
			</div>

			<?php if (!empty($this->productDisplayTypes)) : ?>
				<div class="vm-shipping-info text-center small bg-light border-top border-bottom p-3 mt-5">
					<?php
						foreach ($this->productDisplayTypes as $type=>$productDisplayType) {
							foreach ($productDisplayType as $productDisplay) {
								foreach ($productDisplay as $virtuemart_method_id =>$productDisplayHtml) {
									?>
									<div class="<?php echo substr($type, 0, -1) ?> <?php echo substr($type, 0, -1).'-'.$virtuemart_method_id ?>">
										<?php echo $productDisplayHtml; ?>
									</div>
									<?php
								}
							}
						}
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php echo $this->product->event->beforeDisplayContent; // event onContentBeforeDisplay ?>

	<?php if (!empty($this->product->product_desc)) : // Product Description	?>
		<div class="product-description mb-5">
			<h2 class="vm-section-title pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_DESC_TITLE') ?></h2>
			<?php echo $this->product->product_desc; ?>
		</div>
	<?php endif; // Product Description END ?>

	<?php echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'onbot')); ?>

	<?php // onContentAfterDisplay event
	echo $this->product->event->afterDisplayContent;

	echo $this->loadTemplate('reviews');

	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_products','class'=> 'product-related-products','customTitle' => true ));
	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_categories','class'=> 'product-related-categories'));

	// Show child categories
	if ($this->cat_productdetails)  {
		echo $this->loadTemplate('showcategory');
	}
	?>

	<div class="back-to-category">
		<a href="<?php echo $catURL ?>" class="btn btn-sm btn-link">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
				<path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5"/>
			</svg>
			<?php echo vmText::sprintf('COM_VIRTUEMART_CATEGORY_BACK_TO',$categoryName) ?>
		</a>
	</div>

	<?php
		if(VmConfig::get ('jdynupdate', TRUE)) {

			/** GALT
			 * Notice for Template Developers!
			 * Templates must set a Virtuemart.container variable as it takes part in
			 * dynamic content update.
			 * This variable points to a topmost element that holds other content.
			 */
		/*	$j = "Virtuemart.container = jQuery('.productdetails-view');
		Virtuemart.containerSelector = '.productdetails-view';
		//Virtuemart.recalculate = true;	//Activate this line to recalculate your product after ajax
		";

			vmJsApi::addJScript('ajaxContent',$j);*/

			$j = "jQuery(document).ready(function($) {
			Virtuemart.stopVmLoading();
			var msg = '';
				$('a[data-dynamic-update=\"1\"]').off('click', Virtuemart.startVmLoading).on('click', {msg:msg}, Virtuemart.startVmLoading);
				$('[data-dynamic-update=\"1\"]').off('change', Virtuemart.startVmLoading).on('change', {msg:msg}, Virtuemart.startVmLoading);
			});";

			vmJsApi::addJScript('vmPreloader',$j);
		}

		// We need to remove any previously created tooltips when the page is updated via Ajax.
		vmJsApi::addJScript('vmHideTooltips','jQuery(document).ready(function($) {
			 $(\'body\').find(\'.tooltip\').remove();
		});');

		echo vmJsApi::writeJS();

		if ($this->product->prices['salesPrice'] > 0) {
			echo shopFunctionsF::renderVmSubLayout('snippets',array('product'=>$this->product, 'currency'=>$this->currency, 'showRating'=>$this->showRating));
		}
	?>
</div>