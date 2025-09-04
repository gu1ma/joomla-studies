<?php

/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen

 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default_images.php 10649 2022-05-05 14:29:44Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

vmJsApi::loadPopUpLib();

if(VmConfig::get('usefancy',1)){
	if(VmConfig::get('add_thumb_use_descr', false)){
		$u = 'descr';
	} else {
		$u = 'this.alt';
	}

$imageJS = '
jQuery(document).ready(function() {
	Virtuemart.updateImageEventListeners()
});
Virtuemart.updateImageEventListeners = function() {
	jQuery("a[rel=vm-additional-images]").fancybox({
		"titlePosition" 	: "inside",
		"transitionIn"	:	"elastic",
		"transitionOut"	:	"elastic"
	});
	jQuery(".additional-images a.product-image.image-0").removeAttr("rel");
	jQuery(".additional-images img.product-image").click(function() {
		jQuery(".additional-images a.product-image").attr("rel","vm-additional-images" );
		jQuery(this).parent().children("a.product-image").removeAttr("rel");
		var src = jQuery(this).parent().children("a.product-image").attr("href");
		jQuery(".main-image img").attr("src",src);
		jQuery(".main-image img").attr("alt",this.alt );
		jQuery(".main-image a").attr("href",src );
		jQuery(".main-image a").attr("title",this.alt );
		jQuery(".main-image .vm-img-desc").html('.$u.');
		});
	}
	jQuery(".vm-btn-expand").click(function(){
		jQuery(".main-image img").trigger("click");
	});
	';
} else {
	$imageJS = '
	jQuery(document).ready(function() {
		Virtuemart.updateImageEventListeners()
	});
	Virtuemart.updateImageEventListeners = function() {
		jQuery("a[rel=vm-additional-images]").facebox();
		var imgtitle = jQuery("span.vm-img-desc").text();
		jQuery("#facebox span").html(imgtitle);
	}
	';
}

vmJsApi::addJScript('imagepopup',$imageJS);

if (!empty($this->product->images)) {
	$image = reset($this->product->images);
	$width = VmConfig::get('img_width_full', 0);
	$height = VmConfig::get('img_height_full', 0);

	if(!empty($image) and is_object($image)){
		?>
		<div class="main-image position-relative d-flex flex-column align-items-center justify-content-center"<?php echo $height ? ' style="min-height:' . $height . 'px"' : '' ?>>
			<?php
			if(!empty($width) or !empty($height)){
				echo $image->displayMediaThumb('class="img-fluid"',true,'rel="vm-additional-images"', true, true, false, $width, $height);
			} else {
				echo $image->displayMediaFull('class="img-fluid"',true,'rel="vm-additional-images"');
			}
			?>

			<button class="vm-btn-expand" type="button">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrows-angle-expand" viewBox="0 0 16 16">
				    <path fill-rule="evenodd" d="M5.828 10.172a.5.5 0 0 0-.707 0l-4.096 4.096V11.5a.5.5 0 0 0-1 0v3.975a.5.5 0 0 0 .5.5H4.5a.5.5 0 0 0 0-1H1.732l4.096-4.096a.5.5 0 0 0 0-.707m4.344-4.344a.5.5 0 0 0 .707 0l4.096-4.096V4.5a.5.5 0 1 0 1 0V.525a.5.5 0 0 0-.5-.5H11.5a.5.5 0 0 0 0 1h2.768l-4.096 4.096a.5.5 0 0 0 0 .707"/>
				</svg>
			</button>
		</div>
		<?php
	}
}
?>
