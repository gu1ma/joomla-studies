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

use Joomla\CMS\HTML\HTMLHelper;

/** @var TYPE_NAME $viewData */
$product = $viewData['product'];
$position = $viewData['position'];
$customTitle = isset($viewData['customTitle'])? $viewData['customTitle']: false;

if(isset($viewData['class'])){
	$class = $viewData['class'];
} else {
	$class = 'product-fields';
}

$tooltipIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
<path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
</svg>';
?>

<?php if (!empty($product->customfieldsSorted[$position])) : ?>
	<?php if ($position == 'normal') : ?>
		<ul class="cf-position-normal list-unstyled p-0 mb-4">
			<?php foreach ($product->customfieldsSorted[$position] as $field) : ?>
			<?php if ( $field->is_hidden || empty($field->display)) continue; //OSP http://forum.virtuemart.net/index.php?topic=99320.0 ?>
			<li class="d-flex flex-wrap align-items-center pb-2 mb-2 border-bottom ">
				<?php $custom_title = null; ?>
				<?php if (!$customTitle and $field->custom_title != $custom_title and $field->show_title) : ?>
					<div class="product-fields-title text-secondary pe-1">
						<?php echo vmText::_ ($field->custom_title) ?> :
					</div>
				<?php endif; ?>

				<?php if (!empty($field->display)) : ?>
					<div class="product-field-display pe-1"><?php echo $field->display ?></div>
				<?php endif; ?>

				<?php if ($field->custom_tip) : ?>
					<span class="ms-auto" title="<?php echo vmText::_($field->custom_tip); ?>" data-bs-toggle="tooltip">
						<?php echo $tooltipIcon; ?>
					</span>
				<?php endif; ?>

				<?php if (!empty($field->custom_desc)) : ?>
					<div class="product-field-desc w-100 small bg-light py-1 px-2 mt-2"><?php echo vmText::_($field->custom_desc) ?></div>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<div class="<?php echo $class; ?>">
			<?php if ($class == 'product-related-products') : ?>
				<h2 class="vm-section-title pb-2 mb-3 border-bottom"><?php echo vmText::_ ('COM_VIRTUEMART_RELATED_PRODUCTS') ?></h2>
				<div class="row gy-4 g-xl-4 mb-5">
				<?php foreach ($product->customfieldsSorted[$position] as $field) : ?>
					<?php if (!empty($field->display)) : ?>
						<div class="col col-md-2 col-xl-3"><?php echo $field->display ?></div>
					<?php endif; ?>
				<?php endforeach; ?>
				</div>
			<?php elseif ($class == 'product-related-categories') : ?>
				<h2 class="vm-section-title pb-2 mb-3 border-bottom"><?php echo vmText::_ ('COM_VIRTUEMART_RELATED_CATEGORIES') ?></h2>
				<div class="row gy-4 g-xl-4 mb-5">
				<?php foreach ($product->customfieldsSorted[$position] as $field) : ?>
					<?php if (!empty($field->display)) : ?>
						<div class="col col-md-2 col-xl-3 text-center"><?php echo $field->display ?></div>
					<?php endif; ?>
				<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php if ($customTitle and isset($product->customfieldsSorted[$position][0])) : ?>
					<?php $field = $product->customfieldsSorted[$position][0]; ?>
					<div class="product-fields-title-wrapper">
						<div class="product-fields-title">
							<span class="fw-semibold"><?php echo vmText::_ ($field->custom_title) ?></span>
						</div>
						<?php if ($field->custom_tip) {
							echo HTMLHelper::tooltip (vmText::_($field->custom_tip), vmText::_ ($field->custom_title), $tooltipIcon);
						} ?>
					</div>
				<?php endif; ?>

				<?php $custom_title = null; ?>
				<?php foreach ($product->customfieldsSorted[$position] as $field) : ?>
					<?php if ( $field->is_hidden || empty($field->display)) continue; //OSP http://forum.virtuemart.net/index.php?topic=99320.0 ?>
						<div class="product-field product-field-type-<?php echo $field->field_type ?> mb-2">
							<?php if (!$customTitle and $field->custom_title != $custom_title and $field->show_title) : ?>
								<div class="product-fields-title-wrapper">
									<div class="product-fields-title mb-2">
										<span class="fw-semibold"><?php echo vmText::_ ($field->custom_title) ?></span>
										<?php if ($field->custom_tip) : ?>
											<span class="ms-1" title="<?php echo vmText::_($field->custom_tip); ?>" data-bs-toggle="tooltip">
												<?php echo $tooltipIcon; ?>
											</span>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>

							<?php if (!empty($field->display)) : ?>
								<div class="product-field-display mb-1"><?php echo $field->display ?></div>
							<?php endif; ?>

							<?php if (!empty($field->custom_desc)) : ?>
								<div class="product-field-desc px-2 py-1 small bg-light"><?php echo vmText::_($field->custom_desc) ?></div>
							<?php endif; ?>
						</div>
					<?php $custom_title = $field->custom_title; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>