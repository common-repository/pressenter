<?php

namespace Pressenter;

trait Presentation {
	public function getPresentationsList() {
		$id = (isset($_GET['id']))?intval($_GET['id']):0;
		$action  = (isset($_REQUEST['action']))?sanitize_text_field($_REQUEST['action']):'';
		if($action == -1) $action  = (isset($_REQUEST['action2']))?sanitize_text_field($_REQUEST['action2']):'';

		$page = (isset($_GET['paged']))?max(intval($_GET['paged']), 1):1;

		if($id && $action == 'edit') {
			$this->editPresentation($id, 'edit');
		} else {
			$ids = ($id)?[$id]:[];
			if(!$ids && isset($_POST['pressentation']))
				$ids = array_filter(array_map(function($id){return (int)$id;}, $_POST['pressentation']));
			if($action) $this->performBulkAction($ids, $action);

			$presentations = new PresentationsTable();
			$presentations->prepare_items();

			include $this->path."/admin/partials/presentations.phtml";
		}
	} // getPresentationsList

	public function savePresentation() {
		$nonce = sanitize_text_field($_POST['pressenterNonce']);

		if(wp_verify_nonce($nonce, 'savePresentation')) {
			$id = (isset($_POST['presentation-id']))?(int)$_POST['presentation-id']:0;
			$title = sanitize_text_field($_POST['title']);
			$description = sanitize_textarea_field($_POST['description']);

			if($title) {
				$args['description'] = $description;

				$response = null;
				if($id) {
					$args['name'] = $title;
					$status = sanitize_text_field($_POST['presentation-status']);
					switch($status) {
						case 'draft':
						case 'publish':
						case 'pending': break; // Nothing to do, the retrieved value is valid
						default: $status = 'draft';
					}
					$response = wp_update_term($id, PRESENTATION_SLUG, $args);
				} else {
					$args['slug'] = $this->createSlug($title);
					$response = wp_insert_term($title, PRESENTATION_SLUG, $args);
				}

				if(is_wp_error($response)) wp_send_json_error($response);

				wp_send_json_success(['ID' => $response['term_id']]);
			} else wp_send_json_error(['err' => -2, 'desc' => 'Invalid SLUG']);
		} else wp_send_json_error(['err' => -1, 'desc' => 'Invalid NONCE']);
	} // savePresentation

	private function removePresentation($id){
		wp_delete_term($id, PRESENTATION_SLUG);
	} // removePresentation

	public function addPresentationMeta($termID) {
    add_term_meta($termID, PRESENTATION_SLUG.'_status', 'draft');
    add_term_meta($termID, PRESENTATION_SLUG.'_timestamp', time());
    add_term_meta($termID, PRESENTATION_SLUG.'_author', get_current_user_id());
	} // addPresentationMeta

	public function updatePresentationMeta($termID, $tt_id, $taxonomy) {
		if(PRESENTATION_SLUG === $taxonomy) {
			$status = sanitize_text_field($_POST['presentation-status']);
			switch($status) {
				case 'draft':
				case 'publish':
				case 'pending': break; // Nothing to do, the retrieved value is valid
				default: $status = 'draft';
			}

			update_term_meta($termID, PRESENTATION_SLUG.'_status', $status);
			update_term_meta($termID, PRESENTATION_SLUG.'_author', get_current_user_id());
			update_term_meta($termID, PRESENTATION_SLUG.'_timestamp', time());
		}
	} // addPresentationMeta

	public function editPresentation($id=0, $action='new') {
		$id = (int)$id; // trick to force menu callback to pass 0 to create a new presentation
		$template = 'new';
		$slides = null;

		if($id) {
			$template = 'edit';
			$action  = (isset($_GET['action']))?sanitize_text_field($_GET['action']):'';
			$presentation = get_term($id);
			$meta = get_term_meta($id, '', true);
			$status = (isset($meta['pressentation_status'][0]))?$meta['pressentation_status'][0]:'draft';
			if($status === 'publish') $presentation->noDraft = 'checked="checked"';
			$presentation->author = $meta['pressentation_author'][0];
			$presentation->timestamp = $meta['pressentation_timestamp'][0];

			$slides = new SlidesTable($id);
			$slides->prepare_items();
		}

		include $this->path."/admin/partials/$template-presentation.phtml";
	} // editPresentationPage

	private function createSlug(&$title) {
		$base =
		$slug = sanitize_title($title);

		if($slug) {
			$i=2;
			while($i) {
				if(get_term_by('slug', $slug, PRESENTATION_SLUG, ARRAY_N)) {
					$slug = "$base-$i";
					$i++;
				} else {
					$i--;
					if($i>1) $title .= " ($i)";
					$i=0;
				}
			}
		}

		return $slug;
	} // createSlug

	private function performBulkAction($ids, $action) {

		foreach($ids as $id) {
			switch($action) {
				case 'delete':
					$this->removePresentation($id);
				break;
				case 'draft':
				case 'publish':
				case 'pending':
				case 'trash':
					$this->changePresentationStatus($id, $action); break;
				case 'restore':
					$this->changePresentationStatus($id, 'draft'); break;
			}
		}
	} // performBulkAction

	private function changePresentationStatus($id=0, $status) {

		update_term_meta($id, PRESENTATION_SLUG.'_status', $status);
	} // changePresentationStatus

	public function getSlideTemplate($single) {
		global $post;

		if($post->post_type == SLIDE_SLUG)
			return $this->path.'/public/partials/slide.phtml';

		return $single;
	} // getSlideTemplate
} // Presentation trait