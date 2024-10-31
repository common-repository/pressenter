<?php
namespace Pressenter;

trait Utils {
	static function truncate($string,$length=100,$append="&hellip;") {
		$string = trim(preg_replace('/\s\s+/', ' ', $string));

		if(strlen($string) > $length) {
			$string = @wordwrap($string, $length - sizeof($append), "\n", true);
			$string = explode("\n", $string, 2);
			$string = $string[0] . $append;
		}

		return $string;
	} // truncate

	public function manageOptions() {
		include $this->path."/admin/partials/settings.phtml";
	} // manageOptions
}