<?php
/**
 *
 * @package VirtueMart
 * @subpackage Mustache template
 * @copyright Copyright (c) 2004 - Copyright (C) 2004 - 2022 Virtuemart Team. All rights reserved. VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * @version $Id: search_medias.php 10649 2022-05-05 14:29:44Z Milbo $
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>
<div id="search-media-template">
	{{#media}}
	<div class="">
		<div class="vmuikit-js-media-card uk-card uk-card-small uk-card-vm uk-padding-small">
			<div class="" uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_IMAGE_SELECT') ?> {{ file_title }}">
				<div class="uk-card-media">
					<div class="uk-inline-clip  uk-flex uk-flex-center uk-flex-middle">
						{{#file_url_thumb_img }}
						{{{file_url_thumb_img }}}
						{{/file_url_thumb_img }}
					</div>

				</div>
				<div class="uk-card-footer">
					<h6 class="uk-margin-remove uk-text-bold">{{ file_title }}</h6>
					<p class="uk-margin-remove uk-text-small">{{ file_description }}</p>
					<p class="uk-margin-remove uk-text-small uk-text-muted">{{ file_meta }}</p>
				</div>
			</div>
		</div>
	</div>
	{{/media}}
</div>










