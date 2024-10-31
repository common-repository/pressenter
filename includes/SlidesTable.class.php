<?php
namespace Pressenter;

class SlidesTable extends TableView {
	private $presentationID;
	function __construct(int $id) {
		$this->presentationID = $id;
		parent::__construct(PRESENTATION_SLUG, [
			'singular'  => __('presentation', 'pressenter'),
			'plural'    => __('presentations', 'pressenter'),
			'ajax'      => false]
		);
	} // __construct

	public function get_columns() {
		return [
			'cb'      => '<input type="checkbox" />',
			'title'   => __('Title', 'pressenter'),
			'author'  => __('Author', 'pressenter'),
			'date'    => __('Date', 'pressenter')
		];
	} // get_columns

	public function get_sortable_columns() {
		$sortable_columns = [
			'title'    => ['title', true],
			'date'     => ['date', true]
		];

		return $sortable_columns;
 } // get_sortable_columns

 	public function column_title($item) {
		$actions = $this->initActions($item['id']);
		$editURL  = add_query_arg(['post' => $item['id'], 'action' => 'edit'], admin_url('post.php'));
		$title = '<strong><a class="row-title" href="%1$s"">%2$s</a>%3$s</strong> %4$s';

		$statusDesc = ' — <span class="post-state">%s</span>';
		switch($item['status']) {
			case 'draft':
				$statusDesc = sprintf($statusDesc, __('Draft', 'pressenter'));
			break;
			case 'pending':
				$statusDesc = sprintf($statusDesc, __('Pending review', 'pressenter'));
			break;
			default: $statusDesc = '';
		}

		return sprintf($title, $editURL, $item['title'], $statusDesc, $this->row_actions($actions));
	} // column_title

	private function initActions($id) {
		$return = [];
		$args = ['page' => PRESENTATION_SLUG, 'id' => $id];

		switch($this->shownStatus) {
			case 'all':
			case 'draft':
			case 'pending':
			case 'publish':
				$trashURL = wp_nonce_url(add_query_arg($args+['action' => 'trash']));
				$editURL  = add_query_arg(['post' => $id, 'action' => 'edit'], admin_url('post.php'));

				$return = [
					'edit'  => '<a href="'.$editURL.'">'.__('Edit', 'pressenter').'</a>',
					'trash' => '<a href="'.$trashURL.'">'._x('Trash', 'actions', 'pressenter').'</a>'
				];
			break;
			case 'trash':
				$restoreURL = wp_nonce_url(add_query_arg($args+['action' => 'restore']));
				$deleteURL = wp_nonce_url(add_query_arg($args+['action' => 'delete']));

				$return = [
					'restore'  => '<a href="'.$restoreURL.'">'.__('Restore', 'pressenter').'</a>',
					'delete' => '<a href="'.$deleteURL.'">'.__('Delete Permanently', 'pressenter').'</a>'
				];
			break;
		}

		return $return;
	}

	public function column_date($item) {
		$timeDiff = time()-$item['date'];
		$extWhen = wp_date(__('Y/m/d g:i:s a', 'pressenter'), $item['date']);

		if($item['date'] && $timeDiff>0 && $timeDiff<DAY_IN_SECONDS)
			$when = sprintf(__('%s ago'), human_time_diff($item['date']));
		else
			$when = wp_date(__('Y/m/d', 'pressenter'), $item['date']);

		$status = ($item['status'] === 'publish')?__('Published', 'pressenter'):__('Last Modified', 'pressenter');

		return  $status.'<br /><span title="'.$extWhen.'">'.$when.'</span>';
	} // column_date


	public function tableData($search, $order=null) {
		global $wpdb;

		$args = [
			'post_type' => SLIDE_SLUG,
			'tax_query' => [[
				'taxonomy' => PRESENTATION_SLUG,
				'field'    => 'term_id',
				'terms'    => $this->presentationID,
			]]
		];

		$query = new \WP_Query($args);
		$slides = $query->get_posts();

		$this->count =  $this->enumerateSlides();

		$data = null;

		foreach ($slides as $slide) {
			$data[] = [
				'id'          => $slide->ID,
				'slug'        => $slide->post_name,
				'title'       => $slide->post_title,
				'description' => $slide->post_excerpt,
				'status'      => $slide->post_status,
				'date'        => strtotime($slide->post_date),
				'author'      => get_the_author_meta('display_name', $slide->post_author)
			];
		}

		return $data;
	} // tableData

	public function extra_tablenav($which) {
		global $wpdb;

		if($which == "top") {
			?>
			<div class="alignleft actions">
				<label for="filter-by-date" class="screen-reader-text">Filtra per data</label>
			<select name="m" id="filter-by-date">
				<option selected="selected" value="0">Tutte le date</option>
			<option value="202004">Aprile 2020</option>
			</select>
			<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filtra">		</div>
			<?php
		}
	} // extra_tablenav

	private function enumerateSlides() {
		global $wpdb;

		$sql  = "SELECT post_status status, COUNT(*) count FROM {$wpdb->posts} ";
		$sql .= "LEFT JOIN {$wpdb->term_relationships} ON object_id = ID ";
		$sql .= 'WHERE term_taxonomy_id = %d AND post_status != "auto-draft" ';
		$sql .= 'GROUP BY post_status';

		$counting = $wpdb->get_results($wpdb->prepare($sql, $this->presentationID));

		$count = new \StdClass();
		$count->all = 0;

		foreach($counting as $item) {
			$count->{$item->status} = $item->count;
			if($item->status != 'trash')
				$count->all += $item->count;
		}

		return $count;
	} // enumerateSlides
} // class SlidesTable