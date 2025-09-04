<?php

/**
*
* Layout for the login
*
* @package	VirtueMart
* @subpackage User
* @author Max Milbers, George Kostopoulos
*
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: cart.php 4431 2011-10-17 grtrustme $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

//set variables, usually set by shopfunctionsf::getLoginForm in case this layout is differently used
if (!isset( $this->show )) $this->show = TRUE;
if (!isset( $this->from_cart )) $this->from_cart = FALSE;
if (!isset( $this->order )) $this->order = FALSE ;


if (empty($this->url)){
	$url = vmURI::getCurrentUrlBy('request');
} else{
	$url = $this->url;
}

vmdebug('My Url in loginform',$url);
$user = Factory::getUser();

// Set bootstrap classes to change login form layout (vertical or inline) per view
$app   = Factory::getApplication();
$input = $app->getInput();
$view =  $input->getCmd('view', '');
$tmpl  = $input->getCmd('tmpl', '');
$formBg = $tmpl == 'component' && $view != 'cart' ? '' : ' bg-light';
$formPadding = $tmpl == 'component' && $view != 'cart' ? '' : ' p-3';
$hiddenLabel = ($tmpl == 'component' && $view != 'cart') || $this->order ? '' : ' visually-hidden';
?>

<?php if ($this->show and $user->id == 0  ) : ?>
	<?php
		vmJsApi::vmValidator();
		//Extra login stuff, systems like openId and plugins HERE
		if (JPluginHelper::isEnabled('authentication', 'openid')) {
			$lang = vmLanguage::getLanguage();
			$lang->load('plg_authentication_openid', JPATH_ADMINISTRATOR);
			$langScript = '
			'.'var JLanguage = {};' .
			' JLanguage.WHAT_IS_OPENID = \'' . vmText::_('WHAT_IS_OPENID') . '\';' .
			' JLanguage.LOGIN_WITH_OPENID = \'' . vmText::_('LOGIN_WITH_OPENID') . '\';' .
			' JLanguage.NORMAL_LOGIN = \'' . vmText::_('NORMAL_LOGIN') . '\';' .
			' var comlogin = 1;
			';

			vmJsApi::addJScript('login_openid',$langScript);
			HTMLHelper::_('script', 'openid.js');
		}
	?>

	<?php if ($this->order) : //guest order section , we display this with 2 columns ?>
		<div class="row gy-4 align-items-stretch">
			<div class="col-md-6">
				<div class="vm-track-order h-100 px-4 py-3 bg-light">
					<p class="lead pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_ORDER_ANONYMOUS') ?></p>

					<form action="<?php echo Route::_( 'index.php', 1, $this->useSSL); ?>" method="post" name="com-login">
						<div class="mb-3" id="com-form-order-number">
							<label class="form-label" for="order_number"><?php echo vmText::_('COM_VIRTUEMART_ORDER_NUMBER') ?></label>
							<input class="form-control" id="order_number" type="text" name="order_number" size="18" />
						</div>

						<div class="mb-3" id="com-form-order-pass">
							<label class="form-label" for="order_pass"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PASS') ?></label>
							<input class="form-control" id="order_pass" type="text" name="order_pass" size="18" />
						</div>

						<div class="mb-3" id="com-form-order-submit">
							<button class="btn btn-primary" type="submit" name="Submitbuton"><?php echo vmText::_('COM_VIRTUEMART_ORDER_BUTTON_VIEW') ?></button>
						</div>

						<input type="hidden" name="option" value="com_virtuemart" />
						<input type="hidden" name="view" value="orders" />
						<input type="hidden" name="layout" value="details" />
						<input type="hidden" name="return" value="" />
					</form>
				</div>
			</div>
	<?php endif; ?>

	<?php if ($this->order) : ?>
			<div class="col-md-6">
	<?php endif; ?>

				<div class="vm-login<?php echo $this->order ? ' h-100' : ''; ?> px-4 py-3 mb-4<?php echo $formBg;?>">
					<?php if ($tmpl == 'component') : ?>
						<h1 class="vm-section-title pb-2 mb-4 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_ORDER_CONNECT_FORM'); ?></h1>
					<?php else : ?>
						<p class="lead pb-2 mb-3 border-bottom"><?php echo vmText::_('COM_VIRTUEMART_ORDER_CONNECT_FORM'); ?></p>
					<?php endif; ?>

					<form class="row<?php echo !$this->order ? ' row-cols-lg-auto ' : ''; ?>align-items-center<?php echo $formPadding; ?>needs-validation" id="com-form-login" action="<?php echo Uri::root(true).'/'.$url; ?>" method="post" name="com-login">
						<div class="col-12 mb-3">
							<label class="form-label<?php echo $hiddenLabel; ?>" for="login-username"><?php echo vmText::_('COM_VIRTUEMART_USERNAME'); ?></label>
							<input class="form-control" id="login-username" type="text" name="username"<?php echo !empty($hiddenLabel) ? ' placeholder="' . vmText::_('COM_VIRTUEMART_USERNAME') .'" ' : ''; ?>size="18" required />
						</div>

						<div class="col-12 mb-3">
							<label class="form-label<?php echo $hiddenLabel; ?>" for="login-password"><?php echo vmText::_('COM_VIRTUEMART_PASSWORD'); ?></label>
							<input class="form-control" id="login-password" type="password" name="password"<?php echo !empty($hiddenLabel) ? ' placeholder="' . vmText::_('COM_VIRTUEMART_PASSWORD') .'" ' : ''; ?>size="18" required />
						</div>

						<?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
							<div class="col-12 mb-3">
								<input class="form-check-input" type="checkbox" id="remember" name="remember" value="yes" />
								<label class="form-check-label" for="remember"><?php echo $remember_me = vmText::_('JGLOBAL_REMEMBER_ME') ?></label>
							</div>
						<?php endif; ?>

						<div class="col-12 mb-3" id="com-form-login-remember">
							<button class="btn btn-primary" type="submit" name="Submit"><?php echo vmText::_('COM_VIRTUEMART_LOGIN') ?></button>
						</div>

						<div class="col-12 mb-3">
							<a href="<?php echo Route::_('index.php?option=com_users&view=remind'); ?>" rel="nofollow">
							<?php echo vmText::_('COM_VIRTUEMART_ORDER_FORGOT_YOUR_USERNAME'); ?></a><br>

							<a href="<?php echo Route::_('index.php?option=com_users&view=reset'); ?>" rel="nofollow">
							<?php echo vmText::_('COM_VIRTUEMART_ORDER_FORGOT_YOUR_PASSWORD'); ?></a>
						</div>

						<input type="hidden" name="task" value="user.login" />
						<input type="hidden" name="option" value="com_users" />
						<input type="hidden" name="return" value="<?php echo base64_encode($url) ?>" />
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
	<?php if ($this->order) : //guest order section , we display this with 2 columns ?>
			</div>
		</div>
	<?php endif; ?>
<?php elseif ($user->id) : ?>
	<form class="p-3 bg-light mb-4" action="<?php echo Uri::root(true).'/'.$url; ?>" method="post" name="login" id="form-login">
		<?php echo vmText::sprintf( 'COM_VIRTUEMART_HINAME', $user->name ); ?>
		<button class="btn btn-sm btn-primary ms-2" type="submit" name="Submit"><?php echo vmText::_( 'COM_VIRTUEMART_BUTTON_LOGOUT'); ?></button>
		<input type="hidden" name="option" value="com_users" />
		<input type="hidden" name="task" value="user.logout" />
		<?php echo HTMLHelper::_('form.token'); ?>
		<input type="hidden" name="return" value="<?php echo base64_encode($url) ?>" />
	</form>
<?php endif; ?>