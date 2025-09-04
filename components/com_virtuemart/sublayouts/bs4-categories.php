<?php

/**
 *
 * renders the categories
 *
 * @package     VirtueMart
 * @subpackage
 * @author      Max Milbers, Eugen Stranz
 * @link        https://virtuemart.net
 * @copyright   Copyright (c) 2015 VirtueMart Team. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @version     $Id: bs4-categories.php 8024 2014-06-12 15:08:59Z Milbo $
 */


// Joomla Security Check - no direct access to this file 
// Prevents File Path Exposure
defined('_JEXEC') or die('Restricted access');

/** @var TYPE_NAME $viewData */
// output the passed array / object content
$category = $viewData['bs4-categories'];

// CategoryURL
$caturl = JRoute::_(
    'index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id, FALSE
);

// $ajaxUpdate = '';
// if(VmConfig::get ('ajax_category', false)){
//     $ajaxUpdate = 'data-dynamic-update="1"';
// }
// todo $ajaxUpdate in den container der kategorie einbinden
?>
<div class="card text-center">
    <div>
        <?php echo $category->images[0]->displayMediaThumb('class="img-fluid vm-category-thumbnail"', FALSE); ?>
    </div>
    <div class="card-body">
        <h5 class="card-title"><?php echo $category->category_name; ?></h5>
        <a href="<?php echo $caturl ?>" title="<?php echo vmText::_($category->category_name) ?>"
           class="btn btn-primary">
            <?php echo vmText::_('JSHOW'); ?>
        </a>
    </div>
</div>