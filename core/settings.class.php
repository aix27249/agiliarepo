<?php

require_once 'mongo.class.php';

// Wrapper on db()->settings stuff
class Settings extends MongoDBAdapter {
	private $realtime = true;
	private $fields = [];
	private static $instance = NULL;

	public function __construct($realtime = true) {
		$this->realtime = $realtime;
		if (!$this->realtime) $this->load();
	}

	public function __get($key) {
		if ($this->realtime) {
			$obj = self::db()->settings->findOne(['key' => $key]);
			if (isset($obj['value'])) return $obj['value'];
		}
		else return @$this->fields[$key];
	}

	public static function get($key) {
		if (!self::$instance) self::$instance = new self(true);
		return self::$instance->$key;
	}

	public function __set($key, $value) {
		if ($this->realtime) self::db()->settings->update(['key' => $key], ['key' => $key, 'value' => $value], ['upsert' => 1]);
		else $this->fields[$key] = $value;
	}

	public static function set($key, $value) {
		if (!self::$instance) self::$instance = new self(true);
		self::$instance->$key = $value;
	}

	public function clear($key) {
		if ($this->realtime) self::db()->settings->remove(['key' => $key]);
		else unset($this->fields[$key]);
	}

	private function load() {
		$opts = self::db()->settings->find();
		$this->fields = [];
		foreach($opts as $opt) {
			if (!isset($opt['key'])) continue;
			if (!isset($opt['value'])) continue;
			$this->fields[$opt['key']] = $opt['value'];
		}
	}

	public function save() {
		if ($this->realtime) throw new Exception ('Save cannot be used in realtime mode');
		foreach($this->fields as $key => $value) {
			self::db()->settings->update(['key' => $key], ['key' => $key, 'value' => $value], ['upsert' => 1]);
		}
	}

	public function keys() {
		if ($this->realtime) return self::db()->settings->distinct('key');
		else return array_keys($this->fields);
	}
}
