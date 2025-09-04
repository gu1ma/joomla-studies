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
use Joomla\CMS\HTML\HTMLHelper;

$min = VmConfig::get('asks_minimum_comment_length', 50);
$max = VmConfig::get('asks_maximum_comment_length', 2000) ;
vmJsApi::JvalideForm();
vmJsApi::addJScript('askform', '
	jQuery(function($){
			$("#askform").validationEngine("attach");
			$("#comment").keyup( function () {
				var result = $(this).val();
					$("#counter").val( result.length );
			});
	});
');
?>

<div class="vendor-details-view vendor-details-view-contact">
	<h1 class="vm-page-title mb-5 text-center"><?php echo $this->vendor->vendor_store_name; ?></h1>

	<div class="row gy-4">
		<div class="col-lg-6">
 			<?php if (!empty($this->vendor->images[0])) : ?>
			<div class="vendor-image mb-4">
				<?php echo $this->vendor->images[0]->displayMediaThumb('class="img-fluid"', false, '', true, false, false, 288, 0); ?>
			</div>
			<?php endif; ?>

			<address>
				<?php echo shopFunctionsF::renderVendorAddress($this->vendor->virtuemart_vendor_id); ?>
			</address>

			<ul class="list-unstyled">
				<li><?php echo $this->linkdetails; ?></li>
				<li><?php echo $this->linktos; ?></li>
			</ul>
		</div>
		<div class="col-lg-6">
			<h2 class="h5 fw-normal mb-4"><?php echo vmText::_('COM_VIRTUEMART_VENDOR_ASK_QUESTION'); ?></h2>

			<div class="form-field">
				<form method="post" class="form-validate" action="<?php echo Route::_('index.php') ; ?>" name="askform" id="askform">
					<div class="mb-3">
						<label class="form-label" for="name"><?php echo vmText::_('COM_VIRTUEMART_USER_FORM_NAME'); ?></label>
						<input class="validate[required,minSize[4],maxSize[64]] form-control" id="name" type="text" value="<?php echo $this->user->name ?>" name="name" size="30"  validation="required name"/>
					</div>

					<div class="mb-3">
						<label class="form-label" for="email"><?php echo vmText::_('COM_VIRTUEMART_USER_FORM_EMAIL');  ?></label>
						<input class="validate[required,custom[email]] form-control" id="email" type="text" value="<?php echo $this->user->email ?>" name="email" size="30"  validation="required email"/>
					</div>

					<div class="mb-3">
						<label class="form-label" for="comment"><?php echo vmText::sprintf('COM_VIRTUEMART_ASK_COMMENT', $min, $max); ?></label>
						<textarea class="validate[required,minSize[<?php echo $min ?>],maxSize[<?php echo $max ?>]] field form-control" id="comment" name="comment" cols="30" rows="10"></textarea>
					</div>

					<div class="mb-3">
						<?php echo $this->captcha; // captcha addition ?>
					</div>

					<div class="row align-items-end">
						<div class="col-8">
							<button class="btn btn-primary" type="submit" name="submit_ask"><?php echo vmText::_('COM_VIRTUEMART_ASK_SUBMIT'); ?></button>
						</div>
						<div class="col-4 text-end">
							<label class="form-label mb-1" for="counter"><?php echo vmText::_('COM_VIRTUEMART_ASK_COUNT'); ?></label>
							<input class="counter form-control" id="counter" type="text" value="0" size="4" name="counter" maxlength="4" readonly="readonly" disabled />
						</div>
					</div>

					<input type="hidden" name="view" value="vendor" />
					<input type="hidden" name="virtuemart_vendor_id" value="<?php echo $this->vendor->virtuemart_vendor_id ?>" />
					<input type="hidden" name="option" value="com_virtuemart" />
					<input type="hidden" name="task" value="mailAskquestion" />
					<?php echo HTMLHelper::_( 'form.token' ); ?>
				</form>

			</div>
		</div>
	</div>
</div>