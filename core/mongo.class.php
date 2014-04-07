<?php
/* Generic MongoDB connection class with singleton connection */
class MongoConnection {
	private static $connection = NULL;
	public static function c() {
		if (!static::$connection) {
			static::$connection = new MongoClient();
		}
		
		return static::$connection;
	}
}
