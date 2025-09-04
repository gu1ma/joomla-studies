<?php

$product = $viewData['product'];

$squareFill = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-square-fill" viewBox="0 0 16 16">
<path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2z"/>
</svg>';

$square = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-square" viewBox="0 0 16 16">
<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
</svg>';

?>

<?php if (VmConfig::get('display_stock', 1)) : ?>
	<div class="ms-auto vm-stock-status <?php echo $product->stock->stock_level; ?>" title="<?php echo $product->stock->stock_tip; ?>" data-bs-toggle="tooltip" data-bs-placement="top">
	<?php
		switch ($product->stock->stock_level) {
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