<?php
// Joomla Security Check - no direct access to this file 
// Prevents File Path Exposure
defined('_JEXEC') or die('Restricted access');

/** @var TYPE_NAME $viewData
 output the passed array / object content */
$item = $viewData['vm-grid-item'];
echo $item;