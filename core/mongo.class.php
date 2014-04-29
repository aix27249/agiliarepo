<?php
/* Generic MongoDB connection class with singleton connection */
class MongoConnection {
	private static $connection = NULL;
	public static function c() {
		if (!static::$connection) {
			// Check if config exists
			$conf = dirname(__FILE__) . '/../conf/mongo.conf.php';
			if (file_exists($conf)) {
				require_once $conf;
				if (!isset($mongo_config['server'])) $mongo_config['server'] = 'mongodb://localhost:27017';
				if (!isset($mongo_config['options'])) $mongo_config['options'] = ['connect' => true];
				if (!isset($mongo_config['driver_options'])) $mongo_config['driver_options'] = [];
				static::$connection = new MongoClient($mongo_config['server'], $mongo_config['options'], $mongo_config['driver_options']);

			}
			else {
				static::$connection = new MongoClient();
			}
		}

		return static::$connection;
	}
}


class MongoDBAdapter {
	private static $db = NULL;
	public static function db() {
		if (!self::$db) {
			$dbname = 'agiliarepo';//$mongo_config['dbname']; // FIXME: remove hardcode
			self::$db = MongoConnection::c()->$dbname;
		}
		return self::$db;


	}
}

abstract class MongoDBObject extends MongoDBAdapter {
	protected $data = [];
	protected $old_data = NULL;
	protected $id_key = NULL;

	public function __get($key) {
		if (isset($this->data[$key])) return $this->data[$key];
		return NULL;
	}

	public function __set($key, $value) {
		if ($this->old_data===NULL) $this->old_data = $this->data;

		$this->data[$key] = $value;
	}
	public function storeState($force = true) {

		if ($force || $this->old_data===NULL) $this->old_data = $this->data;
	}

	protected function load($collection, $query) {
		$this->data = self::db()->$collection->findOne($query);
	}

	abstract public function __construct($id = NULL);

	public function save($dry_run = false) {
		if ($this->id_key===NULL) throw new Exception('Save failed: class' . get_class() . ' has no id_key defined');

		$changeset = [];
		foreach($this->data as $key => $value) {
			if (@$this->old_data[$key]!=$value) $changeset[$key] = $value;
		}

		if ($dry_run) {
			print_r($changeset);
			return;
		}

		if (count($changeset)===0) return;
		
		$searchquery = 	[
			$this->id_key => $this->data[$this->id_key], 
			'_rev' => $this->data['_rev']
			]; 

		$setquery = [
			'$set' => $changeset, 
			'$inc' => ['_rev' => 1]
			];


		self::db()->packages->findAndModify($searchquery, $setquery);




	}
}


