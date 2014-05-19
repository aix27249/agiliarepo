<?php
require_once '../core/bootstrap.php';
class TEST extends MongoDBAdapter {
	public static function run() {
		// Bug is in this query. Parameters specified in query may present in different rows; need to ensure that all of this is in the single line (js function maybe?)
		$query = [
			'arch' => Package::queryArchSet('x86'),
			'repositories' => ['$elemMatch' => [
					'repository' => 'master', 
					'osversion' => ['$in' => ['8.1', '9.0']], 
					'branch' => 'core', 
					'latest' => true
					],
				],
			];

		$packages = self::db()->packages->find($query);

		echo "Initial count: " . $packages->count() . "\n";
		$tree = new PackageTree($packages);

		$sv = self::db()->setup_variants->findOne(['name' => 'KDE']);
		$list = $sv['packages'];

		echo "Running dep_reduced\n";
		$reduced = $tree->dep_reduced($list, true);
		echo "Got " . count($reduced) . " sums\n";
		var_dump($reduced);
		echo "Final query\n";
		$pnames = self::db()->packages->distinct('name', ['md5' => ['$in' => $reduced]]);
		sort($pnames);
		print_r($pnames);
	}
}

TEST::run();

