<?php

class Module_uicore extends Module {
	static $styles = ['uicore.css'];
	static $scripts = ['uicore.js'];
}

class UiCore {
	public static $slider_counter = 0;

	// Generate input code for specific field
	public static function getInput($key, $value, $key_suffix, $field_desc) {
		$ret = '';
		if ($field_desc['type']!=='hidden') {
			$ret = '<div class="field_container field_container_' . $key . '" id="field_container_' . $key . $key_suffix . '">';
			if ($field_desc['type']!=='checkbox' && isset($field_desc['label'])) $ret .= '<label for="' . $key . $key_suffix . '">' . $field_desc['label'] . '</label>';
		}

		$idclass_str = 'class="input_' . $key . '" id="' . $key . $key_suffix . '" name="' . $key . $key_suffix . '"';

		switch($field_desc['type']) {
		case 'checkbox':
			$ret .= '<input ' . $idclass_str . ' value="' . (isset($field_desc['setvalue']) ? $field_desc['setvalue'] : '1') . '" type="checkbox"' . (intval($value)===1 ? ' checked' : '') . ' />';
			$ret .= '<label for="' . $key . $key_suffix . '">' . $field_desc['label'] . '</label>';

			break;
		case 'textarea':
			$ret .= '<textarea ' . $idclass_str . ' placeholder="' . $field_desc['placeholder'] . '">' . htmlspecialchars($value) . '</textarea>';
			break;
		case 'select':
			$ret .= '<select ' . $idclass_str . '>';
			if (self::isAssoc($field_desc['options'])) {
				foreach($field_desc['options'] as $o_key => $o_title) {
					$ret .= '<option value="' . $o_key . '"' . ($value===$o_key ? ' selected' : '') . '>' . $o_title . '</option>';
				}
			}
			else {
				foreach($field_desc['options'] as $o_key) {
					$ret .= '<option value="' . $o_key . '"' . ($value===$o_key ? ' selected' : '') . '>' . $o_key . '</option>';
				}

			}
			$ret .= '</select>';
			break;
		case 'submit':
		case 'button':
			$ret .= '<input type="' . $field_desc['type'] . '" ' . $idclass_str . ' value="' . htmlspecialchars($value) . '" />';
			break;
		default:
			$ret .= '<input type="' . $field_desc['type'] . '" ' . $idclass_str . ' placeholder="' . $field_desc['placeholder'] . '" value="' . htmlspecialchars($value) . '" />';
			break;
		}

		if ($field_desc['type']!=='hidden') $ret .= '</div>';
		return $ret;
	}



	// Generate form with specified input code inside
	public static function editForm($form_id, $object = NULL, $code = NULL, $submit_code = NULL, $action_url = NULL, $method = 'POST') {
		$ret = '<form class="uicore_form" id="' . $form_id . '_form" method="' . $method . '"' . ($action_url ? ' action="' . $action_url . '"' : '') . '>';
		if ($code) $ret .= $code;
		else if ($object) {
			$ret .= static::editFields($object);
		}
		else {
			trigger_error('UiCore::editForm required object or code to be specified. You may omit one of them, but not both.', E_USER_ERROR);
		}

		if ($submit_code) $ret .= $submit_code;
		else {
			$ret .= '<div class="field_container field_container_submit"><input type="submit" value="Save" /></div>';
		}


		$ret .= '<input type="hidden" name="__submit_form_id" id="__submit_form_id" value="' . $form_id . '" />';

		$ret .= '</form>';

		return $ret;

	}

	private static function isAssoc($arr) {
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	// Returns image field 
	public static function imageField($image_id, $imgpath, $delete_function = 'uiCoreDeleteImage') {
		$ret = '<div class="image_field" id="image_field_' . $image_id . '">
			<div class="image_field_image"><img src="' . $imgpath . '" alt="" /></div>
			' . (trim($delete_function)!=='' ? '<div class="image_field_action"><input type="button" value="Удалить" onclick="' . $delete_function . '(' . $image_id . ');" /></div>' : '') . '
			</div>';

		return $ret;


	}

	public static function addImageForm($file_key = 'add-image', $use_tags = true) {
		$ret = '<form id="new_photo_form" method="post" enctype="multipart/form-data">
			<input type="hidden" name="__submit_form_id" value="' . $file_key . '" />
			' . ($use_tags ? '
			<select name="tag">
			<option value="gallery">Фотогалерея</option>
			<option value="standalone">Отдельное фото</option>
			</select>' : '') . '
			<input type="file" name="' . $file_key . '" /><br />
			<input type="submit" value="Добавить фото" />

			</form>';

		return $ret;


	}


	public static function slider($images, $slider_id = NULL) {
		if (!$slider_id) {
			self::$slider_counter++;
			$slider_id = 'slider_' . self::$slider_counter;
		}
		if (count($images)===0) return;
		$slides = '<div class="slider">';
		$thumbs = '<div class="slider_thumbs">';
		$active = true;
		foreach($images as $image) {
			$slides .= '<div id="slide_' . $image->id . '" class="slide' . ($active ? ' active' : '') . '" style="background-image: url(' . $image->url('b') . ');"></div>';
			$thumbs .= '<div class="slider_thumb' . ($active ? ' active' : '') . '" id="slider_thumb_' . $image->id . '" style="background-image: url(' . $image->url('s') . ');">
				<div class="slider_thumb_overlay" onmouseover="switchSlide(\'' . $slider_id . '\', ' . $image->id . ');"></div>
				</div>';

			$active = false;
		}
		$slides .= '</div>';
		$thumbs .= '</div>';
		if (count($images)===1) $thumbs = '';

		return '<div class="slider_container" id="' . $slider_id . '">' . $slides . $thumbs . '</div>';

	}


	public static function deleteField($callback_name, $value, $redirect_url='', $warning_text = '', $delete_function = 'uiCore_deleteObject') {
		$ret = '<input type="button" value="' . $value . '" onclick="' . $delete_function . '(\'' . $callback_name . '\', \'' . $warning_text . '\', \'' . $redirect_url . '\');" />';
		return $ret;
	}


	public static function table($table, $head = NULL) {
		$ret = '<div class="table uicore-table">';
		if ($head) {
			$ret .= '<div class="table-row table-head">';
			foreach($head as $head_item) {
				$ret .= '<div class="table-cell">' . $head_item . '</div>';
			}
			$ret .= '</div>';
		}

		if (self::isAssoc($table)) {
			foreach($table as $title => $value) {
				$ret .= '<div class="table-row">
					<div class="table-cell table-cell-title">' . $title . '</div>
					<div class="table-cell table-cell-value">' . $value . '</div>
					</div>';
			}
		}
		else {
			foreach($table as $row) {
				$ret .= '<div class="table-row">';
				if (isAssoc($row)) {
					foreach($row as $key => $value) {
						$ret .= '<div class="table-cell table-cell-' . $key . '">' . $value . '</div>';
					}
				}
				else {
					foreach($row as $value) {
						$ret .= '<div class="table-cell">' . $value . '</div>';
					}
				}
				$ret .= '</div>';
			}
		}
		
		$ret .= '</div>';



		return $ret;
	}

	public static function tabs($tabs) {
		$ret = '<div class="tabs">';

		// Nav bar
		$ret .= '<div class="tabs_nav">';
		$first = true;
		foreach($tabs as $tab_id => $tab) {
			$ret .= '<div class="tab_nav_item' . ($first ? ' active' : '') . '" id="tab_nav_item_' . $tab_id . '" data-tab-id="' . $tab_id . '">' . $tab['title'] . '</div>';
			$first = false;
		}
		$ret .= '</div>';

		// Tabs content
		$ret .= '<div class="tabs_content">';

		$first = true;
		foreach($tabs as $tab_id => $tab) {
			$ret .= '<div class="tab_content_item' . ($first ? ' active' : '') . '" id="tab_content_item_' . $tab_id . '" data-tab-id="' . $tab_id . '">' . $tab['body'] . '</div>';
			$first = false;
		}

		$ret .= '</div>';


		$ret .= '</div>';

		return $ret;
	}

	public static function dependParse($from) {
		$map = [
			'atleast' => '>=',
			'equal' => '=',
			'any' => '',
			];

		if (array_key_exists($from, $map)) return $map[$from];
		else if (in_array($from, $map)) return array_search($from, $map);
		return '';
	}
}
