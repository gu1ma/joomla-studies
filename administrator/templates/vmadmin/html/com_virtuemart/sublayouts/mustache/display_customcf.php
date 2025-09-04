<?php
/**
 *
 * @package VirtueMart
 * @subpackage Mustache template
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * @version $Id: display_customcf.php 10912 2023-09-04 10:47:36Z Milbo $
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>

<ul class="vmuikit-js-container-removable vmuikit-js-sortable uk-list uk-list-striped" >
	{{#customcfs}}
	<li class="vmuikit-js-removable">
		<div class="vmuikit-js-customcf vmuikit-customcf vmuikit-customcf-striped">
			<div class="uk-grid-small uk-grid-divider" uk-grid>
				<div class="uk-width-auto@m">
					<div class="">
						<div class="uk-grid-small" uk-grid>

							<div class="uk-width-1-1">
								<div class="uk-iconnav uk-">
                                    {{#canMove}}
                                    <a uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_CUSTOMFIELD_SORTABLE') ?>"
                                       href="#"
                                       class="uk-sortable-handle">
                                        <span class="" uk-icon="icon: move; ratio: 0.75"></span>
                                    </a>
                                    {{/canMove}}
                                    {{#canRemove}}
                                    <div class="uk-link vmuikit-js-remove"
                                         uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_CUSTOMFIELD_REMOVE') ?>">
                                        <span class="" uk-icon="icon: trash; ratio: 0.75"></span>
                                    </div>
                                    {{/canRemove}}
									{{#disableDerivedCheckbox }}
									<label class="uk-link"
											uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_CUSTOMFLD_DIS_DER_TIP') ?>">
										<span class="" uk-icon="icon: ban; ratio: 0.75"></span>
										{{{disableDerivedCheckbox}}}
									</label>
									{{/disableDerivedCheckbox }}
									{{#overrideCheckbox }}
									<label class="uk-link"
											uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_DIS_DER_CUSTOMFLD_OVERR_DER_TIP') ?>">
										<span class="" uk-icon="icon: warning; ratio: 0.75"></span>
										{{{overrideCheckbox}}}
									</label>
									{{/overrideCheckbox }}
									<?php // Click here to prevent inhereting of this customfield to the childproduct ?>
                                    {{#nonInheritableCheckbox }}
                                    <div class="uk-width-auto uk-text-right">
                                        <label class="uk-link" uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_CUSTOMFLD_DIS_INH_TIP') ?>">
                                            <span class="" uk-icon="icon: lock; ratio: 1"></span>
                                            {{{nonInheritableCheckbox}}}
                                        </label>
                                    </div>
                                    {{/nonInheritableCheckbox }}
								</div>
								<div class="">

									<div class="uk-text-bold">{{title}}</div>
									<div class="uk-text-meta">{{type}}</div>
									<div class="uk-iconnav">
										{{#is_cart_attribute}}
										<div class=""
												uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_CUSTOM_IS_CART_ATTRIBUTE') ?>"
										>
											<span uk-icon="icon: cart; ratio: 0.75"></span>
										</div>
										{{/is_cart_attribute}}
										{{#searchable}}
										<div class=""
												uk-tooltip="<?php echo vmText::_('COM_VM_CUSTOM_IS_SEARCHABLE') ?>"
										>
											<span uk-icon="icon: search; ratio: 0.75"></span>
										</div>
										{{/searchable}}
										{{#layout_pos}}
										<div class=""
												uk-tooltip="{{layout_pos}}"
										>
											<span uk-icon="icon: location; ratio: 0.75"></span>
										</div>
										{{/layout_pos}}
									</div>

								</div>


							</div>
						</div>


					</div>
				</div>

				<div class="uk-width-expand@m">
					<div class="">
						{{#displayHTML }}
						{{{displayHTML}}}
						{{/displayHTML }}
						{{#hiddenHTML }}
						{{{hiddenHTML}}}
						{{/hiddenHTML }}
					</div>
				</div>
			</div>
		</div>
	</li>
	{{/customcfs}}
</ul>
<?php
return
?>


{{#customcfs}}
<div class="vmuikit-js-customcf vmuikit-customcf">
	<div class="uk-card uk-card-small uk-card-vm ">
		<div class="uk-card-header uk-padding-remove">

			<div class="uk-navbar-container" uk-navbar>
				<div class="uk-navbar-left">
					<div class="uk-navbar-item">
						<h6 class="uk-margin-small-bottom uk-margin-remove-adjacent uk-text-bold">{{title}}</h6>
					</div>
					<div class="uk-navbar-item">
						<div class="uk-text-meta">{{type}}</div>
					</div>
					{{#is_cart_attribute}}
					<div class="uk-navbar-item">
						<div class="uk-icon-button md-bg-grey-400"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_CUSTOM_IS_CART_ATTRIBUTE') ?>"
						>
							<span uk-icon="icon: cart; ratio: 0.75"></span>
						</div>
					</div>
					{{/is_cart_attribute}}
					{{#searchable}}
					<div class="uk-navbar-item">
						<div class="uk-icon-button md-bg-grey-400"
								uk-tooltip="<?php echo vmText::_('COM_VM_CUSTOM_IS_SEARCHABLE') ?>"
						>
							<span uk-icon="icon: search; ratio: 0.75"></span>
						</div>
					</div>
					{{/searchable}}
					{{#layout_pos}}
					<div class="uk-navbar-item">
						<div class="md-bg-grey-400"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_CUSTOM_LAYOUT_POS') ?>"
						>
							<span class="uk-label md-bg-grey-400 md-color-grey-600">{{layout_pos}}</span>
						</div>
					</div>
					{{/layout_pos}}
				</div>

				<div class="uk-navbar-right">
					<?php // Click here to disable the derived customfield for this child product ?>
					{{#overrideCheckbox }}
					<div class="uk-navbar-item">
						<label class="uk-link"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_CUSTOMFLD_DIS_DER_TIP') ?>">
							<span class="" uk-icon="icon: disable; ratio: 0.75"></span>
							{{{overrideCheckbox}}}
						</label>
					</div>
					{{/overrideCheckbox }}
					{{#canMove}}
					<div class="uk-navbar-item">
						<a uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_CUSTOMFIELD_SORTABLE') ?>" href="#"
								class="uk-sortable-handle">
							<span class="" uk-icon="icon: move; ratio: 0.75"></span>
						</a>
					</div>
					{{/canMove}}
					{{#canRemove}}
					<div class="uk-navbar-item">
						<div class="uk-link vmuikit-js-remove"
								uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_CUSTOMFIELD_REMOVE') ?>">
							<span class="" uk-icon="icon: trash; ratio: 0.75"></span>
						</div>
					</div>
					{{/canRemove}}
				</div>

			</div>

		</div>
		<div class="uk-card-body">
			{{#displayHTML }}
			{{{displayHTML}}}
			{{/displayHTML }}
			{{#hiddenHTML }}
			{{{hiddenHTML}}}
			{{/hiddenHTML }}
		</div>

	</div>
</div>
{{/customcfs}}







