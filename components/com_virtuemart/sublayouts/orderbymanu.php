<?php

/** @var TYPE_NAME $viewData */
$manufacturers = $viewData['manufacturers'];
$fieldLink = $viewData['fieldLink'];
$orderbyTxt = $viewData['orderbyTxt'];
$orderDirLink = $viewData['orderDirLink'];

$manufacturerTxt = '';
$manufacturerLink = '';

$virtuemart_manufacturer_id = vRequest::getInt ('virtuemart_manufacturer_id', 0);
if (count ($manufacturers) > 1) {

  $manufacturerLink = '<div class="orderlist">';



  if (!empty($virtuemart_manufacturer_id)) {
    $manufacturerTxt = '&virtuemart_manufacturer_id=' . $virtuemart_manufacturer_id;
  }

  if ($virtuemart_manufacturer_id > 0) {
    $allLink = str_replace($manufacturerTxt,$fieldLink,'');
    $allLink .= '&virtuemart_manufacturer_id=0';
    $manufacturerLink .= '<div><a title="" href="' . JRoute::_ ($allLink . $orderbyTxt . $orderDirLink , FALSE) . '">' . vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_ALL_MANUFACTURER') . '</a></div>';
  }
  foreach ($manufacturers as $mf) {
    $l = str_replace($manufacturerTxt,'',$fieldLink) . '&virtuemart_manufacturer_id=' . $mf->virtuemart_manufacturer_id . $orderbyTxt . $orderDirLink . '&limitstart=0';
    $link = JRoute::_ ($l,FALSE);
    if ($mf->virtuemart_manufacturer_id != $virtuemart_manufacturer_id) {
      $manufacturerLink .= '<div><a title="' . $mf->mf_name . '" href="' . $link . '">' . $mf->mf_name . '</a></div>';
    }
    else {
      $currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="activeOrder">' . $mf->mf_name . '</div>';
    }
  }
  $manufacturerLink .= '</div>';
}
elseif ($virtuemart_manufacturer_id > 0) {
  $currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="activeOrder">' . $manufacturers[0]->mf_name . '</div>';
}
else {
  $currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="Order"> ' . $manufacturers[0]->mf_name . '</div>';
}
if (empty ($currentManufacturerLink)) {
  $currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="activeOrder">' . vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_MANUFACTURER') . '</div>';
}
$manuList = ' <div class="orderlistcontainer">' . $currentManufacturerLink;
$manuList .= $manufacturerLink . '</div><div class="clear"></div>';

echo $manuList;