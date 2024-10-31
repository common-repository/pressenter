<?php

namespace Pressenter;

trait ShortCode {
	public function doShortCodes($atts, $content = '', $tag) {
		ob_start();
		// $postType = $this->PowerdotSlides['slug'];
		$termsIDS = get_terms(PRESENTATION_SLUG, [
			'hide_empty'	=> true,
			'meta_query' => [[
				'key'       => PRESENTATION_SLUG.'_status',
				'value'     => 'publish',
				'compare'   => '='
			]],
			'fields'		=> 'ids',
			'orderby'		=> 'date',
			'order'			=> 'DESC'
		]);
		include $this->path . '/public/partials/sections/presentations-list.phtml';

		return ob_get_clean();
	} // doShortCodes

	public function getSlides($termID, $postStatus=[]) {
		$slides = get_posts([
			'post_type'   => SLIDE_SLUG,
			'post_status' => $postStatus,
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'tax_query'   => [[
				'taxonomy'         => PRESENTATION_SLUG,
				'field'            => 'term_id',
				'terms'            => $termID,
				'include_children' => false
			]]
		]);

		return $slides;
	} // getSlides

	public function getSlider() {
		$termID = (int)$_POST['termID'];
		$slides = $this->getSlides($termID, ['publish']);
		$presTitle = get_term($termID)->name;
		ob_start();
		include $this->path.'/public/partials/items/slider.phtml';
		$slider = ob_get_clean();
		wp_send_json_success($slider);
	} // getSlider

	public function sliderWrapper() {
		global $post;
		if((get_post_type($post->ID) == SLIDE_SLUG)) {
			$context = 'preview';
			$termID = '';
			$slides = [$post];
			$presTitle = __('Presentation title', $this->slug);
			echo '<div id="pd-slider-wrapper" class="pd-slide-preview show">';
			include $this->path . '/public/partials/items/slider.phtml';
			echo '</div>';
		} else {
			$context = 'presentations';
			echo '<div id="pd-slider-wrapper"></div>';
		}
	} // sliderWrapper

	public function getSlideBg($id=null) {
		$imgURL	= filter_var(get_the_post_thumbnail_url($id, 'full'), FILTER_SANITIZE_URL);
		$bgImg = (!empty($imgURL)) ? 'background-image: url('.$imgURL.');' : '';
		return $bgImg;
	} // getSlideBg

} // Presentation trait