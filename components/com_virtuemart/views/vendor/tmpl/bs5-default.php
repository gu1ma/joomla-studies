<?php

/**
*
* Description
*
* @package	VirtueMart
* @subpackage vendor
* @author Kohl Patrick, Eugen Stranz
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: default.php 2701 2011-02-11 15:16:49Z impleri $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;

// Calculating Vendors Per Row
$vendorPerRow = 4;
$bscol = ' col-lg-'.floor ( 12 / $vendorPerRow );
?>

<h1 class="vm-page-title mb-4 text-center"><?php echo $this->document->title; ?></h1>

<?php if (!empty($this->vendors)) : ?>
	<div class="vendor-view-default row align-items-end gx-xl-5 gy-3">
		<?php foreach ($this->vendors as $vendor) : ?>
			<?php
				// vendor Elements
				$vendorsLink = Route::_('index.php?option=com_virtuemart&view=vendor&virtuemart_vendor_id=' . $vendor->virtuemart_vendor_id, FALSE);
				$vendorIncludedProductsURL = Route::_('index.php?option=com_virtuemart&view=category&virtuemart_vendor_id=' . $vendor->virtuemart_vendor_id, FALSE);
				$vendorImage = $vendor->images[0]->displayMediaThumb('class="img-fluid"', false, '', true, false, false, 288, 0);
			?>

			<div class="vendor col-6 col-md-4<?php echo $bscol; ?> text-center">
				<a href="<?php echo $vendorsLink; ?>">
					<div class="vendor-img mb-3"><?php echo $vendorImage; ?></div>
					<h2 class="vm-subcategory-title fw-normal pt-2 border-top">
						<?php echo $vendor->vendor_name; ?>
					</h2>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<?php echo 'Serious configuration problem, no vendor found.'; ?>
<?php endif; ?>