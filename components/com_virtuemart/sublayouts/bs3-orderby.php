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

if($orderDir == 'ASC'){
	$orderDir = 'DESC';
	$orderDirTxt = 'Z-A';
} else {
	$orderDir = 'ASC';
	$orderDirTxt = 'A-Z';
}

// full string list
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

if (count ($fields) > 1) { ?>
<div class="input-group">
	<select id="orderBy" onchange="window.location.href=this.value">
	<?php
		foreach ($fields as $field) {
			$dotps = strrpos ($field, '.');
			if ($dotps !== FALSE) {
				$prefix = substr ($field, 0, $dotps + 1);
				$fieldWithoutPrefix = substr ($field, $dotps + 1);
			}
			else {
				$prefix = '';
				$fieldWithoutPrefix = $field;
			}

			$selected = ($fieldWithoutPrefix == strtolower($orderby) ? ' selected' : '');

			$text = vmText::_ ('COM_VIRTUEMART_' . strtoupper (str_replace(array(',',' '),array('_',''),$fieldWithoutPrefix)));

			$field = explode('.',$field);
			if(isset($field[1])){
				$field = $field[1];
			} else {
				$field = $field[0];
			}
			$link = JRoute::_ ($fieldLink . $manufacturerTxt . '&orderby=' . $field ,FALSE);
			$btnLink = JRoute::_ ($fieldLink . $manufacturerTxt . '&orderby=' . $field . '&dir=' . $orderDir ,FALSE);

			if ( empty($selected) ) {
				echo '<option value="' . str_replace('&tmpl=component','',$link) . '">' . $text . '</option>';
			} else {
			   	echo '<option' . $selected . '>' . $text . '</option>';
			}
		}
	?>
	</select>
	<span class="input-group-btn">
		<a href="<?php echo str_replace('&tmpl=component','',$btnLink); ?>" class="btn btn-primary hasTooltip" type="button" title="<?php echo vmText::_('COM_VIRTUEMART_ORDERING'); ?>"><?php echo $orderDirTxt; ?></a>
	</span>
</div>
<?php }