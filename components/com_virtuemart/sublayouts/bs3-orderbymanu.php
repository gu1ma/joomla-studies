<?php

/** @var TYPE_NAME $viewData */
$manufacturers = $viewData['manufacturers'];
$fieldLink = $viewData['fieldLink'];
$orderbyTxt = $viewData['orderbyTxt'];
$orderDirLink = $viewData['orderDirLink'];

$manufacturerTxt = '';
$manufacturerLink = '';

$virtuemart_manufacturer_id = vRequest::getInt ('virtuemart_manufacturer_id', 0);

if (count ($manufacturers) > 1) { ?>
<select id="manulist" onchange="window.location.href=this.value">

	<?php if (!empty($virtuemart_manufacturer_id)) {
		$manufacturerTxt = '&virtuemart_manufacturer_id=' . $virtuemart_manufacturer_id;
	}

	if ($virtuemart_manufacturer_id > 0) {
		$allLink = str_replace($manufacturerTxt,$fieldLink,'');
		$allLink .= '&virtuemart_manufacturer_id=0&limitstart=0';
		echo '<option value="' . JRoute::_ ($allLink . $orderbyTxt . $orderDirLink , FALSE) . '">' . vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_ALL_MANUFACTURER') . '</option>';
	} else {
		echo '<option selected>' . vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_MANUFACTURER') . '</option>';
	}

	foreach ($manufacturers as $mf) {
		$l = str_replace($manufacturerTxt,'',$fieldLink) . '&virtuemart_manufacturer_id=' . $mf->virtuemart_manufacturer_id . $orderbyTxt . $orderDirLink . '&limitstart=0';

		$link = JRoute::_ ($l,FALSE);
		if ($mf->virtuemart_manufacturer_id != $virtuemart_manufacturer_id) {
			echo '<option value="' . str_replace('&tmpl=component','',$link) . '">' . $mf->mf_name . '</option>';
		}
		else {
			echo '<option selected>' . $mf->mf_name . '</option>';
		}
	} ?>

</select>
<?php } ?>
