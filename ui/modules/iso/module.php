<?php

class Module_iso extends Module {
	public static $scripts = ['iso.js'];

	public function run() {
		$this->user = Auth::user();
		if (!$this->user) return 'You need to be logged in before accessing this page';

		if (trim(@$_POST['action'])!=='') die($this->ajax($_POST['action'], $_POST));

		$images = FsMap::scandir($this->user->homedir() . '/iso/images');
		if (isset($this->page->path[2])) {
			$image = $this->user->homedir() . '/iso/images/' . basename($this->page->path[2]);
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
		

		$table = [];
		foreach($images as $img) {
			$item = [
			'<a href="/iso/' . $img . '">' . $img . '</a>',
			UI::humanizeSize(filesize($this->user->homedir() . '/iso/images/' . $img)),
			'<input type="button" onclick="iso.remove(\'' . $img . '\');" value="Remove" />'
			];
			$table[] = $item;
		}

		$ret .= UiCore::table($table);

		return $ret;


	}

	public function ajax($action, $data) {
		switch($action) {
		case 'remove':
			$image = $this->user->homedir() . '/iso/images/' . trim($data['name']);
			if (!file_exists($image)) return 'File not found';
			unlink($image);
			return 'OK';
		default:
			return 'Unknown action ' . $action;
		}
	}
}
