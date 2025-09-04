<?php

/**
 *
 * Show the product details page
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers
 * @todo handle child products
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2015 - 2021 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default_addtocart.php 7833 2014-04-09 15:04:59Z Milbo $
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

/** @var TYPE_NAME $viewData */
$product = $viewData['product'];

if (isset($viewData['rowHeights']))
{
	$rowHeights = $viewData['rowHeights'];
}
else
{
	$rowHeights['customfields'] = TRUE;
}

$init = 1;

if (isset($viewData['init']))
{
	$init = $viewData['init'];
}

if (!empty($product->min_order_level) and $init<$product->min_order_level)
{
	$init = $product->min_order_level;
}

$step=1;

if (!empty($product->step_order_level))
{
	$step=$product->step_order_level;
	if (!empty($init))
	{
		if ($init<$step)
		{
			$init = $step;
		}
		else if ($init>$step)
		{
			$init = ceil($init/$step) * $step;
		}
	}
	if (empty($product->min_order_level) and !isset($viewData['init']))
	{
		$init = $step;
	}
}

$maxOrder= '';

if (!empty($product->max_order_level))
{
	$maxOrder = ' max="'.$product->max_order_level.'" ';
}

$addtoCartButton = '';

if (!VmConfig::get('use_as_catalog', 0))
{
	if (!$product->addToCartButton and $product->addToCartButton!=='')
	{
		$addtoCartButton = self::renderVmSubLayout('addtocartbtn',array('orderable'=>$product->orderable)); //shopFunctionsF::getAddToCartButton ($product->orderable);
	}
	else
	{
		$addtoCartButton = $product->addToCartButton;
	}
}

$position = 'addtocart';

if ($product->min_order_level > 0) {
	$minOrderLevel = $product->min_order_level;
}
else
{
	$minOrderLevel = 1;
}


if (!VmConfig::get('use_as_catalog', 0))
{
?>
	<div class="addtocart-bar mt-auto">
		<?php $stockhandle = VmConfig::get('stockhandle_products', false) && $product->product_stockhandle ? $product->product_stockhandle : VmConfig::get('stockhandle','none'); ?>
		<?php if ($product->show_notify) : ?>
			<a class="btn btn-primary notify w-100" href="<?php echo Route::_ ('index.php?option=com_virtuemart&view=productdetails&layout=notify&virtuemart_product_id=' . $product->virtuemart_product_id); ?>" ><?php echo vmText::_ ('COM_VIRTUEMART_CART_NOTIFY') ?></a>
		<?php else : ?>
			<?php
			$tmpPrice = (float) $product->prices['costPrice'];
			if (!(VmConfig::get('askprice', true) and empty($tmpPrice)))
			{
				$editable = 'hidden';
				if ($product->orderable)
				{
					$editable = 'text';
				}
			?>

				<div class="row gx-0">
					<?php if ($product->orderable) : ?>
						<div class="col-auto">
							<label class="quantity_box visually-hidden" for="quantity<?php echo $product->virtuemart_product_id; ?>"><?php echo vmText::_ ('COM_VIRTUEMART_CART_QUANTITY'); ?>:</label>

							<div class="quantity-box input-group flex-nowrap">
								<button class="quantity-controls quantity-minus btn btn-link px-1 col" type="button">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16">
										<path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"/>
									</svg>
								</button>
								<input class="quantity-input js-recalculate form-control text-center px-1"
									id="quantity<?php echo $product->virtuemart_product_id; ?>"
									type="<?php echo $editable ?>"
									name="quantity[]"
									data-errStr="<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED')?>"
									value="<?php echo $init; ?>"
									data-init="<?php echo $init; ?>"
									data-step="<?php echo $step; ?>"
									<?php echo $maxOrder; ?>
									/>
								<button class="quantity-controls quantity-plus btn btn-link px-1 col" type="button">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
										<path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
									</svg>
								</button>
							</div>

							<div class="quantity-controls js-recalculate"></div>
						</div>
					<?php endif; ?>
					<div class="vm-addtocart-button-col<?php echo $product->orderable ? ' col' : ' col-12'; ?>">
						<?php if (!empty($addtoCartButton)) : ?>
							<div class="vm-addtocart-button-wrapper">
								<?php echo $addtoCartButton ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<input type="hidden" name="virtuemart_product_id[]" value="<?php echo $product->virtuemart_product_id ?>"/>

				<?php if (!VmConfig::get('addtocart_popup',true)) : ?>
					<input type="hidden" name="task" value="add"/>
				<?php else : ?>
					<noscript><input type="hidden" name="task" value="add"/></noscript>
				<?php endif; ?>
			<?php
			}
			?>
		<?php endif; ?>
	</div>
<?php
}
?>