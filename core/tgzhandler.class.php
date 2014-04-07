<?php

/* 
 * AgiliaLinux PHP package handling library
 *
 * Class TgzHandler: handle txz archives, mostly to assist Package handling 
 * WARNING: do not use complex filenames (with spaces, brackets etc) - they passed to shell directly.
 * TODO: fix this issue
 *
 */


class TgzHandler {
	public static function filelist($filename) {
		$ptr = popen('tar tf ' . $filename, 'r');
		$ret = array();
		while (!feof($ptr)) {
			
			$t = trim(fgets($ptr));
			if ($t==='' || $t==='./') continue;
			$ret[] = $t;
		}
		fclose($ptr);

		return $ret;
		
	}

	public static function readFile($tgz, $file) {
		return shell_exec('tar xf ' . $tgz . ' ' . $file . ' --to-stdout');
	}
}
