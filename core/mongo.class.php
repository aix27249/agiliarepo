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
