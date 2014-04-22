<?php
Page::loadModule('repository');
class Module_index extends RepositoryModule {
	public function run() {
		/* There are basically two methods of getting JSON data from collection: use PHP code, or use mongoexport shell command. 
		 * PHP mode is simplier in code, safer to pass arguments from user, requires no shell calls, but about 7x times slower.
		 *
		 * mongoexport example here:
		 * system('mongoexport -d ' . $mongo_db_name . ' -c packages -q \'{"repositories.repository":"master","repositories.branch":"core","repositories.osversion":"8.1"}\'');
		 */

		$packages = self::db()->packages->find(['repositories.repository' => 'master', 'repositories.osversion' => '8.1', 'repositories.branch' => 'core']);
		die(json_encode(iterator_to_array($packages)));
	}
}
