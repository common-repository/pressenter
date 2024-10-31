<?php
namespace Pressenter;

class PresentationsTable extends TableView {
	function __construct() {
		parent::__construct(PRESENTATION_SLUG, [
			'singular'  => __('presentation', 'pressenter'),
			'plural'    => __('presentations', 'pressenter'),
			'ajax'      => false]
		);
	} // __construct

	public function get_columns() {
		$slidesHeader  = '<span class="dashicons-before dashicons-images-alt2" title="'.__('Slides', 'pressenter').'"></span>';

		return [
			'cb'      => '<input type="checkbox" />',
			'title'   => __('Title', 'pressenter'),
			'author'  => __('Author', 'pressenter'),
			'slides'  => $slidesHeader,
			'date'    => __('Date', 'pressenter')
		];
	} // get_columns

	public function get_sortable_columns() {
		$sortable_columns = [
			'title'    => ['title', true],
			'date'     => ['date', true],
			'slides'   => ['slides', true]
		];

		return $sortable_columns;
 } // get_sortable_columns

 	public function column_title($item) {
		$actions = $this->initActions($item['id']);
		$title = '';

		if($item['status'] == 'trash') {
			$title = $item['title'];
		} else {
			$title  = '<a class="row-title" href="';
			$title .= add_query_arg(['page' => PRESENTATION_SLUG, 'id' => $item['id'], 'action' => 'edit']);
			$title .= '">'.$item['title'].'</a>';
		}

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

		return sprintf('<strong>%1$s%2$s</strong> %3$s', $title, $statusDesc, $this->row_actions($actions));
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
				$editURL  = add_query_arg($args+['action' => 'edit']);

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

		$sql  = "SELECT DISTINCT t0.term_id id, slug, name, description, count, ";
		$sql .= "t1.meta_value status, t3.meta_value date, {$wpdb->users}.display_name author ";
		$sql .= "FROM {$wpdb->terms} t0 ";
		$sql .= "LEFT JOIN {$wpdb->term_taxonomy} USING(term_id) ";
		$sql .= "LEFT JOIN {$wpdb->termmeta} t1 ON t1.term_id =t0.term_id AND t1.meta_key = '".PRESENTATION_SLUG.'_status\' ';
		$sql .= "LEFT JOIN {$wpdb->termmeta} t2 ON t2.term_id =t0.term_id AND t2.meta_key = '".PRESENTATION_SLUG.'_author\' ';
		$sql .= "LEFT JOIN {$wpdb->termmeta} t3 ON t3.term_id =t0.term_id AND t3.meta_key = '".PRESENTATION_SLUG.'_timestamp\' ';
		$sql .= "LEFT JOIN {$wpdb->users} ON ID =t2.meta_value";

		$where = 'WHERE taxonomy = %s ';
		if($this->shownStatus == 'all') $where .= 'AND t1.meta_value != "trash" ';
		else $where .= "AND t1.meta_value = '{$this->shownStatus}' ";
		if($search) $where .= " AND (name LIKE '%$search%' OR description LIKE '%$search%') ";

		$limit = 'LIMIT '.($this->get_pagenum()-1)*self::PER_PAGE.', '.self::PER_PAGE;

		if(is_array($order)) {
			if($order['by'] == 'title') $order['by'] = 'name';
			$sort = 'ORDER BY '.$order['by'].' '.$order['way'];
		}

		$this->count =  $this->enumeratePresentations();

		$data = null;

		if($this->count->all || $this->shownStatus == 'trash') {
			$resultset = $wpdb->get_results($wpdb->prepare("$sql $where $sort $limit", PRESENTATION_SLUG));
			foreach ($resultset as $record) {
				$data[] = [
					'id'          => $record->id,
					'slug'        => $record->slug,
					'title'       => $record->name,
					'description' => $record->description,
					'slides'      => $record->count,
					'status'      => $record->status,
					'date'        => $record->date,
					'author'      => $record->author
				];
			}
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

	private function enumeratePresentations() {
		global $wpdb;

		$sql  = "SELECT meta_value status, COUNT(*) count FROM {$wpdb->terms} ";
		$sql .= "LEFT JOIN {$wpdb->term_taxonomy} USING(term_id) ";
		$sql .= "LEFT JOIN {$wpdb->termmeta} ON {$wpdb->termmeta}.term_id = {$wpdb->terms}.term_id ";
		$sql .= "AND {$wpdb->termmeta}.meta_key = 'pressentation_status' ";
		$sql .= 'WHERE taxonomy = %s AND meta_value IS NOT NULL GROUP BY meta_value';

		$counting = $wpdb->get_results($wpdb->prepare($sql, PRESENTATION_SLUG));

		$count = new \StdClass();
		$count->all = 0;

		foreach($counting as $item) {
			$count->{$item->status} = $item->count;
			if($item->status != 'trash')
				$count->all += $item->count;
		}

		return $count;
	} // enumeratePresentations
} // class PresentationsTable