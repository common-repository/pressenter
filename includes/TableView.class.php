<?php
namespace Pressenter;

abstract class TableView extends \WP_List_Table {
	const PER_PAGE = 20;

	protected $shownStatus;
	protected $trashIsEmpty;
	protected $slug;
	protected $count;
	private $bulkable;

	public function __construct($slug, $args=[]) {
		global $wpdb;

		$this->slug = $slug;
		$this->shownStatus = (isset($_REQUEST['status']))?sanitize_text_field($_REQUEST['status']):'all';

		if(isset($args['bulkable'])) $this->bulkable = $args['bulkable'];
		else $this->bulkable = true;

		parent::__construct($args);
	} // __construct

	protected function get_hidden_columns() {
		return ['id'];
	} // get_hidden_columns

	protected function get_views() {
		$link = '<a href="?page='.$this->slug.'%s" %s>%s <span class="count">(%d)</span></a>';

		foreach($this->count as $status => $count) {
			$selected = ($this->shownStatus == $status)?'class="current" aria-current="page"':'';

			$label = '';
			$query = "&status=$status";
			switch($status) {
				case 'all':
					$label = _x('All', 'presentations status group', 'pressenter');
					$query = '';
				break;
				case 'draft':
					$label = _x('Draft', 'presentations status group', 'pressenter');
				break;
				case 'publish':
					$label = _x('Publish', 'presentations status group', 'pressenter');
				break;
				case 'pending':
					$label = _x('Pending', 'presentations status group', 'pressenter');
				break;
				case 'trash':
					$label = _x('Trash', 'presentations status group', 'pressenter');
				break;
			}

			if($label) $links[$status] = sprintf($link, $query, $selected, $label, $count);
		}

		$this->columnsStyle();

		return $links;
	} // get_views

	protected function column_cb($item) {
		return '<input name="'.$this->slug.'[]" id="'.$item['id'].'" class="cb-'.$this->slug.'" value="'.$item['id'].'" type="checkbox">';
	} // column_cb

	protected function column_default($item, $column_name) {
		return Utils::truncate($item[$column_name]);
	} // column_default

	public function get_bulk_actions() {
		$actions = [];
		if($this->bulkable) {
			switch($this->shownStatus) {
				case 'publish':
				case 'pending':
				case 'draft':
				default:
					$actions['trash'] = __('Move to trash', 'pressenter');
				break;
				case 'trash':
					$actions['restore'] = __('Restore', 'pressenter');
					$actions['delete'] = __('Delete Permanently', 'pressenter');
				break;
			}
		}

		return $actions;
	} // get_bulk_actions

	public function prepare_items() {
		$search =  isset($_REQUEST['s'])?wp_unslash(trim($_REQUEST['s'])):'';
		$order['by'] = isset($_REQUEST['orderby'])?wp_unslash(trim($_REQUEST['orderby'])):'id';
		$order['way'] = isset($_REQUEST['order'])?wp_unslash(trim($_REQUEST['order'])):'desc';

		$data = $this->tableData($search, $order);
		$this->_column_headers = [$this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns()];

		$pagination_args = [
			"total_items" => $this->getTotal(),
			"per_page" => self::PER_PAGE
		];
		$this->set_pagination_args($pagination_args);
		$this->items = $data;
	} // prepare_items

	public function getTotal() {
		return (isset($this->count->all))?(int)$this->count->all:0;
	}

	protected function columnsStyle() {}
	abstract function tableData($search, $order);
} // class TableView