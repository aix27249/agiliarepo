<?php

class Module_iso extends Module {

	public function run() {
		$user = Auth::user();
		if (!$user) return 'You need to be logged in before accessing this page';

		$images = FsMap::scandir($user->homedir() . '/iso/images');
		if (isset($this->page->path[2])) {
			$image = $user->homedir() . '/iso/images/' . basename($this->page->path[2]);
			if (!file_exists($image)) return 'Sorry, file not found';

			// Serve file
			set_time_limit(0);
			$fh = fopen($image, 'rb');
			if (!$fh) die('Could not open file for reading');

			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($image));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($image));
			$chunk = 10 * 1048576; // 10Mb
			while (!feof($fh)) {
				echo fread($fh, $chunk);
				flush();
			}
			fclose($fh);
			exit;


		}

		$ret = '<h1>User ISO images</h1>';
		
		$ret .= '<ul>';
		foreach($images as $img) {
			$ret .= '<li><a href="/iso/' . $img . '">' . $img . '</a> (' . UI::humanizeSize(filesize($user->homedir() . '/iso/images/' . $img)) . ')</li>';
		}
		$ret .= '</ul>';

		return $ret;


	}
}
