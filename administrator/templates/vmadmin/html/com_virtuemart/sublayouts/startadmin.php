<?php
/**
 * Administrator Start admin
 *
 * @package VirtueMart
 * @subpackage Sublayouts
 * @author Max Milbers
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * @version $Id: startadmin.php 10999 2024-04-17 21:01:32Z  $
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//vmLanguage::loadJLang('com_virtuemart_missing');
$adminTemplate = VMPATH_ROOT . '/administrator/templates/vmadmin/html/com_virtuemart';
$lang = JFactory::getLanguage();
$lang->load('com_virtuemart_missing', $adminTemplate, null, false, true);


/** @var TYPE_NAME $viewData */
$vmView = $viewData['vmView'];
$selectText = $viewData['selectText'];
$menuItems = $viewData['menuItems'];
/* TODO This is a temprory fiw to be able to test the template without having to do a vm release */
if (!empty($vmView->langList)) {
	$vmView->langList = str_replace('id="vmlang"', '', $vmView->langList);
}

/*if(JVM_VERSION<4){*/
	$hideMenu = false;
/*} else {
	$hideMenu = true;
}*/


$menuCookie = JFactory::getApplication()->input->cookie->getString('vmmenu', 'show');
// the previous template uses the value 'show' / 'hide', or users without cookie
if ($menuCookie == 'show') {
	if(JVM_VERSION<4){
		$menuCookie = 'visible';
	} else {
		$menuCookie = 'hidden';
	}
} else if ($menuCookie == 'hide') {
	$menuCookie = 'hidden';
}

$vmMenuCookie = ($menuCookie == 'visible') ? 'uk-visible@m' : 'uk-hidden@m';
$offcanvasMenuCookie = ($menuCookie == 'hidden') ? 'uk-visible@m' : 'uk-hidden@m';

$vm2string = "editImage: 'edit image',select_all_text: '" . vmText::_('COM_VIRTUEMART_DRDOWN_SELALL') . "',select_some_options_text: '" . vmText::_($selectText) . "'";
vmJsApi::addJScript('vmuikit-remindTab', "
		var tip_image='" . JURI::root(true) . "/components/com_virtuemart/assets/js/images/vtip_arrow.png';
		var vm2string ={" . $vm2string . "} ;
		jQuery( function($) {

			$('dl#system-message').hide().slideDown(400);
			$('.virtuemart-admin-area .toggler').vm2admin('toggle');
			$('#admin-ui-menu').vm2admin('accordeon');
			if ( $('#vmuikit-admin-ui-tabs').length  ) {
				$('#vmuikit-admin-ui-tabs').vmuikitadmin('vmuikitTabs',virtuemartcookie);
			}
			//$('.vmuikit-toggle-menu-button').vmuikitadmin('vmuikitToogleMenu');
			$('.vmuikit-js-offcanvas-toggle').vmuikitadmin('vmuikitToggleOffcanvas');
			
			
			$('#content-box [title]').vm2admin('tips',tip_image);
			$('.vmuikit-js-reset-value').click( function(e){
				e.preventDefault();
				$(this).parent().find('.vmuikit-js-reset-input-value').val('');
			});
		});	");

if(JVM_VERSION>3 and VmConfig::getInt('autoCloseJMenu',1)){
	vmJsApi::addJScript ('vm-closeJMenu', "var jWrapper = document.getElementById('wrapper');

            if (jWrapper && !jWrapper.classList.contains('closed') ){
                wrapper.className += ' closed';
                var mIcon = document.getElementById('menu-collapse-icon');
                mIcon.className = 'icon-fw icon-toggle-on';
                
                 var vmUikitOffCW = document.getElementById('vmuikit-menu-offcanvas-wrapper');
                 vmUikitOffCW.className = 'uk-width-auto@m vmuikit-menu-offcanvas-wrapper uk-first-column uk-hidden@m';
                 var vmUikitMW = document.getElementById('vmuikit-menu-wrapper');
                 vmUikitMW.className = 'uk-width-auto@m uk-flex uk-flex-stretch uk-padding-remove-horizontal uk-visible@m';
            }
		    ");
}
?>


<!--[if lt IE 9]>
<script src="//ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script>
<style type="text/css">
	.virtuemart-admin-area {
		display: block;
	}

	.virtuemart-admin-area #menu-wrapper {
		float: left;
	}

	.virtuemart-admin-area #admin-content {
		margin-left: 221px;
	}

</style>
<![endif]-->
<div>


	<div class="virtuemart-admin-area<?php echo $hideMenu ?> uk-grid-small" uk-grid>
		<!-- OFFCANVAS -->
		<div class="uk-width-auto@m <?php echo $offcanvasMenuCookie ?> vmuikit-menu-offcanvas-wrapper"
				id="vmuikit-menu-offcanvas-wrapper">
			<div id="vmuikit-menu-wrapper-offcanvas" class="vmuikit-menu-wrapper">

				<div class="uk-flex uk-flex-column">
					<a href="#"
							class="uk-icon-button uk-margin-small-bottom vmuikit-js-menu-offcanvas-toggle uk-visible@m"
							uk-tooltip="<?php echo vmText::_('COM_VM_SHOW_MENU') ?>">
						<span class="uk-icon-button" uk-icon="arrow-right"></span>
					</a>
					<button class="uk-button uk-button-small uk-button-default uk-padding-remove "
							uk-toggle="target: #vmuikit-menu-offcanvas-toggle"
							uk-tooltip="<?php echo vmText::_('COM_VM_TOGGLE_MENU') ?>"
					>
						<span uk-icon="menu"></span><span
								class="uk-margin-small-left uk-hidden@m"><?php echo vmText::_('COM_VM_VIEW_VM_MENU') ?></span>

					</button>

				</div>
				<!-- OFFCANVAS MENU-->
				<div class="vmuikit-menu-offcanvas-toggle" id="vmuikit-menu-offcanvas-toggle"
						uk-offcanvas="overlay: false">
					<div class="uk-offcanvas-bar">
						<button class="uk-offcanvas-close " type="button" uk-close></button>
						<div class="vmuikit-menu-offcanvas">

							<div class="uk-flex uk-flex-middle uk-flex-left">
								<div class="vm-menu-logo uk-padding-xsmall  uk-light   vmuikit-menu-toggle">
									<div class="uk-padding-small uk-padding-remove-vertical">
										<img src="<?php echo JURI::root(true) . '/administrator/components/com_virtuemart/assets/images/vm_menulogo.png' ?>">
									</div>
								</div>
							</div>
							<?php

							if (!empty($vmView->langList)) {
								?>
								<div class="menu-langlist uk-flex uk-flex-middle uk-flex-center uk-light uk-padding-xsmall">
									<?php echo $vmView->langList; ?>
								</div>

							<?php }

							?>

							<?php echo adminSublayouts::renderAdminVmSubLayout('menu',
								array(
									'vmView' => $vmView,
									'menuItems' => $menuItems
								)); ?>
							<?php echo adminSublayouts::renderAdminVmSubLayout('menu_bottom'); ?>
						</div>
						<?php echo $viewData['fnag'] ?>
					</div>

				</div>
				<!-- /OFFCANVAS  MENU -->
			</div>


		</div>
		<!-- /OFFCANVAS -->

		<!-- MENU -->
		<div class="uk-width-auto@m <?php echo $vmMenuCookie ?> uk-flex uk-flex-stretch uk-padding-remove-horizontal"
				id="vmuikit-menu-wrapper<?php echo $hideMenu ?>">
			<div class="vmuikit-menu-wrapper uk-light uk-background-secondary">
				<div uk-height-viewport="offset-top: true;offset-bottom: true">


					<div id="vmuikit-menu">

						<div class="">
							<div class="uk-grid-collapse" uk-grid>
								<div class="uk-width-expand uk-flex uk-flex-middle uk-flex-left">
									<div class="vm-menu-logo uk-padding-xsmall  uk-light   vmuikit-menu-toggle">
										<div class="uk-padding-xsmall">
											<img src="<?php echo JURI::root(true) . '/administrator/components/com_virtuemart/assets/images/vm_menulogo.png' ?>">
										</div>
									</div>
								</div>
								<div class="uk-width-auto">
									<div class="uk-flex uk-flex-top uk-flex-right ">
										<a href="#"
												class=" uk-icon-button  vmuikit-js-menu-offcanvas-toggle md-color-white"
												uk-tooltip="<?php echo vmText::_('COM_VM_HIDE_MENU') ?>"
												type="button">
											<span uk-icon="arrow-left"></span>
										</a>
									</div>
								</div>

							</div>

							<?php if (!empty($vmView->langList)) {
								?>
								<div class="menu-langlist uk-flex uk-flex-middle uk-flex-center uk-light uk-padding-xsmall">
									<?php echo $vmView->langList; ?>
								</div>

							<?php } ?>

							<?php echo adminSublayouts::renderAdminVmSubLayout('menu',
								array(
									'vmView' => $vmView,
									'menuItems' => $menuItems
								)); ?>
							<?php echo adminSublayouts::renderAdminVmSubLayout('menu_bottom'); ?>


						</div>

					</div>
					<?php echo $viewData['fnag'] ?>;
				</div>
			</div>


		</div>

		<!-- /MENU -->


		<!-- BOF CONTENT -->
		<div class="uk-width-expand">
			<div class="">

				<?php
				// the return here is on purpose
				return;
				?>


			</div>
		</div>
		<!-- EOF CONTENT -->
	</div>
</div>


