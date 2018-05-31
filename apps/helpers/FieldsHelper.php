<?php

class FieldsHelper extends BaseHelper {
	public static $instance = null;

	public static $db = null;

	public static $config = null;

	public static function instance() {
		return !is_null(self::$instance) ? self::$instance : self::$instance = new FormHelper();
	}

	public function __construct($params = null) {
		parent::__construct();
		$this->params = $params;
		$this->localizer = Registry()->localizer;

		if(Registry()->controller->module){
			$this->module_labels = Registry()->localizer->get_label(Registry()->controller->module, 'DB_FIELDS');
		}

		self::$instance = $this;
		self::$db = Registry()->db;
	}

	public function generate($module, $obj = null, $view = false) {
		$result = array();
		if($obj) {
			$model = $obj;
			$model_name = get_class($obj);
		} else {
			$model_name = Inflector::modulize($module.'Model');
			$model  = new $model_name;
		}

		$belongs_to = (array)$model->get_belongs_to();
		$habtms = (array)$model->get_habtms();
		$has_many = (array)$model->get_has_many();
		$has_one = (array)$model->get_has_one();

		self::$config = Yaml::loadFile(Config()->MODULES_PATH . $module .'/Config.yaml');

		$columns = $this->table_to_columns($model);


		foreach($columns as $k => $v) {
			$options = array('name' => $k, 'value' => $model->{$k});
			$type = $v;

			if(array_key_exists($k, self::$config['FIELDS_TYPES'])) {
				$type = $options['type'] = self::$config['FIELDS_TYPES'][$k];
			}

			if(array_key_exists($k, self::$config['FIELDS_CLASSES'])) {
				$options['class'] = self::$config['FIELDS_CLASSES'][$k];
			}

			/*if($type=='file' && $options['value']) {
				if(method_exists($model,'get_'.$k.'_for_admin')) {
					$options['value'] = $model->{'get_'.$k.'_for_admin'}();
				} else {
					$options['value'] = '<p class="info">'.sprintf(Registry()->localizer->get('missing_function_for_file'),'get_'.$k.'_for_admin').'</p>';
				}
			}*/

			if(method_exists($model, 'get_' . $k . '_for_admin')) {
				$options['options'] = $model->{'get_'.$k.'_for_admin'}();
			}

			if(is_array($v)) {
				$column = current($v);
				if($column['is_habtm']==true) {
					$options['autocomplete_column'] = $column['autocomplete_column'];
					$options['foreign_key'] = $column['foreign_key'];
					$options['association_foreign_key'] = $column['association_foreign_key'];
                    $options['order'] = $column['order'];
					$options['class_name'] = $column['class_name'] ? $column['class_name'] : $k;
					$options['data_relation'] = $k;
					$options['current_object_id'] = $model->id;
					$options['values'] = array();

					foreach($obj->{$k} as $rel) {
						$options['values'][$rel->id] = (string)$rel;
					}

					if($type == 'dropdown') {
						$options['conditions'] = $column['conditions'];
						$result[] = $this->{'habtm_autocomplete_to_dropdown'}($options);
					} else {
						$result[] = $this->{'habtm_autocomplete_to_field'}($options);
					}

				} elseif ($column['is_has_many']==true) {
					if(in_array($column['class_name'], array('image','image_extra', 'file', 'file_extra'))) {
						$options['keyname'] = $k;
						$options['class_name'] = $column['class_name'];
						$options['multiple'] = true;
						$options['current_object_id'] = $model->id;
						$options['model'] = $model_name;
						$result[] = $this->{'_to_fileupload'}($options);
					}
				} elseif ($column['is_has_one']==true) {
					if(in_array($column['class_name'], array('image','image_extra', 'file', 'file_extra'))) {
						$options['keyname'] = $k;
						$options['class_name'] = $column['class_name'];
						$options['multiple'] = false;
						$options['current_object_id'] = $model->id;
						$options['model'] = $model_name;
						$result[] = $this->{'_to_fileupload'}($options);
					}
				} else {
					if($type == 'dropdown'){
						$options['column'] = $column['column'];
						$options['conditions'] = $column['conditions'];
						$options['class_name'] = $column['class_name'];
						$options['show_as_three_list'] = isset($column['show_as_three_list']) ? $column['show_as_three_list'] : false;
						$result[] = $this->{'belongs_to_dropdown'}($options);
					}else{
						$options['autocomplete_column'] = $column['column'];
						$options['autocomplete_conditions'] = $column['conditions'];
						$options['class_name'] = $column['class_name'];
                        $options['show_as_three_list'] = isset($column['show_as_three_list']) ? $column['show_as_three_list'] : false;
						$result[] = $this->{'belongs_to_autocomplete_to_field'}($options);
					}
				}
			}
			elseif(in_array($k, $model->primary_keys)) {
				$options['type'] = 'hidden';
				$result[] = $this->{'varchar_to_field'}($options);
			}
			elseif(in_array($type, array('int','tinyint','smallint','mediumint','bigint','float','double'))){
				$result[] = $this->small_field_generator($options);
			}
			elseif(in_array($type, array('text','tinytext','mediumtext','longtext','bigtext'))){
				$options['class'] = $options['class'] ? $options['class'] :'rich-text';
				$result[] = $this->{'text_to_field'}($options);
			}
			elseif(method_exists($this, $type.'_to_field')) {
				$result[] = $this->{$type.'_to_field'}($options);
			} else { // All other types
				$result[] = $this->{'varchar_to_field'}($options);
			}

		}

		return '<ol><li>' . join('</li><li>', $result) . '</li></ol>';
	}

	public function dropdown_to_field($options) {
		return $this->_to_select_box_field($options);
	}

	public function _to_select_box_field($options) {
		$html = '';
		$options['id'] = $this->name_to_id($options['name']);

		if(Registry()->localizer->get($options['name'].'_values')) {
			$options['options'] = Registry()->localizer->get($options['name'].'_values');
		} else {
			if(self::$config[$options['name'].'_values']) {
				$options['options'] = self::$config[$options['name'].'_values'];
			}
		}

		foreach($options['options'] as $key => $value) {
			$keys[] = htmlspecialchars($key);
			$values[] = $value;
		}

		if($options['type'] != 'hidden') {
			if(is_array($this->module_labels) && array_key_exists(str_replace('_autocomplete','',$options['name']), $this->module_labels)) {
				$label = $this->module_labels[str_replace('_autocomplete','',$options['name'])];
			} else {
				$label = Registry()->localizer->get('DB_FIELDS',str_replace('_autocomplete','',$options['name']));
			}
			$html .= $this->content_tag('label', $label, array('for' => $options['id']));
		}

		$selected = false;
		if(array_key_exists('value', $options)) {
			$selected = $options['value'];
			unset($options['value']);
		}

		unset($options['options']);
		unset($options['value']);

		$options_html = "\n";
		$pairs = count($keys) && count($values) ? array_combine($keys, $values) : array();
		foreach($pairs as $key => $value) {
			$tag_options = array('value' => $key);
			if($key == $selected)
				$tag_options['selected'] = 'selected';

			$options_html .= $this->content_tag('option', $value, $tag_options);
		}

		$html .= $this->content_tag('select', $options_html, $options);

		return $html;
	}

	public function _to_input_field($options) {
		$html = '';

		$options['id'] = $options['id'] ? $options['id'] : $this->name_to_id($options['name']);

		$value = $options['value'];

		if($options['type'] == 'file' && isset($options['value'])) {
			unset($options['value']);
		}

		if(array_key_exists('value', $options)) {
			if($options['class'] != 'no-escape'){
				$options['value'] = str_replace(array('&amp;',"&quot;"), array('&','"'), htmlspecialchars($options['value']));
			}
		}

		if($options['type'] == 'hidden' && isset($options['size'])) {
			unset($options['size']);
		}

		if($options['type'] != 'hidden') {
			if(is_array($this->module_labels) && array_key_exists(str_replace('_autocomplete','',$options['name']), $this->module_labels)) {
				$label = $this->module_labels[str_replace('_autocomplete','',$options['name'])];
			} else {
				$label = Registry()->localizer->get('DB_FIELDS',str_replace('_autocomplete','',$options['name']));
			}

			$html .= $this->content_tag('label', $label, array('for' => $options['id']));
		}

		$html .= $this->tag('input', $options);

		if($options['type'] == 'file' && $value) {
			$html .= $value;
		}

		return $html;
	}

	public function _to_fileupload($options) {
		$html = '';

		$options['id'] = $options['id'] ? $options['id'] : $this->name_to_id($options['name']);

		$value = $options['value'];

		$options['type'] = 'file';

		if($options['type'] == 'file' && isset($options['value'])) {
			unset($options['value']);
		}

		if(array_key_exists('value', $options)) {
			$options['value'] = htmlspecialchars($options['value']);
		}

		if($options['type'] == 'hidden' && isset($options['size'])) {
			unset($options['size']);
		}

		if($options['type'] != 'hidden') {
			if(is_array($this->module_labels) && array_key_exists(str_replace('_autocomplete','',$options['name']), $this->module_labels)) {
				$label = $this->module_labels[str_replace('_autocomplete','',$options['name'])];
			} else {
				$label = Registry()->localizer->get('DB_FIELDS',str_replace('_autocomplete','',$options['name']));
			}

			$html .= $this->content_tag('label', $label, array('for' => $options['id']));
		}

		// $html .= $this->tag('input', $options);

		if($options['type'] == 'file' && $value) {
			// $html .= $value;
		}

		$html.=  $this->render_helper($options['class_name'].'upload', array('options'=>$options));

		return $html;
	}

	public function _to_text_area($options) {
		$html = '';

		$options['id'] = $this->name_to_id($options['name']);

		if($options['type'] != 'hidden') {
			if(is_array($this->module_labels) && array_key_exists(str_replace('_autocomplete','',$options['name']), $this->module_labels)) {
				$label = $this->module_labels[str_replace('_autocomplete','',$options['name'])];
			} else {
				$label = Registry()->localizer->get('DB_FIELDS',str_replace('_autocomplete','',$options['name']));
			}

			$html .= $this->content_tag('label', $label, array('for' => $options['id']));
		}

		if(isset($options['value'])) {
			$value = $options['value'];
			unset($options['value']);
		} else {
			$value = null;
			unset($options['value']);
		}

		$html .= $this->content_tag('textarea', htmlspecialchars($value), $options);
		$html .= '<br class="clear" />';
		return $html;
	}

	private function belongs_to_autocomplete_to_field($options) {
		$html = '';

		$autocomplete_options = $options;

		if($autocomplete_options['value']) {
			$obj_name = $options['class_name'];
			$obj = new $obj_name;
			$autocomplete_options['value'] = $obj->find($autocomplete_options['value']);
		}

		$autocomplete_options['type'] = 'text';
		$autocomplete_options['name'].='_autocomplete';
		$autocomplete_options['class'] = $autocomplete_options['class'] ? $autocomplete_options['class'] : 'belongs_to_autocomplete';
		$autocomplete_options['data-object']=$options['class_name']; // tzuzy
		$autocomplete_options['data-column']=$options['autocomplete_column'];
		$autocomplete_options['data-conditions']=$options['conditions'];
		unset($autocomplete_options['autocomplete_column']);
		$html.= $this->_to_input_field($autocomplete_options);

		$options['type'] = 'hidden';
		$html.= $this->_to_input_field($options);

		return $html;
	}

	private function belongs_to_dropdown($options) {
		$html = '';

		$options['id'] = $this->name_to_id($options['name']);

		if(self::$config[$options['name'].'_values']) {
			$options['options'] = self::$config[$options['name'].'_values'];

			foreach($options['options'] as $key => $value) {
				$keys[] = htmlspecialchars($key);
				$values[] = $value;
			}
		} else {
			$obj_name = Inflector::camelize($options['class_name']);
			$obj = new $obj_name;
			$list = $obj->find_all($options['conditions']);
			$keys[] = '';
			$values[] = '--------------------';

			foreach($list as $item){
				$keys[] = $item->id;

				if($options['column']) {
                    if($options['name'] == 'page_id' || (isset($options['show_as_three_list']) && $options['show_as_three_list'])){
                        if(strpos($options['column'], '()')!==false) {
                            $values[] = $item->{str_replace('()','',$options['column'])}();
                        } else {
                            $values[] = str_repeat('--', ($item->lvl == 0) ?$item->lvl :$item->lvl + 2) . ' ' . $item->{$options['column']};
                        }

                    }else{
                        $values[] = $item->{$options['column']};
                    }
                } else {
                    $values[] = $item;
                }
			}

		}


		if($options['type'] != 'hidden') {
			if(is_array($this->module_labels) && array_key_exists(str_replace('_autocomplete','',$options['name']), $this->module_labels)) {
				$label = $this->module_labels[str_replace('_autocomplete','',$options['name'])];
			} else {
				$label = Registry()->localizer->get('DB_FIELDS',str_replace('_autocomplete','',$options['name']));
			}
			$html .= $this->content_tag('label', $label, array('for' => $options['id']));
		}

		$selected = false;
		if(array_key_exists('value', $options)) {
			$selected = $options['value'];
			unset($options['value']);
		}

		unset($options['options']);
		unset($options['value']);

		$options_html = "\n";
		$pairs = count($keys) && count($values) ? array_combine($keys, $values) : array();
		foreach($pairs as $key => $value) {
			$tag_options = array('value' => $key);
			if($key == $selected) {
				$tag_options['selected'] = 'selected';
			}

			$options_html .= $this->content_tag('option', $value, $tag_options);
		}

		$html .= $this->content_tag('select', $options_html, $options);
		return $html;
	}

	private function habtm_autocomplete_to_dropdown($options) {
		$html = '';


		$options['id'] = $this->name_to_id($options['name']);

		$obj_name = Inflector::camelize($options['class_name']);
		$obj = new $obj_name;

		if($obj_name == 'NewsModel' && $options['name'] == 'related_news') {
			$options['conditions'] = sprintf("news.id != %d", $options['current_object_id']);
		}

		$list = $obj->find_all($options['conditions'], $options['order'] ? $options['order'] : null);

//		ds();exit;
		$keys = array();

		foreach($list as $item) {
			$keys[] = $item->id;
			if($options['autocomplete_column']) {
                if(strpos($options['autocomplete_column'], '()')!==false) {
                    $values[] = $item->{str_replace('()','',$options['autocomplete_column'])}();
                } else {
                    $values[] = $item->{$options['autocomplete_column']};
                }

			} else {
				$values[] = $item;
			}
		}

		if($options['type'] != 'hidden') {
			if(is_array($this->module_labels) && array_key_exists(str_replace('_autocomplete','',$options['name']), $this->module_labels)) {
				$label = $this->module_labels[str_replace('_autocomplete','',$options['name'])];
			} else {
				$label = Registry()->localizer->get('DB_FIELDS',str_replace('_autocomplete','',$options['name']));
			}
			$html .= $this->content_tag('label', $label, array('for' => $options['id']));
		}

		unset($options['options']);
		unset($options['value']);

		$selected = array_keys($options['values']);
		$options_html = "\n";
		$pairs = count($keys) && count($values) ? array_combine($keys, $values) : array();

		foreach($pairs as $key => $value) {
			$tag_options = array('value' => $key);
			if(in_array($key, $selected)) {
				$tag_options['selected'] = 'selected';
			}

			$options_html .= $this->content_tag('option', $value, $tag_options);
		}

		$options['multiple'] = 'multiple';
		$options['id'] = $options['name'];
		$options['name'] = $options['name'].'[]';
		$html .= $this->content_tag('select', $options_html, $options);
		return $html;
	}

	private function habtm_autocomplete_to_field($options) {
		$html = '';
		$autocomplete_options = $options;
		$autocomplete_options['type'] = 'text';
		$autocomplete_options['name'].='_autocomplete';
		$autocomplete_options['class'] = $autocomplete_options['class'] ? $autocomplete_options['class'] : 'habtm_autocomplete';
		$autocomplete_options['data-column']=$options['autocomplete_column'];
		$autocomplete_options['data-association_foreign_key']= $options['association_foreign_key'];
		$autocomplete_options['data-foreign_key']= $options['foreign_key'];
		$autocomplete_options['data-class_name']= $options['class_name'];
		$autocomplete_options['data-relation']= $options['data_relation'];

		$values = $options['values'];
		$object_id = $options['current_object_id'];

		unset($autocomplete_options['autocomplete_column']);
		unset($autocomplete_options['association_foreign_key']);
		unset($autocomplete_options['foreign_key']);
		unset($autocomplete_options['foreign_key']);
		unset($autocomplete_options['class_name']);
		unset($autocomplete_options['data_relation']);
		unset($autocomplete_options['values']);
		unset($autocomplete_options['current_object_id']);

		$html.= $this->_to_input_field($autocomplete_options);
		$html .= '<ul class="habtm_result" id="result_'.$autocomplete_options['name'].'">';
		foreach($values as $k=>$v) {
			$html.='<li id="'.$autocomplete_options['data-relation'].'__'.$object_id.'_'.$k.'"><a href="javascript:;">Close</a>'.$v.'</li>';
		}
		$html .= '</ul>';
		//$html.= $this->_to_select_box_field($options);
		return $html;
	}

	private function varchar_to_field($options) {
		$options['type'] = $options['type'] ? $options['type'] : 'text';

		if($options['type']=='dropdown') {
			$options['class'] = $options['class'] ? $options['class'] : '';
			$options['options'] = Registry()->localizer->get('yesno');
			return $this->_to_select_box_field($options);
		} else {
			$options['class'] = $options['class'] ? $options['class'] : '';
			return $this->_to_input_field($options);
		}
	}

	private function text_to_field($options) {
		$options['class'] = $options['class'] ? $options['class'] : '';
		return $this->_to_text_area($options);
	}

	private function date_to_field($options) {
		$options['class'] = $options['class'] ? $options['class'] : 'date-input';
		$options['type'] = $options['type'] ? $options['type'] : 'text';
		return $this->_to_input_field($options);
	}

	private function datetime_to_field($options) {
		$options['class'] = $options['class'] ? $options['class'] : 'datetime-input';
		$options['type'] = $options['type'] ? $options['type'] : 'text';
		return $this->_to_input_field($options);
	}

	private function small_field_generator($options) {
		$options['type'] = $options['type'] ? $options['type'] : 'text';

		if($options['type']=='dropdown') {
			$options['class'] = $options['class'] ? $options['class'] : '';
			$options['options'] = Registry()->localizer->get('yesno');
			return $this->_to_select_box_field($options);
		} else {
			$options['class'] = $options['class'] ? $options['class'] : 'short';
			return $this->_to_input_field($options);
		}
	}

	private function name_to_id($name) {
		return 'id_' . $name;
	}

	/**
	*
	* TODO :
	*
	* 1. Make readable from Cache
	*
	*/
	function table_to_columns($model) {

		$belongs_to = (array)$model->get_belongs_to();
		$habtms = (array)$model->get_habtms();
		$has_many = (array)$model->get_has_many();
		$has_one = (array)$model->get_has_one();
		$table_columns = self::$db->table_info($model->table_name);
		if($model->have_i18n()) {
			$table_columns = $table_columns + self::$db->table_info($model->table_name.'_i18n');
			unset($table_columns['i18n_foreign_key'], $table_columns['i18n_locale']);
		}

		$return = array();

		foreach($table_columns as $k=>$v) {
			if(!in_array($k, array_keys(self::$config['FIELDS_EXCLUDE']))) {
				$belongs_to_key = str_replace('_id','',$k);

				if(array_key_exists($belongs_to_key, $belongs_to)) {
					$return[$k]=array($belongs_to_key=>$belongs_to[$belongs_to_key]);
				} else {
					$return[$k]=$v['real_type'];
				}
			}
		}

		if($habtms) {
			foreach($habtms as $h=>$habtm) {
				if(!in_array($h, array_keys(self::$config['FIELDS_EXCLUDE']))) {
					$habtm['is_habtm'] = true;
					$return[$h]=array($h=>$habtm);
				}
			}
		}

		if($has_many) {
			foreach($has_many as $name => $conditions){
				$conditions['is_has_many'] = true;
				$return[$name]=array($name=>$conditions);
			}
		}

		if($has_one) {
			foreach($has_one as $name => $conditions){
				$conditions['is_has_one'] = true;
				$return[$name]=array($name=>$conditions);
			}
		}

        if (is_callable([$model, 'reorderAdminFields'])) {
            $model->reorderAdminFields($return);
        }

		return $return;
	}

	function render_helper($partial = null, $options = array()) {
		// a little trick when called within smarty template
		if (is_array($partial)) {
			//$options = $partial;
			$data = $partial;
			$options = array();
			$partial = $data['partial'];
			$options['collection'] = array('obj'=>(object)$data);
		}

		// now let's find out the template file we will use for rendering this partial
		$helper_name = strtolower(get_class($this));
		$file = Config()->VIEWS_PATH . '/helpers/';
		$file .= (strpos($partial, '/') !== false) ? dirname($partial) . '/_' . basename($partial) : $helper_name  . '/_' . $partial . '.htm';

		if (is_file($file)) {
			$filename = substr(basename($file), 1, -4);
			$locals[$filename] = $options;
			$content = Registry()->tpl->assign($locals)->fetch($file);
		} else {
			$content = '<blockquote style="padding: 10px;margin-right: 0;margin-left: 0;background: lightblue;">Error: Partial <strong>' . $file . '</strong> not found.</blockquote>';
		}

		return $content;
	}

	function to_file_upload_smarty($params, $smarty) {
		$options = array(
			'name' => $params['name'],
			'keyname' => $params['name'],
			'class_name' => $params['class_name'],
			'current_object_id' => $params['id'],
			'model' => $params['model_name'],
			'multiple' => true,
		);

		return $this->_to_fileupload($options);
	}

}

?>