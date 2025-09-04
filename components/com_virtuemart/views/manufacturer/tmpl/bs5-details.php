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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// Manufacturer Product Link
$manufacturerProductsURL = Route::_('index.php?option=com_virtuemart&view=category&virtuemart_manufacturer_id=' . $this->manufacturer->virtuemart_manufacturer_id, FALSE);
?>

<div class="manufacturer-details-view">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-lg-4">
			 	<?php if (!empty($this->manufacturerImage)) : // Manufacturer Image ?>
					<div class="manufacturer-image text-center">
						<?php echo $this->manufacturerImage; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="col-lg-8">
				<div class="d-flex align-items-end border-bottom pb-2 mb-3">
					<h1 class="d-inline-block mb-0 me-auto"><?php echo $this->manufacturer->mf_name; ?></h1>

					<?php if(!empty($this->manufacturer->mf_email)) : // Manufacturer Email ?>
						<span class="manufacturer-email d-flex align-items-center me-4">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-at me-1" viewBox="0 0 16 16">
								<path d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2zm3.708 6.208L1 11.105V5.383zM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2z"/>
								<path d="M14.247 14.269c1.01 0 1.587-.857 1.587-2.025v-.21C15.834 10.43 14.64 9 12.52 9h-.035C10.42 9 9 10.36 9 12.432v.214C9 14.82 10.438 16 12.358 16h.044c.594 0 1.018-.074 1.237-.175v-.73c-.245.11-.673.18-1.18.18h-.044c-1.334 0-2.571-.788-2.571-2.655v-.157c0-1.657 1.058-2.724 2.64-2.724h.04c1.535 0 2.484 1.05 2.484 2.326v.118c0 .975-.324 1.39-.639 1.39-.232 0-.41-.148-.41-.42v-2.19h-.906v.569h-.03c-.084-.298-.368-.63-.954-.63-.778 0-1.259.555-1.259 1.4v.528c0 .892.49 1.434 1.26 1.434.471 0 .896-.227 1.014-.643h.043c.118.42.617.648 1.12.648m-2.453-1.588v-.227c0-.546.227-.791.573-.791.297 0 .572.192.572.708v.367c0 .573-.253.744-.564.744-.354 0-.581-.215-.581-.8Z"/>
							</svg>
							<?php echo HTMLHelper::_('email.cloak', $this->manufacturer->mf_email,true,vmText::_('COM_VIRTUEMART_EMAIL'),false) ?>
						</span>
					<?php endif; ?>

					<?php if(!empty($this->manufacturer->mf_url)) : // Manufacturer URL ?>
						<span class="manufacturer-url d-flex align-items-center">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-arrow-up-right me-1" viewBox="0 0 16 16">
								<path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
								<path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
							</svg>
							<a target="_blank" href="<?php echo $this->manufacturer->mf_url ?>"><?php echo vmText::_('COM_VIRTUEMART_MANUFACTURER_PAGE') ?></a>
						</span>
					<?php endif; ?>
				</div>

				<?php if(!empty($this->manufacturer->mf_desc)) : // Manufacturer Description ?>
					<div class="manufacturer-description mb-4">
						<?php echo $this->manufacturer->mf_desc ?>
					</div>
				<?php endif; ?>

				<?php if (!empty($this->manufacturer->virtuemart_manufacturer_id)) : ?>
					<a class="manufacturer-product-link btn btn-primary" target="_top" href="<?php echo $manufacturerProductsURL; ?>"><?php echo vmText::sprintf('COM_VIRTUEMART_PRODUCT_FROM_MF',$this->manufacturer->mf_name); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>