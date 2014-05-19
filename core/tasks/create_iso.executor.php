<?php
require_once dirname(__FILE__) . '/../bootstrap.php';

class CreateIsoTask extends AsyncTask {
	public function run() {

		// Query packages which should be added to ISO
		$this->setStatus('running', 'Querying packages to be added');
		$query = [
			'repositories' => ['$elemMatch' => [
				'repository' => $this->options['repository'],
				'latest' => true,
				'branch' => ['$in' => $this->options['branches']],
				'osversion' => ['$in' => $this->options['osversions']],
				'subgroup' => ['$in' => $this->options['subgroups']],
				]]
			];
		$archset = Package::queryArchSet($this->options['arch']);
		if ($archset!==NULL) {
			$query['arch'] = $archset;
		}

	
		// Extract setup variants which should be used in setup
		$setup_variants = [];
		foreach($this->options['osversions'] as $osversion) {
			foreach($this->options['setup_variants'] as $variant_name) {
				echo "Querying setup variant $variant_name\n";
				$sv_query = ['name' => $variant_name, 'osversion' => $osversion, 'repository' => $this->options['repository']];
				$variant = self::db()->setup_variants->findOne($sv_query);
				if ($variant === NULL) {
					continue;
					echo 'Setup variant not found: ' . $variant_name . "\n";
					die('');
				}
				$setup_variants[] = $variant;
			}
		}

		// Extract packages which are used in setup_variants
		$package_names = [];
		foreach($setup_variants as $sv) {
			$package_names = array_merge($package_names, $sv['packages']);
		}
		$package_names = array_unique($package_names);
		echo "Got " . count($package_names) . " packages total with uniquing\n";
		$this->setProgress(0, 100, 'Got ' . count($package_names) . ' package names to include, running dep_reduce');
	
		// Reduce
		echo "Querying...\n";
		$packages = self::db()->packages->find($query);
		echo "Reducing\n";
		$tree = new PackageTree($packages);
		
		$query = ['md5' => ['$in' => $tree->dep_reduced($package_names, true)]];

		
		unset($tree);

		// Call IsoBuilder to build this stuff
		// TODO: pass setup variants into there
		IsoBuilder::makeISO($this->options['iso_name'], $this->options['iso_template'], $query, $setup_variants, $this->owner, $this);

		$this->setProgress(100, 100, 'Done');
		$this->setStatus('complete', 'ISO created (maybe, successfully)');
		


	}
}
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new CreateIsoTask($task_id);
$task->setPid();
$task->run();


