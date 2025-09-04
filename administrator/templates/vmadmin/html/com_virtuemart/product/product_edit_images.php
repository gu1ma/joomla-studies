<?php
/**
 *
 * The main product images
 *
 * @package    VirtueMart
 * @subpackage Product
 * @author RolandD
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: product_edit_images.php 10775 2022-12-19 20:38:17Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');


?>

<div class="selectimage">

	<?php
	if (empty($this->product->images[0]->virtuemart_media_id)) {
		?>
		<!-- MEDIA Hidden -->
		<input type="hidden"  name="file_is_product_image" value="1" />
	<?php
	}
	if (!empty($this->product->virtuemart_media_id)) {
		echo VmuikitMediaHandler::displayFilesHandler($this->product->images[0], $this->product->virtuemart_media_id, 'product');

	} else {
		echo VmuikitMediaHandler::displayFilesHandler($this->product->images[0], null, 'product');

	}
	?>
</div>


