<?php
Page::loadModule('admin');
Page::loadModule('uicore');

class Module_admin_settings extends AdminModule {
	public function run() {
		if ($this->blockname==='sidebar') return $this->run_sidebar();
	
		// Table of known settings
		$fields = [
			'default_repository' => ['type' => 'select', 'label' => 'Default repository', 'options' => Repository::getList()],
			'language' => ['type' => 'select', 'label' => 'UI language', 'options' => ['auto' => 'Detect automatically', 'en' => 'English', 'ru' => 'Russian']],
			'iso_templates_path' => ['type' => 'text', 'label' => 'ISO templates directory', 'placeholder' => 'Please, enter absolute path'],
			];

		$settings = new Settings(false);
		if (@$_POST['__submit_form_id']==='settings') {
			foreach($fields as $key => $fdesc) {
				$settings->$key = @$_POST[$key];
			}
			$settings->save();

		}
		$ret = '<h1>Server settings</h1>';
		

	
		$code = '';
		foreach($fields as $key => $fdesc) {
			$code .= UiCore::getInput($key, $settings->$key, '', $fdesc);
		}

		$ret .= UiCore::editForm('settings', NULL, $code);

		return $ret;
	}

	public function run_sidebar() {
	}
}
