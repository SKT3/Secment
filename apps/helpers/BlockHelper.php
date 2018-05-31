<?php

/**
 * Block Helper class
 *
 * Helps generation and caching of page blocks
 *
 * @package Sweboo
 * @subpackage Sweboo Core Helpers
 */
class BlockHelper extends BaseHelper {
	/*
	 * Paging parameters used in hash
	 */
	protected $_pagination_url_params = array();

	private static $instances = array();

	/*
	* Late Static Bindings
	*/
	public static function getInstance() {
		$class = get_called_class();
		if (array_key_exists($class, self::$instances) === false)
			self::$instances[$class] = new $class();
		return self::$instances[$class];
	}

	public function __construct() {
		$class = get_called_class();
		if (array_key_exists($class, self::$instances))
			trigger_error("Tried to construct  a second instance of class \"$class\"", E_USER_WARNING);
		parent::__construct();
	}


	/*
	* Render block from cache or load the dynamic block text with parsed params
	*/
	function render_block($options = array()) {
		if($options['cache_hash']) {
			$content = Registry()->cache->pull($options['cache_hash']);
		}
		if($content) {
			return $content;
		}

		Registry()->tpl->assign('pagination_url_params', $this->_pagination_url_params);

		// use this road to include files within widget
		$module = Inflector::underscore(str_replace('Widget', '', get_called_class()));
		$widget_template_path = Config()->MODULES_PATH . $module . '/views/widgets/';
		Registry()->tpl->assign('widget_template_path', $widget_template_path);

		// assign pagination url params
		!$this->_pagination_url_params && $this->_pagination_url_params = array('module' => Inflector::slugalize($module));
		Registry()->tpl->assign('pagination_url_params', $this->_pagination_url_params);

		$file = $widget_template_path . '_' . $options['partial'] . '.htm';
		foreach($options as $k => $v) {
			Registry()->tpl->assign($k, $v);
		}

		if($options['smarty_cache_id'] && Config()->DEVELOPMENT == false && false){
			Registry()->tpl->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
			Registry()->tpl->force_compile = false;
			Registry()->tpl->assign('session', Registry()->session);
			$content = Registry()->tpl->fetch($file, $options['smarty_cache_id']);
			Registry()->tpl->setCaching(0);
			Registry()->tpl->force_compile = true;
		}else{
			Registry()->tpl->assign('session', Registry()->session);
			$content = Registry()->tpl->fetch($file);
		}

		return $content;
	}

	function save_block($object){
		//Registry()->cache->expire($object->cache_hash);
		$cache_hash = $object->cache_hash;
		$object->cache_hash = '';

		// load parent model if necessary
		self::loadParentModel($object->action, $object->module);

		$content = $this->{$object->action}($object);
		if(is_array($content)) {
			if(array_key_exists('model_name', $content)) {
				$object->params = serialize(unserialize($object->params) + array('model_name' => $content['model_name']));
			}
			$content = array_key_exists('content', $content) ? $content['content'] : '';
		}

		$object->cache_hash = $cache_hash;

		if($object->use_cache) {
			$key = Registry()->cache->push($content,$object->cache_hash);
		}

		if ($object->use_cache) {
			$object->cache_hash = $key;
		} else {
			$object->cache_hash = '';
		}
		$object->save();

		return $content;
	}

	function _validatePageId($page_id, $parameters = null) {
		$params = $parameters && is_array($parameters) && !empty($parameters['restrictions']) ? $parameters : $this->getParams();
		$action = $parameters && is_string($parameters) ? $parameters : (is_array($parameters) && !empty($parameters['action']) ? $parameters['action'] : null);

		$widget_restrictions = $action && !empty($params['restrictions'][$action]) ? $params['restrictions'][$action] : $params['restrictions'];

		if(
			(!isset($widget_restrictions['page_id']) && !isset($widget_restrictions['root_page_id']))
			||
			(
				(!empty($widget_restrictions['page_id']) && (int)$widget_restrictions['page_id'] <> $page_id)
				&&
				(!empty($widget_restrictions['root_page_id'])
					&& (
						(int)$widget_restrictions['root_page_id'] <> $page_id
						&& (
							($page = new Page())
							&& ($request_page = $page->find($page_id))
							&& ($range_root_page = $page->find($widget_restrictions['root_page_id']))
							&& ($request_page->lft < $range_root_page->lft || $request_page->lft > $range_root_page->rght)
						)
					)
				)
			)
		) {
			return false;
		}

		!$request_page && $request_page = (new Page())->find($page_id);

		return $request_page ?: true;
	}

	function getParams() {
		return array(
			'use_cache' => '0',
			'visibility' => '2',
		);
	}

	function get_method_settings_restrictions($method_name = null) {
		return $this->_methodSettingsRestrictions($method_name);
	}

	protected function _methodSettingsRestrictions($method_name = null) {}

	/*
	 * If the action is calling a new ModelName which is not the same as the one of the Widget and there is no class registered in Model.php with the {WidgetName}Model
	* Then call include the Model.php in order to have access to its classes and pray the called Model is there :)
	*/
	static function loadParentModel($action_for_class, $module) {
		if(!class_exists($action_for_class)) {
			$key = array_search($module, (array)Config()->MODULES);
			if($key) {
				$file = Config()->MODULES_PATH . Config()->MODULES[$key] . DIRECTORY_SEPARATOR .'Model.php';
				if(file_exists($file)) {
					include_once($file);
				}
			}
		}
	}
}

?>