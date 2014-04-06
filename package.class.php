<?php

/* 
 * AgiliaLinux PHP package handling library
 *
 * Class Package: read different data from *.txz package files 
 * WARNING: do not use complex filenames (with spaces, brackets etc) - they passed to shell directly.
 * TODO: fix this issue
 *
 */

require_once 'tgzhandler.class.php';
class Package {
	public $filename = NULL;
	// Checks if file exists
	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new Exception($filename . ': file not found');
		}
		$this->filename = $filename;
	}

	// Returns SimpleXML object with metadata
	public function xml() {
		$raw_xml = TgzHandler::readFile($this->filename, 'install/data.xml');
		$xml = new SimpleXMLElement($raw_xml);
		return $xml;
	}

	// Returns array of metadata
	public function metadata() {
		$xml = $this->xml();
		$data = [
			'name' => trim($xml->name),
			'version' => trim($xml->version),
			'arch' => trim($xml->arch),
			'build' => trim($xml->build),
			'short_description' => trim($xml->short_description),
			'description' => trim($xml->description),
			'dependencies' => [], // Fill later
			'suggests' => [], // Fill later
			'maintainer' => ['name' => trim($xml->maintainer->name), 'email' => trim($xml->maintainer->email)],
			'tags' => [], // Fill later,
			'compressed_size' => $this->filesize(),
			'installed_size' => $this->datasize(),
			'filename' => basename($this->filename),
			'md5' => $this->md5(),
			];

		foreach($xml->dependencies->children() as $dep) {
			$data['dependencies'][] = ['name' => trim($dep->name), 'condition' => trim($dep->condition), 'version' => trim($dep->version)];
		}

		foreach($xml->suggests->children() as $dep) {
			$data['suggests'][] = ['name' => trim($dep->name), 'condition' => trim($dep->condition), 'version' => trim($dep->version)];
		}

		foreach($xml->tags->children() as $tag) {
			$data['tags'][] = trim($tag);
		}

		return $data;
	}

	// Returns JSON with metadata. I think it will be used often, so it seems to be usable to make this separate function
	public function json() {
		return json_encode($this->metadata());
	}

	// Returns array of files inside package (including install/*)
	public function filelist() {
		return TgzHandler::filelist($this->filename);
	}

	// Returns JSON-encoded array of files inside package (including install/*)
	public function json_filelist() {
		return json_encode($this->filelist());
	}

	public function md5() {
		return preg_replace('/\s.*/', '', shell_exec("md5sum " . $this->filename));
	}

	public function filesize() {
		return filesize($this->filename);
	}

	public function datasize() {
		$data = explode("\t", shell_exec('xz -l --robot ' . $this->filename . ' | grep totals'));
		return $data[4];

	}


}
