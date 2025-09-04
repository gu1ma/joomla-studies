<?php
defined('_JEXEC') or die();
/**
 *
 * @package	VirtueMart
 * @subpackage Plugins  - Fields
 * @author Reinhold Kainhofer, Open Tools
 * @link http://www.open-tools.net
 * @copyright Copyright (c) 2014 Reinhold Kainhofer. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
if (!class_exists('ShopFunctions'))
    require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'shopfunctions.php');

class JFormFieldVmLengthUnit extends JFormField {
    var $_name = 'vmLengthUnit';

    protected function getInput() {
        return ShopFunctions::renderLWHUnitList($this->name, $this->value);
    }
}