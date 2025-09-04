<?php

defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

$doc = Factory::getDocument();
$wa  = $doc->getWebAssetManager();

$wa->addInlineScript('
    jQuery(document).ready(function($){
        $(\'#vm-orderby-select-mf\').change(function() {
            var orderbyMf = $(this).val();
            window.location.href = orderbyMf;
        });
    });
');

/** @var TYPE_NAME $viewData */
$manufacturers = $viewData['manufacturers'];
$fieldLink = $viewData['fieldLink'];
$orderbyTxt = $viewData['orderbyTxt'];
$orderDirLink = $viewData['orderDirLink'];

$manufacturerTxt = '';
$manufacturerLink = '';

$virtuemart_manufacturer_id = vRequest::getInt ('virtuemart_manufacturer_id', 0);
?>
<label class="form-label mb-1" for="vm-orderby-select-mf"><?php echo vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_MANUFACTURER'); ?></label>

<select class="form-select mw-100" id="vm-orderby-select-mf">
	<?php if (count ($manufacturers) > 1) : ?>
		<?php
		if (!empty($virtuemart_manufacturer_id))
		{
			$manufacturerTxt = '&virtuemart_manufacturer_id=' . $virtuemart_manufacturer_id;
		}

		$allLink = str_replace($manufacturerTxt,$fieldLink,'');
		$allLink .= '&virtuemart_manufacturer_id=0';
		?>

		<?php foreach ($manufacturers as $mf) : ?>
			<?php
			$l = str_replace($manufacturerTxt,'',$fieldLink) . '&virtuemart_manufacturer_id=' . $mf->virtuemart_manufacturer_id . $orderbyTxt . $orderDirLink . '&limitstart=0';
			$link = Route::_ ($l,FALSE);
			?>
			<option <?php echo $mf->virtuemart_manufacturer_id != $virtuemart_manufacturer_id ? 'value="' . $link . '"' : 'selected'; ?>><?php echo $mf->mf_name ?></option>
		<?php endforeach; ?>

		<option value="<?php echo Route::_ ($allLink . $orderbyTxt . $orderDirLink , FALSE) ?>" <?php echo $virtuemart_manufacturer_id == 0 ? 'selected="selected"' : ''; ?>><?php echo vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_ALL_MANUFACTURER'); ?></option>
	<?php else : ?>
		<option selected><?php echo $manufacturers[0]->mf_name; ?></option>
	<?php endif; ?>
</select>