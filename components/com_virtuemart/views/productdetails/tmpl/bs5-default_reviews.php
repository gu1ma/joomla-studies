<?php

/**
 *
 * Show the product details page
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default_reviews.php 10854 2023-05-24 15:09:40Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

// Customer Reviews
$review_editable = true;
$maxrating = VmConfig::get( 'vm_maximum_rating_scale', 5 );
$ratingsShow = VmConfig::get( 'vm_num_ratings_show', 3 );

$emptyStar = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
<path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"/>
</svg>';

$star = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
</svg>';

$commentIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-right-text" viewBox="0 0 16 16">
					<path d="M2 1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h9.586a2 2 0 0 1 1.414.586l2 2V2a1 1 0 0 0-1-1zm12-1a2 2 0 0 1 2 2v12.793a.5.5 0 0 1-.854.353l-2.853-2.853a1 1 0 0 0-.707-.293H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2z"/>
					<path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
				</svg>';
?>

<?php if ($this->allowRating || $this->allowReview || $this->showRating || $this->showReview) : ?>
	<div class="customer-reviews mb-5">
		<?php
			if ($this->rating_reviews) {
				foreach( $this->rating_reviews as $review ) {
					/* Check if user already commented */
					if ($review->created_by == $this->user->id && !$review->review_editable) {
						$review_editable = false;
					}
				}
			}
		?>

		<?php if ($this->showReview) : ?>
			<h2 class="vm-section-title pb-2 mb-3 border-bottom"><?php echo vmText::_ ('COM_VIRTUEMART_REVIEWS') ?></h2>
		<?php endif; ?>

		<?php if ($this->allowRating or $this->allowReview) : // Write review ?>
			<?php if ($review_editable) : ?>
				<form method="post"
					action="<?php echo Route::_( 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$this->product->virtuemart_product_id.'&virtuemart_category_id='.$this->product->virtuemart_category_id, FALSE ); ?>"
					name="reviewForm" id="reviewform">

					<div class="row">
						 <div class="col-md-6">
							<?php if ($this->allowRating and $review_editable) : ?>
								<p class="lead mb-2"><?php echo vmText::_( 'COM_VIRTUEMART_WRITE_REVIEW' ); ?></p>

								<?php if(count($this->rating_reviews) == 0) : ?>
									<p><?php echo vmText::_( 'COM_VIRTUEMART_WRITE_FIRST_REVIEW' ) ?></p>
								<?php endif; ?>

								<p class="alert alert-info p-2 small"><?php echo vmText::_( 'COM_VIRTUEMART_RATING_FIRST_RATE' ) ?></p>

								<div class="mb-3 mb-md-5">
									<div class="form-check mb-2">
										<input class="form-check-input" type="radio" name="vote" id="star0" value="0">
										<label class="form-check-label" for="star0">0 <?php echo $emptyStar . $emptyStar . $emptyStar . $emptyStar . $emptyStar; ?></label>
									</div>
									<div class="form-check mb-2">
										<input class="form-check-input" type="radio" name="vote" id="star1" value="1">
										<label class="form-check-label" for="star1">1 <?php echo $star . $emptyStar . $emptyStar . $emptyStar . $emptyStar; ?></label>
									</div>
									<div class="form-check mb-2">
										<input class="form-check-input" type="radio" name="vote" id="star2" value="2">
										<label class="form-check-label" for="star2">2 <?php echo $star . $star . $emptyStar . $emptyStar . $emptyStar; ?></label>
									</div>
									<div class="form-check mb-2">
										<input class="form-check-input" type="radio" name="vote" id="star3" value="3">
										<label class="form-check-label" for="star3">3 <?php echo $star . $star . $star . $emptyStar . $emptyStar; ?></label>
									</div>
									<div class="form-check mb-2">
										<input class="form-check-input" type="radio" name="vote" id="star4" value="4">
										<label class="form-check-label" for="star4">4 <?php echo $star . $star . $star . $star . $emptyStar; ?></label>
									</div>
									<div class="form-check">
										<input class="form-check-input" type="radio" name="vote" id="star5" value="5">
										<label class="form-check-label" for="star5">5 <?php echo $star . $star . $star . $star . $star; ?></label>
									</div>
								</div>
							<?php endif; ?>
						 </div>
						 <div class="col-md-6 mb-4 mb-md-0">
							<?php if ($this->allowReview and $review_editable) : // Writing A Review ?>
								<div class="write-reviews my-md-5">
									<div class="mb-3">
										<label class="form-label" for="comment"><?php echo vmText::sprintf( 'COM_VIRTUEMART_REVIEW_COMMENT', VmConfig::get( 'reviews_minimum_comment_length', 100 ), VmConfig::get( 'reviews_maximum_comment_length', 2000 ) ); ?></label>
										<textarea
												class="form-control"
												id="comment"
												name="comment"
												onblur="refresh_counter();"
												onfocus="refresh_counter();"
												onkeyup="refresh_counter();"
												rows="8"
												cols="60"><?php echo !empty($this->review->comment) ? $this->review->comment : ''; ?></textarea>
									</div>

									<div class="d-flex align-items-end">
										<div class="col-8">
											<button class="btn btn-primary" type="submit" onclick="return( check_reviewform());"><?php echo vmText::_( 'COM_VIRTUEMART_REVIEW_SUBMIT' ) ?></button>
										</div>
										<div class="col-4 text-end">
											<label class="form-label small mb-1" for="counter"><?php echo vmText::_( 'COM_VIRTUEMART_REVIEW_COUNT' ) ?></label>
											<input class="form-control" id="counter" type="text" value="0" size="4" name="counter" maxlength="4" disabled />
										</div>
									</div>
								</div>
							<?php elseif ($review_editable and $this->allowRating) : ?>
								<input class="highlight-button" type="submit" name="submit_review" value="<?php echo vmText::_( 'COM_VIRTUEMART_REVIEW_SUBMIT' ) ?>"/>
							<?php endif; ?>
						 </div>
					</div>

					<input type="hidden" name="virtuemart_product_id" value="<?php echo $this->product->virtuemart_product_id; ?>"/>
					<input type="hidden" name="option" value="com_virtuemart"/>
					<input type="hidden" name="virtuemart_category_id" value="<?php echo vRequest::getInt( 'virtuemart_category_id' ); ?>"/>
					<input type="hidden" name="virtuemart_rating_review_id" value="0"/>
					<input type="hidden" name="task" value="review"/>
				</form>
			<?php elseif (!$review_editable) : ?>
				<p><?php echo '<strong>'.vmText::_( 'COM_VIRTUEMART_DEAR' ).$this->user->name.',</strong><br>' .  vmText::_( 'COM_VIRTUEMART_REVIEW_ALREADYDONE' ); ?></p>
			<?php endif; ?>

			<?php // Show Review Length While Your Are Writing
				$reviewJavascript = "
						function check_reviewform() {

							var form = document.getElementById('reviewform');

							if (form.comment.value.length < ".VmConfig::get( 'reviews_minimum_comment_length', 100 ).") {
								alert('".addslashes( vmText::sprintf( 'COM_VIRTUEMART_REVIEW_ERR_COMMENT1_JS', VmConfig::get( 'reviews_minimum_comment_length', 100 ) ) )."');
								return false;
							}
							else if (form.comment.value.length > ".VmConfig::get( 'reviews_maximum_comment_length', 3800 ).") {
								alert('".addslashes( vmText::sprintf( 'COM_VIRTUEMART_REVIEW_ERR_COMMENT2_JS', VmConfig::get( 'reviews_maximum_comment_length', 3800 ) ) )."');
								return false;
							}
							else {
								return true;
							}
						}

						function refresh_counter() {
							var form = document.getElementById('reviewform');
							form.counter.value= form.comment.value.length;
						}
						";

				vmJsApi::addJScript( 'check_reviewform', $reviewJavascript );
			?>
		<?php endif; // Write review ?>

		<?php if ($this->showReview) : // Show review ?>
			<div class="list-reviews">
				<?php
				$i = 0;
				$reviews_published = 0;
				?>
				<?php if ($this->rating_reviews) : ?>
					<?php foreach ($this->rating_reviews as $index => $review) : ?>
						<?php // Loop through all reviews
						if (!empty($this->rating_reviews) && $review->published) {
							$reviews_published++;
							$rating = $this->rating_reviews[$index]->review_rating;
							$ratingwidth = $rating * 16;
							?>
							<div class="row pb-3 mx-0 mb-3 border-bottom">
								<div class="d-flex align-items-end px-0 py-3">
									<span class="customer-name text-nowrap"><?php echo $commentIcon . $review->customer ?></span>
									<span class="date ms-4 text-secondary small"><?php echo HTMLHelper::date ($review->created_on, vmText::_ ('DATE_FORMAT_LC')); ?></span>
									<div class="vm-ratingbox-container d-inline-block position-relative ms-auto">
										<div class="vm-ratingbox-unrated d-inline-block text-nowrap">
											<?php
											for ($i=0; $i<5; $i++)
											{
												echo $emptyStar;
											}
											?>
										</div>
										<div class="vm-ratingbox-rated d-inline-block" title="<?php echo (vmText::_("COM_VIRTUEMART_RATING_TITLE") . ' ' . round($rating, 2) . '/' . $maxrating) ?>" data-bs-toggle="tooltip">
											<div class="vm-ratingbox-bar overflow-x-hidden text-nowrap" style="width:<?php echo $ratingwidth.'px'; ?>">
									 			<?php
												for ($i=0; $i<5; $i++)
												{
													echo $star;
												}
												?>
											</div>
										</div>
									</div>
								</div>
								<blockquote class="bg-light p-3"><?php echo $review->comment; ?></blockquote>
							</div>
						<?php
						}

						if ($i == $ratingsShow && !$this->showall) {
							/* Show all reviews ? */
							if ($reviews_published >= $ratingsShow) {
								$attribute = array('class'=> 'btn btn-primary');
								echo HTMLHelper::link ($this->more_reviews, vmText::_ ('COM_VIRTUEMART_MORE_REVIEWS'), $attribute);
							}

							break;
						}

						$i++;
						?>
					<?php endforeach; ?>
				<?php else : // "There are no reviews for this product"  ?>
					<p class="vm-no-reviews"><?php echo vmText::_ ('COM_VIRTUEMART_NO_REVIEWS') ?></p>
				<?php endif;?>
			</div>
		<?php endif; // Show review ?>
	</div>
<?php endif; ?>