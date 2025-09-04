<?php

/**
 *
 * loads the add to cart button
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2015 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @version $Id: addtocartbtn.php 8024 2014-06-12 15:08:59Z Milbo $
 */

defined ('_JEXEC') or die('Restricted access');

/** @var TYPE_NAME $viewData */

if ($viewData['orderable'])
{
	echo '<button class="addtocart-button btn btn-primary w-100" type="button" name="addtocart">'.vmText::_( 'COM_VIRTUEMART_CART_ADD_TO' ).'</button>';
}
else
{
	echo '<button class="addtocart-button-disabled btn btn-secondary w-100" type="button" disabled>'.vmText::_( 'COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT' ).'</button>';
}