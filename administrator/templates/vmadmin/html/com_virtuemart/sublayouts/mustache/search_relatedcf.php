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
 * @version $Id: search_relatedcf.php 10649 2022-05-05 14:29:44Z Milbo $
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>
<div id="search-relatedcf-template">
	{{#relatedData}}
	<div class="">
		<div class="vmuikit-js-cf-card uk-card uk-card-small uk-card-vm">
			<div uk-tooltip="<?php echo vmText::_('COM_VIRTUEMART_RELATEDCF_SELECT') ?>">
				<div class="vmuikit-js-cf-card-body uk-card-body">
					{{{hiddenHTML}}}
					{{{displayHTML}}}
				</div>
			</div>
		</div>
	</div>
	{{/relatedData}}
</div>









