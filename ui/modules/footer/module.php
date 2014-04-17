<?php

Page::loadModule('repository');
class Module_footer extends RepositoryModule {
	public static $styles = ['footer.css'];
	function run() {
		$count = $this->db->packages->count();
		$fcount = $this->db->package_files->count();

		/*
		$map = new MongoCode('function() { emit("total", this.installed_size }');

		$reduce = new MongoCode('function(k,v) {
			var i, sum = 0;
			for (i in v) {
				sum += v[i];
			}
			return sum;
		}');

		$totalsize = $this->db->command 
		 */

		$totalsize = $this->db->packages->aggregate(
			[
				'$group' => [
					'_id' => '',
					'compressed_size' => ['$sum' => '$compressed_size']
				],
			], 
			[
				'$project' => [
					'_id' => 0,
					'compressed_size' => '$compressed_size'
				]
			]);

		$r = $totalsize['result'][0]['compressed_size'];
		$ret = 'This server contains ' . $count . ' packages, total ' . UI::humanizeSize($r);

		return $ret;

	}

}
