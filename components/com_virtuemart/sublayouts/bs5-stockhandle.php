<?php

defined ('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$app   = Factory::getApplication();
$input = $app->getInput();
$view  = $input->getCmd('view', '');

/** @var TYPE_NAME $viewData */
$product = $viewData['product'];
// Availability
$stockhandle = VmConfig::get('stockhandle_products', false) && $product->product_stockhandle ? $product->product_stockhandle : VmConfig::get('stockhandle','none');
$product_available_date = $product->product_available_date != null ? substr($product->product_available_date,0,10) : '0000-00-00';
$current_date = date("Y-m-d");
?>

<?php if (($product->product_in_stock - $product->product_ordered) < 1) : ?>
	<?php if ($product_available_date != '0000-00-00' and $current_date < $product_available_date) : ?>
		<div class="availability d-flex align-items-center justify-content-between px-3 py-2 mb-3">
			<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_AVAILABLE_DATE') .': '. HTMLHelper::_('date', $product->product_available_date, vmText::_('DATE_FORMAT_LC4')); ?>
		</div>
	<?php elseif ($stockhandle == 'risetime' and VmConfig::get('rised_availability') and empty($product->product_availability)) : ?>
		<div class="availability p-3">
			<?php echo (file_exists(JPATH_BASE . '/' . VmConfig::get('assets_general_path') . 'images/availability/' . VmConfig::get('rised_availability'))) ? HTMLHelper::image(Uri::root() . VmConfig::get('assets_general_path') . 'images/availability/' . VmConfig::get('rised_availability', '7d.gif'), VmConfig::get('rised_availability', '7d.gif'), array('class' => 'availability')) : vmText::_(VmConfig::get('rised_availability')); ?>
		</div>
	<?php elseif (!empty($product->product_availability)) : ?>
		<div class="availability d-flex align-items-center justify-content-between px-3 py-2 mb-3<?php echo $view == 'category' ? ' flex-column' : ''; ?>">
			<div class="<?php echo $view == 'category' ? 'mb-1' : ''; ?>">
				<span class="ms-1"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_AVAILABILITY'); ?> :</span>
			</div>
			<?php if (file_exists(JPATH_BASE . '/templates/' . $app->getTemplate() . '/images/availability/' . $product->product_availability)) : ?>
				<?php echo HTMLHelper::image(Uri::root() . '/templates/' . $app->getTemplate()  . '/images/availability/' . $product->product_availability, $product->product_availability, array('class' => 'availability-img img-fluid')); ?>
			<?php elseif (file_exists(JPATH_BASE . '/' . VmConfig::get('assets_general_path') . 'images/availability/' . $product->product_availability)) : ?>
				<?php echo HTMLHelper::image(Uri::root() . VmConfig::get('assets_general_path') . 'images/availability/' . $product->product_availability, $product->product_availability, array('class' => 'availability-img img-fluid')); ?>
			<?php else : ?>
				<span class="fw-bold"><?php echo vmText::_($product->product_availability); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
<?php elseif ($product_available_date != '0000-00-00' and $current_date < $product_available_date) : ?>
	<div class="availability d-flex align-items-center justify-content-between px-3 py-2 mb-3">
		<span class="fw-bold"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_AVAILABLE_DATE') .': '. HTMLHelper::_('date', $product->product_available_date, vmText::_('DATE_FORMAT_LC4')); ?></span>
	</div>
<?php endif; ?>