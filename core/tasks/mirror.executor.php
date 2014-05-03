<?php
/* Mirror external repository */
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('allow_url_open', 1);

require_once dirname(__FILE__) . '/../bootstrap.php';

class MirrorTask extends AsyncTask {
	public function run() {
		$this->setStatus('running');
		$server_url = $this->options['server_url'];
		$repository_name = $this->options['repository_name'];
		
		$api_root = $server_url . '/api';
		
		$repository_info_raw = $this->wget_data($api_root . '/repositories/info/' . $repository_name);
		if ($repository_info_raw===false) {
			die('Failed to read repository info from ' . $api_root . '/repositories/info/' . $repository_name . "\n");
		}

		$repository_info = json_decode($repository_info_raw, true);
		if ($repository_info===NULL) {
			die('Failed to parse data, raw: ' . var_dump($repository_info_raw) . "\n");
		}

		var_dump($repository_info);


		$repository = new Repository($repository_name);
		$repository->setSettings($repository_info);
		$repository->update();

		$remote_packages_raw = $this->wget_data($api_root . '/repositories/packages/' . $repository_name . '?latest');
		$remote_packages = json_decode($remote_packages_raw, true);
		if ($remote_packages===NULL) {
			die('Failed to read remote packages');
		}


		$skip_packages = [];
		$local_packages = self::db()->packages->distinct('md5', ['repositories.repository' => $repository_name]);

		$fetch_packages = array_diff($remote_packages, $local_packages);

		$count = count($fetch_packages);
		$counter = 0;

		foreach($fetch_packages as $remote_md5) {
			$counter++;
			$this->setProgress($counter, $count, 'Fetching ' . $counter . '/' . $count . ': ' . $remote_md5);
			// Fetch package info from remote server
			$pkginfo_raw = $this->wget_data($api_root . '/packages/info/' . $remote_md5);
			$pkginfo = json_decode($pkginfo_raw, true);

			// Check if such package is already on local server, but in other repository
			$local = self::db()->packages->findOne(['md5' => $remote_md5]);
			if ($local) {
				$local_repset = $local['repositories'];
				foreach($pkginfo['repositories'] as $remote_repinfo) {
					if ($remote_repinfo['repository']===$repository_name) $local_repset[] = $remote_repinfo;
				}
				$package = new Package($local);
				$package->repositories = $local_repset;
				$package->save();
			}
			else {
				unset($pkginfo['_id']);
				$remote_repset = $pkginfo['repositories'];
				$local_repset = [];
				foreach($remote_repset as $remote_repinfo) {
					if ($remote_repinfo['repository']===$repository_name) $local_repset[] = $remote_repinfo;
				}
				$pkginfo['repositories'] = $local_repset;
				$url = $this->wget_data($api_root . '/packages/url/' . $remote_md5);
				
				$output = ServerSettings::$root_path . '/' . $repository->defaultPath() . '/' . $pkginfo['filename'];
				system('wget ' . $server_url . '/' . $url . ' -O ' . $output);
				$pkginfo['location'] = $repository->defaultPath();
				// Rewrite add_date, otherwise it will be stored as array
				$pkginfo['add_date'] = new MongoDate($pkginfo['add_date']['sec'], $pkginfo['add_date']['usec']);

				self::db()->packages->insert($pkginfo);
				$package_files_raw = $this->wget_data($api_root . '/packages/files/' . $remote_md5);
				$package_files = json_decode($package_files_raw, true);
				if ($package_files===NULL) {
					die('Oops, cannot parse package files data, raw: ' . $package_files_raw);
				}
				unset($package_files['_id']);
				self::db()->package_files->insert($package_files);
			}

		}
		
		$this->setStatus('complete', 'Finished');
	}

	public function wget_data($url) {
		$data = shell_exec("wget '" . $url . "' -O - 2>/dev/null");
		return $data;
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new MirrorTask($task_id);
$task->setPid();
$task->run();
