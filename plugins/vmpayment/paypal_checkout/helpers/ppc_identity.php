<?php

/**
 *
 * Paypal checkout payment plugin
 *
 * @author Max Milbers
 * @version $Id: ppc_identity.php
 * @package VirtueMart
 * @subpackage payment
 * Copyright (C) 2023 Virtuemart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */


class PayPalIdentity {

	static function getUserInfoUrl($method){
		return PayPalToken::getUrl($method).'/v1/identity/openidconnect/userinfo?schema=openid';
	}

	static function getUserInfo(&$plugin){

		$currentMethod = $plugin->_currentMethod;
		//PayPalToken::getPayPalAccessToken($currentMethod);

		$url = self::getUserInfoUrl($currentMethod);

		$data = new stdClass();
		$data->contentType = 'application/x-www-form-urlencoded';
		$body = PayPalToken::sendCURLDefaultHeader($plugin, $url, $data);

		vmdebug('my $content in getUserInfo',$body);
	}
}