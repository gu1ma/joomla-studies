<?php

/**
 *
 * renders the categories
 *
 * @package     VirtueMart
 * @subpackage
 * @author      Eugen Stranz
 * @link        https://virtuemart.net
 * @copyright   Copyright (c) 2018 VirtueMart Team. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @version     $Id: bs4-searchcustomvalues.php 8024 2014-06-12 15:08:59Z Milbo $
 */

// Joomla Security Check - no direct access to this file 
// Prevents File Path Exposure
defined('_JEXEC') or die('Restricted access');


/** @var TYPE_NAME $viewData
output the passed array / object content */
$searchCustomValue = $viewData['searchcustomvalues'];

// var_dump($searchCustomValue);
if ($searchCustomValue->field_type == "S")
{
    ?>
    <div class="form-group m-0">
        <label for="exampleInputEmail1"><?php echo vmText::_($searchCustomValue->custom_title); ?></label>
        <?php echo JHtml::_(
            'select.genericlist', $searchCustomValue->value_options,
            'customfields[' . $searchCustomValue->virtuemart_custom_id . ']',
            'class="changeSendForm form-control"', 'virtuemart_custom_id', 'custom_title',
            $searchCustomValue->v
        ); ?>
    </div>
    <?php
} else if ($searchCustomValue->field_type == "P")
{
    $name  = 'customfields[' . $searchCustomValue->virtuemart_custom_id . ']';
    $value = vRequest::getString(
        'customfields[' . $searchCustomValue->virtuemart_custom_id . ']'
    );
    ?>

    <?php echo vmText::_($searchCustomValue->custom_title); ?>
    <input name="<?php echo $name ?>" class="inputbox vm-chzn-select"
           type="text" size="20" value="<?php echo $value ?>"/>

    <?php
}