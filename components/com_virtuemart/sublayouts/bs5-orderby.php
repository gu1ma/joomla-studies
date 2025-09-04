<?php

defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

$doc = Factory::getDocument();
$wa  = $doc->getWebAssetManager();

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

$dirLink = Route::_ ($fieldLink . $manufacturerTxt . $orderbyTxt . $orderDirLink,FALSE);

$wa->addInlineScript('
    jQuery(document).ready(function($){
        $(\'#vm-orderby-select\').change(function() {
            var orderbyUrl = $(this).val();
            window.location.href = orderbyUrl;
        });

        $(\'#vm-orderby-select\').next(\'button\').click(function(){
            window.location.href = "' . $dirLink . '";
        });
    });
');

// full string list
if ($orderby == '') {
    $orderby = $orderbyCfg;
}

$orderby = strtoupper ($orderby);
$dotps = strrpos ($orderby, '.');

if ($dotps !== FALSE) {
    $prefix = substr ($orderby, 0, $dotps + 1);
    $orderby = substr ($orderby, $dotps + 1);
} else {
    $prefix = '';
}

$orderby=str_replace(',','_',$orderby);

$ascIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-sort-down-alt" viewBox="0 0 16 16">
<path d="M3.5 3.5a.5.5 0 0 0-1 0v8.793l-1.146-1.147a.5.5 0 0 0-.708.708l2 1.999.007.007a.497.497 0 0 0 .7-.006l2-2a.5.5 0 0 0-.707-.708L3.5 12.293zm4 .5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1zm0 3a.5.5 0 0 1 0-1h3a.5.5 0 0 1 0 1zm0 3a.5.5 0 0 1 0-1h5a.5.5 0 0 1 0 1zM7 12.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 0-1h-7a.5.5 0 0 0-.5.5"/>
</svg>';

$descIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-sort-up" viewBox="0 0 16 16">
<path d="M3.5 12.5a.5.5 0 0 1-1 0V3.707L1.354 4.854a.5.5 0 1 1-.708-.708l2-1.999.007-.007a.5.5 0 0 1 .7.006l2 2a.5.5 0 1 1-.707.708L3.5 3.707zm3.5-9a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5M7.5 6a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
</svg>';
?>
<label class="form-label mb-1" for="vm-orderby-select">
	<?php echo vmText::_ ('COM_VIRTUEMART_ORDERBY'); ?>
</label>

<div class="input-group flex-nowrap">
	<select class="form-select mw-100" id="vm-orderby-select">
		<?php foreach ($fields as $field) : ?>
			<?php if ($field != $orderby) : ?>
				<?php
				$dotps = strrpos ($field, '.');

				if ($dotps !== FALSE)
				{
					$prefix = substr ($field, 0, $dotps + 1);
					$fieldWithoutPrefix = substr ($field, $dotps + 1);
				} else {
					$prefix = '';
					$fieldWithoutPrefix = $field;
				}

				$text = vmText::_ ('COM_VIRTUEMART_' . strtoupper (str_replace(array(',',' '),array('_',''),$fieldWithoutPrefix)));
				$field = explode('.',$field);

				if (isset($field[1]))
				{
					$field = $field[1];
				} else {
					$field = $field[0];
				}

				$link = Route::_ ($fieldLink . $manufacturerTxt . '&orderby=' . $field ,FALSE);
				?>
				<option value="<?php echo $link; ?>" <?php echo strtolower($orderby) == $field ? 'selected="selected"' : '' ?>><?php echo $text; ?></option>
			<?php endif; ?>
		<?php endforeach; ?>
	</select>

	<button class="btn btn-link text-dark py-0" type="button" title="<?php echo $orderDir; ?>" data-bs-toggle="tooltip">
		<?php echo $orderDir == 'ASC' ? $ascIcon : $descIcon; ?>
	</button>
</div>