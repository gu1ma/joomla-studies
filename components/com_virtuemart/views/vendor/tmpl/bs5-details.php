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
?>

<div class="vendor-details-view">
	<h1 class="vm-page-title mb-4 text-center"><?php echo $this->vendor->vendor_store_name; ?></h1>

	<div class="row gy-4 mb-4">
		<div class="col-lg-3 vendor-image">
			<?php if (!empty($this->vendor->images[0])) : ?>
				<?php echo $this->vendor->images[0]->displayMediaThumb('class="img-fluid"', false, '', true, false, false, 288, 0); ?>
			<?php endif; ?>
		</div>

		<div class="col-lg-9">
			<div class="vendor-description">
				<?php echo $this->vendor->vendor_store_desc; ?>
			</div>

			<?php if (!empty($this->vendor->vendor_legal_info)) : ?>
				<div class="vendor-legal-info mb-4">
					<?php echo $this->vendor->vendor_legal_info; ?>
				</div>
			<?php endif; ?>

			<div class="row gy-4">
				<div class="col-lg-6">
					<address>
						<?php echo shopFunctionsF::renderVendorAddress($this->vendor->virtuemart_vendor_id); ?>
					</address>
				</div>

				<div class="col-lg-6">
					<ul class="list-unstyled">
						<li><?php echo $this->linktos; ?></li>
						<li><?php echo $this->linkcontact; ?></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
