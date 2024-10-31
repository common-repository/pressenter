<?php
/**
 * @file
 * This is the entry class of al 10100's knowledge
 *
 * @author Michele Roncaglione Tet
 * @version 1.0.0
 */

namespace Pressenter;

const PRESENTATION_SLUG = 'pressentation';
const SLIDE_SLUG = PRESENTATION_SLUG.'-slide';

class Main {
	use Presentation, ShortCode;

	public $slug;
	public $version;
	public $path;
	public $URL;
	public $nonce;

	public function __construct($slug=null) {
		$this->path = dirname(__DIR__);
		list('slug' => $this->slug, 'version' => $this->version) =
		get_file_data($this->path.DIRECTORY_SEPARATOR.'plugin.php', [
			'slug' => 'Text Domain',
			'version' => 'Version'
		]);

		$this->URL = trailingslashit(plugins_url($this->slug));

		$this->loadTextdomain();
	} // __construct

	public function addAdminJS($hook) {
		wp_enqueue_script($this->slug.'-admin-js', $this->URL.'/admin/js/main.js', ['jquery', 'wp-util'], $this->version, true);
	}

	public function init() {
		// We need an init method, instead the constructor because wordpress
		// is not available at the constructor time.
		$this->nonce = wp_create_nonce($this->slug);

		register_post_type(SLIDE_SLUG, [
			'public' => true,
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_in_menu' => false,
			'publicly_queryable' => true,
			'has_archive' => true,
			'show_in_rest' => true
		]);
		register_taxonomy(PRESENTATION_SLUG, SLIDE_SLUG);
	} // init

	public function activate() {
		$this->editCaps('add');
		$this->checkUpdates();
	} // activate

	public function deactivate() {
		$this->editCaps('remove');
		delete_option($this->slug);
	} // deactivate

	private function checkUpdates() {
		if($this->version !== get_option($this->slug)) {
			$this->update();
			update_option($this->slug, $this->version);
		}
	} // checkUpdates

	private function update() {}

	private function editCaps($action) {
		$roles = ['administrator', 'editor', 'author', 'contributor'];
		$action .= '_cap';

		foreach($roles as $roleLabel) {
			$role = get_role($roleLabel);

			if($roleLabel !== 'contributor') {
				$role->$action('edit_'.PRESENTATION_SLUG);
				$role->$action('delete_'.PRESENTATION_SLUG);
				$role->$action('edit_others_'.PRESENTATION_SLUG);
				$role->$action('delete_others_'.PRESENTATION_SLUG);
			}

			$role->$action('edit_'.SLIDE_SLUG);
			$role->$action('delete_'.SLIDE_SLUG);
		}
	}  //editCaps

	public function editAdminMenu() {
		global $pagenow;

		$type = $presentationID = null;

		if('post.php' === $pagenow || 'post-new.php' === $pagenow) {
			$id = (isset($_REQUEST['post']))?(int)$_REQUEST['post']:0;

			$type = (isset($_REQUEST['post_type']))?sanitize_text_field($_REQUEST['post_type']):null;
			$presentationID = (isset($_REQUEST['parent']))?(int)$_REQUEST['parent']:0;

			if($id) {
				$post = get_post($id);
				if($post) {
					$type = $post->post_type;
					$terms = wp_get_object_terms($id, 'pressentation');
					if(!empty($terms) && !is_wp_error($terms))
						$presentationID = (int)$terms[0]->term_id;
				}
			}
		}

		add_menu_page(
			__('PressEnter', 'pressenter'),
			__('PressEnter', 'pressenter'),
			'edit_'.SLIDE_SLUG,
			PRESENTATION_SLUG,
			[$this, 'getPresentationsList'],
			'dashicons-analytics',
			21
		);

		add_submenu_page(
			PRESENTATION_SLUG,
			__('Presentations', 'pressenter'),
			__('Presentations', 'pressenter'),
			'edit_'.SLIDE_SLUG,
			PRESENTATION_SLUG
		);

		if(SLIDE_SLUG == $type && $presentationID) {
			add_submenu_page(
				PRESENTATION_SLUG,
				__('Back to slides', 'pressenter'),
				__('Back to slides', 'pressenter'),
				'edit_'.SLIDE_SLUG,
				add_query_arg([
					'page' => PRESENTATION_SLUG,
					'id' => $presentationID,
					'action' => 'edit',
				], admin_url('admin.php'))
			);
		} else {
			add_submenu_page(
				PRESENTATION_SLUG,
				_x('Add new', 'presentation', 'pressenter'),
				_x('Add new', 'presentation', 'pressenter'),
				'edit_'.PRESENTATION_SLUG,
				'new-'.PRESENTATION_SLUG,
				[$this, 'editPresentation']
			);
		}
	}

	public function loadTextdomain() {
		load_plugin_textdomain($this->slug, false, $this->slug.'/languages/');
	} // loadTextdomain

	public function addJQuery($mods=[]) {
		wp_enqueue_script('jquery'); //already registered by wordpress
		foreach($mods as $mod) wp_enqueue_script("jquery-$mod", '', array('jquery'));
	} // addJQuery

	public function addJS() {
	$this->addJQuery([
		'ui-core',
		'ui-widget',
		'ui-mouse',
		'ui-accordion',
		'ui-autocomplete',
		'ui-sortable',
		'ui-resizable',
		'ui-slider'
		]);
		wp_enqueue_script($this->slug.'-js', $this->URL.'/public/js/main.js', ['jquery', 'wp-util'], $this->version);
	}

	public function addStyle() {
		wp_enqueue_style('dashicons');
		wp_enqueue_style($this->slug, $this->URL.'/public/css/style.css', false, '');
	}

	public function addAdminStyle() {
		wp_enqueue_style($this->slug.'-admin', $this->URL.'/admin/css/style.css', false, '');
	}

	public function goToPresentationPage() {
		if(wp_doing_ajax()) return;

		$screen = get_current_screen();

		if($screen->id == 'edit-pressentation-slide') {
			$url = parse_url(wp_get_referer());

			if(is_array($url) && isset($url['query'])) {
				parse_str($url['query'], $vars);

				$id = 0;
				if(!empty($vars)) {
					if(isset($vars['post']))  {
						if($postID = (int)$vars['post']){
							$terms = wp_get_object_terms($postID,  'pressentation');
							if(!empty($terms) && !is_wp_error($terms)) $id = (int)$terms[0]->term_id;
						}
					} elseif(isset($vars['parent']))  {
						$id = (int)$vars['parent'];
					}
				}

				if($id > 0) {
					wp_safe_redirect(
						add_query_arg([
							'page' => PRESENTATION_SLUG,
							'id' => $id,
							'action' => 'edit',
						], admin_url('admin.php'))
					); exit; //exit call is mandatory to stop WP loading.
				}
			}
		}
	} // goToPresentationPage

	public function bindSlide($post_ID, $post, $update) {
		if(!$update) {
			$presentationID = (int)$_GET['parent'];
			wp_set_object_terms($post_ID, $presentationID, \Pressenter\PRESENTATION_SLUG);
		}
	} // bindSlide
} // class