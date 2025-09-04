<?php

/** @var TYPE_NAME $viewData */
$orderby = $viewData['orderby'];
$fieldLink = $viewData['fieldLink'];
$orderDir = $viewData['orderDir'];
$orderbyTxt = $viewData['orderbyTxt'];
$orderDirLink = $viewData['orderDirLink'];

$virtuemart_manufacturer_id = vRequest::getInt ('virtuemart_manufacturer_id', 0);

$manufacturerTxt = '';
if (!empty($virtuemart_manufacturer_id)) {
	$manufacturerTxt = '&virtuemart_manufacturer_id=' . $virtuemart_manufacturer_id;
}

/* order by link list*/
$orderByLink = '';
$fields = VmConfig::get ('browse_orderby_fields');
if (count ($fields) > 1) {
	$orderByLink = '<div class="orderlist">';
	foreach ($fields as $field) {
		if ($field != $orderby) {

			$dotps = strrpos ($field, '.');
			if ($dotps !== FALSE) {
				$prefix = substr ($field, 0, $dotps + 1);
				$fieldWithoutPrefix = substr ($field, $dotps + 1);
			}
			else {
				$prefix = '';
				$fieldWithoutPrefix = $field;
			}

			$text = vmText::_ ('COM_VIRTUEMART_' . strtoupper (str_replace(array(',',' '),array('_',''),$fieldWithoutPrefix)));

			$field = explode('.',$field);
			if(isset($field[1])){
				$field = $field[1];
			} else {
				$field = $field[0];
			}
			$link = JRoute::_ ($fieldLink . $manufacturerTxt . '&orderby=' . $field ,FALSE);

			$orderByLink .= '<div><a title="' . $text . '" href="' . $link . '">' . $text . '</a></div>';
		}
	}
	$orderByLink .= '</div>';
}

if($orderDir == 'ASC'){
	$orderDir = 'DESC';
} else {
	$orderDir = 'ASC';
}

$orderDirConf = VmConfig::get ('prd_brws_orderby_dir');
$orderbyCfg = VmConfig::get ('browse_orderby_field');

if ($orderDir != $orderDirConf ) {
	$orderDirLink = '&dir=' . $orderDir;	//was '&order='
	} else {
	$orderDirLink = '';
}

$orderDirTxt = vmText::_ ('COM_VIRTUEMART_'.$orderDir);

$link = JRoute::_ ($fieldLink . $manufacturerTxt . $orderbyTxt . $orderDirLink,FALSE);

// full string list
if ($orderby == '') {
	$orderby = $orderbyCfg;
}
$orderby = strtoupper ($orderby);

$dotps = strrpos ($orderby, '.');
if ($dotps !== FALSE) {
	$prefix = substr ($orderby, 0, $dotps + 1);
	$orderby = substr ($orderby, $dotps + 1);
}
else {
	$prefix = '';
}
$orderby=str_replace(',','_',$orderby);
$orderByList = '<div class="orderlistcontainer"><div class="title">' . vmText::_ ('COM_VIRTUEMART_ORDERBY') . '</div><div class="activeOrder"><a title="' . $orderDirTxt . '" href="' . $link . '">' . vmText::_ ('COM_VIRTUEMART_SEARCH_ORDER_' . $orderby) . ' ' . $orderDirTxt . '</a></div>';
$orderByList .= $orderByLink . '</div>';

echo $orderByList;