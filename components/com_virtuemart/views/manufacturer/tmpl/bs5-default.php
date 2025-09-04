<?php

/**
*
* Description
*
* @package	VirtueMart
* @subpackage Manufacturer
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

// Calculating Manufacturers Per Row
$manufacturerPerRow = VmConfig::get ('manufacturer_per_row', 4);
$bscol = ' col-lg-'.floor ( 12 / $manufacturerPerRow );

?>
<h1 class="vm-page-title mb-4 text-center"><?php echo $this->document->title; ?></h1>
<?php if (!empty($this->manufacturers)) : ?>
	<div class="manufacturer-view-default row align-items-end gy-3">
		<?php foreach ( $this->manufacturers as $manufacturer ) : ?>
			<?php
				// Manufacturer Elements
				$manufacturerURL = Route::_('index.php?option=com_virtuemart&view=manufacturer&virtuemart_manufacturer_id=' . $manufacturer->virtuemart_manufacturer_id, FALSE);
				$manufacturerIncludedProductsURL = Route::_('index.php?option=com_virtuemart&view=category&virtuemart_manufacturer_id=' . $manufacturer->virtuemart_manufacturer_id, FALSE);
				$manufacturerImage = $manufacturer->images[0]->displayMediaThumb('class="img-fluid"', false, '', true, false, false, 288, 0);
			?>

			<div class="manufacturer col-6 col-md-4<?php echo $bscol; ?> text-center">
				<a title="<?php echo $manufacturer->mf_name; ?>" href="<?php echo $manufacturerURL; ?>">
					<div class="manufacturer-img mb-3"><?php echo $manufacturerImage; ?></div>
					<h2 class="vm-subcategory-title fw-normal pt-2 border-top">
					   <?php echo $manufacturer->mf_name; ?>
					</h2>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>