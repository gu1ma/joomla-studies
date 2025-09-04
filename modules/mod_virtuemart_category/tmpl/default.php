<?php // no direct access
defined('_JEXEC') or die('Restricted access');


?>

<ul class="VMmenu<?php echo $class_sfx ?>" id="<?php echo "VMmenu".$ID ?>">
	<?php foreach ($categories as $category) {
		$active_menu = 'class="VmClose"';
		$caturl = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$category->virtuemart_category_id);
		$cattext = $category->category_name;
		if (in_array( $category->virtuemart_category_id, $parentCategories)) {
			$active_menu = 'class="VmOpen"';
		} ?>

	<li <?php echo $active_menu ?>>
		<div>
			<?php
			echo JHTML::link($caturl, $cattext);
			if (!empty($category->childs)) { ?>
				<span class="VmArrowdown"> </span>
				<?php
			} ?>
		</div>
		<?php if (!empty($category->childs)) { ?>
		<ul class="menu<?php echo $class_sfx; ?>">
			<?php
			foreach ($category->childs as $child) {
				$active_child_menu = 'class="VmClose"';
				$caturl = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$child->virtuemart_category_id);
				$cattext = vmText::_($child->category_name);
				if ($child->virtuemart_category_id == $active_category_id) {
					$active_child_menu = 'class="VmOpen"';
				} ?>
				<li <?php echo $active_child_menu; ?>>
					<div ><?php echo JHTML::link($caturl, $cattext); ?></div>
				<?php
				if(!empty($child->childs)){ ?>
					<ul class="menu<?php echo $class_sfx; ?>">
						<?php
						foreach ($child->childs as $child1) {
							$active_child_menu = 'class="VmClose"';
							$caturl = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$child1->virtuemart_category_id);
							$cattext = vmText::_($child1->category_name);
							if ($child1->virtuemart_category_id == $active_category_id) {
								$active_child_menu = 'class="VmOpen"';
							} ?>
							<li <?php echo $active_child_menu; ?>>
								<div ><?php echo JHTML::link($caturl, $cattext); ?></div>
							</li>
							<?php
						} ?>
					</ul>
				<?php } ?>
				</li>
			<?php
			} ?>
		</ul>
		<?php } ?>
	</li>
	<?php } ?>
</ul>
