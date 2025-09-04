<?php

/**
 * Show the form Ask a Question
 *
 * @package	VirtueMart
 * @subpackage
 * @author Kohl Patrick, Maik K�nnemann
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
* @version $Id: default.php 2810 2011-03-02 19:08:24Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined ( '_JEXEC' ) or die ( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$min = VmConfig::get('asks_minimum_comment_length', 50);
$max = VmConfig::get('asks_maximum_comment_length', 2000);

/* Let's see if we found the product */
if (empty ( $this->product )) {
	echo vmText::_ ( 'COM_VIRTUEMART_PRODUCT_NOT_FOUND' );
	echo '<br /><br />  ' . $this->continue_link_html;
} else {
	$session = Factory::getSession();
	$sessData = $session->get('mailrecommend', 0, 'vm');
	$vendorModel = VmModel::getModel ('vendor');
	$this->vendor = $vendorModel->getVendor ($this->product->virtuemart_vendor_id);

	if (!empty($this->login)) {
		echo $this->login;
		return;
	}

	if (empty($this->login) or VmConfig::get('recommend_unauth',false)) {
		vmJsApi::JvalideForm();
		vmJsApi::addJScript('askform','
			jQuery(function($){
					$("#askform").validationEngine("attach");
					var counterResult = $("#comment").val().length;
					$("#counter").val( counterResult );
					$("#comment").keyup( function () {
						var result = $(this).val();
							$("#counter").val( result.length );
					});
			});
		');
		?>
		<div class="ask-a-question-view p-3">
			<h1 class="pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_RECOMMEND')  ?></h1>

			<form method="post" class="form-validate" action="<?php echo Route::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$this->product->virtuemart_product_id.'&virtuemart_category_id='.$this->product->virtuemart_category_id.'&tmpl=component', FALSE) ; ?>" name="askform" id="askform">
				<div class="mb-3">
					 <label class="form-label" for="name"><?php echo vmText::_('COM_VIRTUEMART_RECOMMEND_NAME'); ?> : </label>
					 <input class="form-control validate[required,minSize[3],maxSize[64]]" id="name" type="text" value="" name="name"  size="30" />
				</div>

				<div class="mb-3">
					 <label class="form-label" for="email"><?php echo vmText::_('COM_VIRTUEMART_RECOMMEND_EMAIL'); ?> : </label>
					 <input class="form-control validate[required,custom[email]]" id="email" type="text" value="" name="email"  size="30"/>
				</div>

				<div class="mb-3">
					<label class="form-label" for="comment"><?php echo vmText::sprintf('COM_VIRTUEMART_COMMENT', $min, $max); ?></label>
					<textarea
						class="form-control validate[required,minSize[<?php echo $min ?>],maxSize[<?php echo $max ?>]] field"
						id="comment"
						name="comment"
						rows="5"
						placeholder="<?php echo vmText::sprintf('COM_VIRTUEMART_RECOMMEND_COMMENT', $this->vendor->vendor_store_name); ?>"><?php echo isset($sessData['comment']) ? $sessData['comment'] : ''; ?></textarea>
				</div>

				<div class="mb-3">
					<?php echo $this->captcha; // captcha addition ?>
				</div>

				<div class="row align-items-end">
					<div class="col-8">
						<button class="btn btn-primary" type="submit" name="submit_ask"><?php echo vmText::_('COM_VIRTUEMART_RECOMMEND_SUBMIT')  ?></button>
					</div>
					<div class="col-4 text-end">
						<label class="form-label mb-0" for="counter"><?php echo vmText::_('COM_VIRTUEMART_ASK_COUNT')  ?></label>
						<input class="form-control counter" id="counter" type="text" value="0" size="4" name="counter" maxlength="4" readonly="readonly" disabled />
					</div>
				</div>

				<input type="hidden" name="virtuemart_product_id" value="<?php echo vRequest::getInt('virtuemart_product_id',0); ?>" />
				<input type="hidden" name="tmpl" value="component" />
				<input type="hidden" name="view" value="productdetails" />
				<input type="hidden" name="option" value="com_virtuemart" />
				<input type="hidden" name="virtuemart_category_id" value="<?php echo vRequest::getInt('virtuemart_category_id'); ?>" />
				<input type="hidden" name="task" value="mailRecommend" />
				<?php echo HTMLHelper::_( 'form.token' ); ?>
			</form>
		</div>
<?php
	}
}
?>