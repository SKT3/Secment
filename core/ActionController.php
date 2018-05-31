<?php

	class ActionController {

		/**
	     *  Name of the controller
	     *
	     *  @var string
	     */
		private $controller;

		/**
	     *  Name of the action method in the controller class
	     *
	     *  @var string
	     */
		private $action;

		/**
	     *  Value of id parsed from URL then forced to lower case
	     *
	     *  @var string
	     */
		private $id;

		/**
	     *  Parameters for the action routine
	     *
	     *  @var string[]
	     */
		private $action_params = array();

		/**
	     *  Instance of Session
	     *
	     *  @var object
	     */
		public $session;

		/**
		 * Instance of Registry
		 *
		 * @var object
		 */
		protected $registry;

		/**
		 *  Instance of Request
		 *
		 * 	@var object
		 */
		protected $request;

		/**
		 *  Instance of Response
		 *
		 * 	@var object
		 */
		protected $response;

		/**
		 *  Instance of Localizer
		 *
		 * 	@var object
		 */
		protected $localizer;

		/**
	     *  Instance of the Templating Engine
	     *
	     *  @var object
	     */
		protected $tpl;

		/**
	     *  List of additional helper files for this controller object
	     *
	     *  Set by {@link add_helper()}
	     *  @var string[]
	     */
		private $helpers = array();

		/**
	     *  List of filters to execute before calling action method
	     *
	     *  Set by {@link ActionController::add_before_filter() add_before_filter()}
	     *  @var string[]
	     */
		private $before_filters = array();

		/**
	     *  List of filters to execute after calling action method
	     *
	     *  Set by {@link ActionController::add_after_filter() add_after_filter()}
	     *  @var string[]
	     */
		private $after_filters = array();

		/**
	     *  JavaScript files which have to be included
	     *
	     *  Set by {@link ActionController::include_javascript() include_javascript()}
	     *  @var string[]
	     */
		private $include_javascript = array();

		/**
	     *  CSS files which have to be included
	     *
	     *  Set by {@link ActionController::include_css() include_css()}
	     *  @var string[]
	     */
		private $include_css = array();

		/**
	     *  Render controller layout name
	     *
	     *  Can be overridden in the child controller to false or other layout
	     *  @var boolean
	     */
		protected $layout = 'index';

		/**
		 * Possible to change the view template by the controller
		 *
		 * @var string
		 */
		protected $view = null;

	    /**
	     * Is render performed
	     *
	     * @var boolean
	     */
	    private $performed_render = false;


		/**
	     *  Constructor
	     */
		function __construct() {}

		/**
	     * Magic method useful for easier setup of different properties
	     */
		function __set($key, $value) {
	        if($key == "before_filter") {
	            $this->add_before_filter($value);
	        } elseif($key == "after_filter") {
	            $this->add_after_filter($value);
	        } elseif($key == "helper") {
	            $this->add_helper($value);
	        } elseif($key == "redirect_to") {
	            $this->redirect_to($value);
	        } else {
	        	$this->$key = $value;
	        }
		}

		/**
	     * Magic method useful for easier setup of different properties
	     */
	    function __call($method_name, $parameters) {
            if($method_name == "before_filter") {
                $result = $this->add_before_filter($parameters);
            } elseif($method_name == "after_filter") {
                $result = $this->add_after_filter($parameters);
            } elseif($method_name == "helper") {
                $result = $this->add_helper($parameters);
            }
	        return $result;
	    }

	    /**
	     * Creates and returns the requested controller object
	     *
	     * @param string $controller
	     */
	    public static function factory($controller) {
            include_once(Config()->CONTROLLERS_PATH.'application_controller.php');
            if ($controller != 'application') {

            	if (Registry()->app_system != 'public') {
            		include_once(Config()->CONTROLLERS_PATH . Registry()->app_system . '/' . Registry()->app_system.'_controller.php');
            	}

            	if (Registry()->app_system == 'modules') {
            		$controller_file = Config()->MODULES_PATH . basename($controller).'/Controller.php';
            	} else {
            		if(Registry()->in_app) {
            			$controller_file = Config()->MODULES_PATH . basename($controller).'/Public.php';
            		} else {
            			$controller_file = Config()->CONTROLLERS_PATH . Registry()->app_system . '/' . basename($controller).'_controller.php';
            		}

            	}
                if(!is_file($controller_file) || !include_once($controller_file)) {
					throw new FileMissingException($controller_file);
				}
				$controller_class = Inflector::camelize($controller).'Controller';
				if(!class_exists($controller_class, false)) {
					throw new ClassMissingException($controller_class);
				}
            }
            $controller_class = Inflector::camelize($controller."Controller");

            // create the controller
			return new $controller_class;
	    }

	    /**
	     * Process this Request when an exception occured
	     *
	     * @param Request $request
	     * @param Response $response
	     * @param Exception $exception
	     * @return Response
	     */
	    public static function process_with_exception(Request $request, Response $response, Exception $exception) {
	    	// uncomment this if you want to go to Error 404 page if something wrong happen
	    	//$response->redirect(Config()->COOKIE_PATH . Registry()->app_system . '/404');
	    	$content = $response->get_content();
	        if(ob_get_length()) {
	            ob_end_clean();
	        }
	        if($request->is_xhr()) {
	          $trace = $exception->getTrace();
	          $dump = sprintf("Got %s\nat line %d in %s\n%s", get_class($exception), @$trace[0]['line'], trim(str_replace(dirname(dirname(__FILE__)), '', @$trace[0]['file']), '\\'), $exception->getMessage());
	        } else {
	        	if($exception instanceof SoapFault) {
	        		d($exception);
	        	}
	        	d($exception);exit;
				$dump = $exception->dump();
	        }

	        $response->set_content($dump);
	        $response->set_status(200);
	        return $response;
	    }

	    /**
	     * Will process the request returning the resulting response
	     *
	     * @param Request request, the request
	     * @param Response response, the response
	     * @return Response
	     */
	    public final function process(Request $request, Response $response) {

	    	if('modules'==Registry()->app_system) {
	    		$params = Registry()->request->get_action_params();
	    		if($params['module']) {
			    	$request->set_controller($params['module']);
					$request->set_action($params['maction']);
				}
	    	}
	    	$this->instantiate($request, $response);
	    	$this->load_models();
	        $this->load_helpers();
	        // Execute the filter chains and perform the action
        	if ($this->execute_filters('before') !== false) {
        		$status = $this->perform_action($this->action);
    			$this->execute_filters('after');
        		// if called render return the response
        		if ($this->performed_render) return $response;
        	}
        	// render the view
        	$content = $this->render(array('action' => (is_null($this->view) ? $this->action : $this->view)));

			return $response;
	    }

	    /**
	     * Act as an internal constructor.
	     *
	     * @param Request request, the request
	     * @param Response response, the response
	     */
	    private function instantiate(Request $request, Response $response) {
	        // class memebers
	        $this->registry = Registry();
	        $this->registry->controller = $this;
	        $this->registry->cache = CacheFactory::factory();
	        $this->request  = $request;
	        $this->response = $response;
	        $this->params   = $request->get_params();
	        $this->controller = $request->get_controller();
	        $this->action = $request->get_action();
	        $this->action_params = $request->get_action_params();
	        $this->session  = $request->get_session();
			// create localizer
	        $this->localizer = Localizer($this->controller);
	        $this->localizer->load($this->registry->locale);
	        // init the templating engine
	        $this->init_templating_engine();
	    }

	    /**
	     * Performs the action
	     *
	     * @param string $action_name
	     */
	    private function perform_action($action_name) {
	        $action = $this->create_method($action_name);
	        $this->action = strtolower($action_name);
	        // First we check if there is such action which is not static,
	        // not a constructor or throw an exception
	        if (!$action || $action->isStatic() || $action->isConstructor()) {
	        	throw new ActionException($this->controller, $action_name);
	        }
	        // If everything is okay - invoke the action
	        else {
	        	$action->invoke($this, $this->action_params);
	        }
	    }

	    /**
	     * Init controller models
	     *
	     * @param void
	     * @access private
	     */
	    private function load_models() {
	        if (!is_array($this->models)) {
	        	$this->models = (strpos($this->models, ',') !== false) ? explode(',', $this->models) : explode(' ', $this->models);
	        	$this->models = array_map('trim', $this->models);
	        }
	        foreach ($this->models as $model) {
	            if (trim($model) != '') {
	            	$model_class = Inflector::camelize($model);
	            	if (Registry()->app_system == 'modules') {
	            		$model_file = Config()->MODULES_PATH . basename($this->controller).'/Model.php';
	            	}else{
	            		$model_file = Config()->MODELS_PATH . Inflector::underscore($model) . '.php';
	            	}
					if (is_file($model_file)) {
						include_once($model_file);
						$this->$model_class = new $model_class;
					}
	        	}
	        }
	    }

		/******************************************************************
		 * HELPERS
		 ******************************************************************/

	    /**
	     *  Add a helper to the list of helpers used by a controller
	     *  object
	     *
	     *  @param $helper_name string Name of a helper to add to the list
	     */

	    protected function add_helper($helper_name) {
	        if(!in_array($helper_name, $this->helpers))
	            $this->helpers[] = $helper_name;
	    }

	    /**
	     * Load helpers method
	     *
	     * @param void
	     * @access private
	     */
	    private function load_helpers() {
			// load the default helper
			include_once(Config()->CORE_PATH . 'BaseHelper.php');
			$this->base_helper = $this->registry->base_helper = new BaseHelper();

	    	// load flash message helper
			include_once(Config()->HELPERS_PATH . 'FlashMessageHelper.php');
			$this->flash_message_helper = $this->registry->flash_message_helper = new FlashMessageHelper();

			// the default controller helper
			if (is_file(Config()->HELPERS_PATH . Inflector::camelize($this->controller.'_helper') . '.php')) {
				$helper_class = Inflector::camelize($this->controller.'_helper');
				include_once(Config()->HELPERS_PATH . $helper_class . '.php');
				$this->{$this->controller.'_helper'} = $this->registry->{$this->controller.'_helper'} = new $helper_class;
			}

			$default = array('base', 'flash_message', $this->controller);
			// include controller helpers
			foreach($this->helpers as $helper) {
				if (in_array($helper, $default)) continue;
				// check if helper file exists in the lib/ directory
				$helper_class = Inflector::camelize($helper.'_helper');
				$helper_path_in_lib = Config()->CORE_PATH.$helper_class.'.php';

				// check for the helper in the helpers directory
				$helper_path_default = Config()->HELPERS_PATH.$helper_class.'.php';

				if(file_exists($helper_path_default)) {
    				include_once($helper_path_default);
    			} elseif(file_exists($helper_path_in_lib)) {
    				include_once($helper_path_in_lib);
    			} else {

    			}

				if (class_exists($helper_class, false)) {
					if(!isset($this->registry->{$helper.'_helper'})) {
						$this->registry->{$helper.'_helper'} = new $helper_class;
					}
					$this->{$helper.'_helper'} = $this->registry->{$helper.'_helper'};
				}
			}
	    }

		/******************************************************************
		 * TEMPLATE ENGINE AND RENDERING
		 ******************************************************************/

		/**
		 * Init the templating engine
		 *
		 * @param void
		 * @return void
		 */
		private function init_templating_engine() {
			require_once(Config()->LIB_PATH.'smarty/SmartyBC.class.php');
			$template = new SmartyBC();
			$this->registry->tpl = $this->tpl = $template;

			// Smarty default config
			$template->force_compile = Config()->DEVELOPMENT;
			$template->template_dir = Config()->VIEWS_PATH;
			$template->compile_dir = Config()->VIEWS_COMPILE_PATH;
			$template->cache_dir = Config()->VIEWS_CACHE_PATH;
			$template->plugins_dir[] = Config()->LIB_PATH . 'smarty/custom_plugins';

			$template->assign('_languages', Config()->LOCALE_SHORTCUTS);

			$template->assign('_root',Config()->COOKIE_PATH);
			$template->assign('_public',Config()->PUBLIC_URL);
			$template->assign('_locale', $this->registry->locale);
			$template->assign('_current_lang', strtolower(substr($this->registry->locale,0,2)));
			$template->assign('_labels', $this->localizer->get_labels());
			$template->assign('_controller', $this->controller);
			$template->assign('_action', $this->action);
			$template->assign('_id', isset($this->action_params['id']) ? $this->action_params['id'] : null);
			$template->assign('_url_params', $this->action_params);
			$template->registerPlugin("function",'render_partial', array($this, 'render_partial'));
			$template->registerPlugin("function",'url_for', array(Router::getInstance(), 'url_for'));
			if(Config()->DEVELOPMENT == false) {
				$template->load_filter('output','trimwhitespace');
			}

		}

	    /**
	     * Renders content
	     *
	     * @param mixed $options
	     * @return string
	     */
		protected function render($options = null) {

			// if called without parameters render the current action
			if (empty($options)) {
				$options['action'] = $this->action;
			}
			// render one of the cases based on given options
			if (is_array($options)) {
				if (isset($options['action'])) {
					return $this->render_action($options['action']);
				}
				else if (isset($options['text'])) {
					return $this->render_text($options['text']);
				}
				else if (isset($options['partial'])) {
					return $this->render_partial($options['partial'], array_diff_key($options, array('partial' => 'partial')));
				}
				else if (isset($options['file'])) {
					return $this->return_file($options['file'], @$options['locals']);
				}
			}
			// if is object
			else if (is_object($options) && $options instanceof BaseHelper) {
				return $this->render_with_layout((string)$options, $this->get_layout_filename());
			}
			// if is string
			else if (is_string($options)) {
				return $this->render_text($options);
			}
			// render nothing
			else {
				return $this->render_text('');
			}
		}

		/**
		 * Renders the content using the given layout
		 *
		 * @param string $content
		 * @param string $layout
		 * @return string
		 */
		private function render_with_layout($content, $layout) {
	    	$this->tpl->assign('content_for_layout', $content);
	    	$this->tpl->assign('include_javascript', $this->include_javascript);
			$this->tpl->assign('include_css', $this->include_css);
			$this->tpl->assign(get_object_vars($this));
			$content = $this->tpl->fetch($layout);

			return $this->render_text($content);
		}

		/**
		 * Renders the content without using any layout
		 *
		 * @param string $content
		 * @return string
		 */
		private function render_without_layout($content) {
			return $this->render_text($content);
		}

		/**
		 * Render an action
		 *
		 * Action rendering is the most common form and the type used automatically by
		 * Action Controller when nothing else is specified. By default, actions are
		 * rendered within the current layout (if one exists).
		 *
		 * == Renders the template for the action "goal" within the current controller
		 * $this->render_action('goal');
		 *
		 * == Renders the template for the action "short_goal" within the current controller,
		 * but without the current active layout
		 * $this->render_action('short_goal', false);
		 *
		 * == Renders the template for the action "long_goal" within the current controller,
		 * but with a custom layout
		 * $this->render_action('long_goal', 'spectacular');
		 *
		 * @param string $action
		 * @param mixed $layout
		 * @return string $content
		 */
		protected function render_action($action, $layout = null) {
			$this->tpl->assign('include_javascript', $this->include_javascript);
			$this->tpl->assign('include_css', $this->include_css);
			// finally assign all local variables to template variables
			$this->tpl->assign(get_object_vars($this));

			if($this->registry->app_system == 'modules'){
				$view_file = Config()->MODULES_PATH .  $this->controller . '/views/' . $action . '.htm';
			}else{
				$view_file = Config()->VIEWS_PATH . '/' . $this->registry->app_system . '/' . $this->controller . '/' . $action . '.htm';
			}

			$content_for_layout = '';
			if (file_exists($view_file)) {
	    		$content_for_layout = $this->tpl->fetch($view_file);
	    	}
	    	if (isset($layout)) $this->layout = $layout;

        	return ($layout_file = $this->get_layout_filename()) ? $this->render_with_layout($content_for_layout, $layout_file) : $this->render_without_layout($content_for_layout);
		}

	    /**
	     * Rendering partials
	     *
	     * Partial rendering is most commonly used together with Ajax calls that
	     * only update one or a few elements on a page without reloading. Rendering
	     * of partials from the controller makes it possible to use the same partial
	     * template in both the full-page rendering (by calling it from within the
	     * template) and when sub-page updates happen (from the controller action
	     * responding to Ajax calls). By default, the current layout is not used.
	     *
	     * == Renders the partial, making $new_person available through
  		 * the local variable 'person'
		 * render_partial("person", $new_person)
		 *
		 * == Renders the same partial with a local variable.
		 * render_partial("person", array("locals" => array("name" => "david")))
		 *
		 * == Renders a collection of the same partial by making each element of
		 * $winners available through the local variable "person" as it builds
		 * the complete response.
		 * render_partial("person", array("collection" => $winners))
		 *
		 * == Renders a collection of partials but with a custom local variable name
		 * render_partial("person", array("collection" => $winners, "as" => "person"))
		 *
		 * == Renders the same collection of partials, but also renders the partial
		 * divider between each one.
		 * render_partial("person", array("collection" => $winners, "spacer_template" => "person_divider"))
	     *
	     * @param string $partial
	     * @param mixed $options
	     * @return string
	     */
		public function render_partial($partial = null, $options = array()) {

			// a little trick when called within smarty template
			if (is_array($partial)) {
				//$options = $partial;
				$data = $partial;
				$options = array();
				$partial = $data['partial'];
				$options['collection'] = array('obj'=>(object)$data);

				//$options = $options['object'];

			}

			if(!$this->module){
			// now let's find out the template file we will use for rendering this partial
				$file = Config()->VIEWS_PATH . '/' . $this->registry->app_system . '/';
				$file .= (strpos($partial, '/') !== false) ? dirname($partial) . '/_' . basename($partial) : $this->controller . '/_' . $partial . '.htm';
			}else{
				$file = Config()->MODULES_PATH . $this->module . '/views/';
				$file .= (strpos($partial, '/') !== false) ? dirname($partial) . '/_' . basename($partial) : '/_' . $partial . '.htm';
			}


			if (is_file($file)) {

				$filename = substr(basename($file), 1, -4);
	        	$locals = isset($options['locals']) ? $options['locals'] : null;
	        	if (isset($options['collection']) && is_array($options['collection'])) {
					if (isset($options['spacer_template'])) {
		        		$spacer_file = Config()->VIEWS_PATH . '/' . $this->registry->app_system . '/';
		        		$spacer_file .= (strpos($options['spacer_template'], '/') !== false) ? dirname($options['spacer_template']) . '/_' . basename($options['spacer_template']) : $this->controller . '/_' . $options['spacer_template'] . '.htm';
		                $add_spacer = is_file($spacer_file);
		        	}
		        	// if is set $options['as'] then render the collection of partials
		        	// but with a given custom local variable name
					$local_var = (isset($options['as'])) ? $options['as'] : $filename;
		        	$locals[$local_var . '_counter_total'] = count($options['collection']);
					// store all rendered output
					$content = array();
		        	foreach($options['collection'] as $value) {
						${$local_var . '_counter'}++;
	                    $locals[$local_var] = $value;
	                    $locals[$local_var . '_counter'] = ${$local_var . '_counter'};
						$content[] = $this->render_file($file, $locals);
						$this->performed_render = false;
						if ($add_spacer && (${$local_var . '_counter'} < $locals[$local_var . '_counter_total'])) {
							$content[] = $this->render_file($spacer_file, $locals);
							$this->performed_render = false;
						}
					}
					$content = join("\n", $content);
	        	} else {
	        		// if locals are not set then the second parameter is the passed var so
	        		// make it available through the local variable named after the partial
					if (is_null($locals)) {
						$locals[$filename] = $options;
					}
	        		$content = $this->render_file($file, $locals);
	        	}
	        } else {
	        	$content = '<blockquote style="padding: 10px;margin-right: 0;margin-left: 0;background: lightblue;">Error: Partial <strong>' . $file . '</strong> not found.</blockquote>';
	        }
            $this->performed_render = false;
	        return $content;
		}

		/**
		 * Render file
		 *
		 * File rendering works just like action rendering except that it takes a
		 * filesystem path. By default, the path is assumed to be absolute, and the
		 * current layout is not applied.
		 *
		 * @param string $file
		 * @param array $locals
		 * @return string $content
		 */
		protected function render_file($file, $locals = array()) {
			// now let's find out the template file
			if(!$this->module){
				$views_path = Config()->VIEWS_PATH . '/' . $this->registry->app_system . '/';
			}else{
				$views_path = Config()->MODULES_PATH;
			}
			if (strpos($file, $views_path) !== false) {
				$filename = $file;
			} else {
				$filename = $views_path;
				$filename .= (strpos($file, '/') !== false) ? $file : $this->controller . '/_' . $file . '.htm';
			}

			if (is_file($filename)) {
				// create new instance of the templating engine and unassign non system values
				$renderer = clone($this->tpl);
				$system_vars = array('SCRIPT_NAME', '_languages', '_root', '_public', '_locale', '_current_lang', '_labels', '_controller', '_action', '_id');
				$renderer->clear_assign(array_keys(array_diff_key($renderer->get_template_vars(), array_combine($system_vars, $system_vars))));
				$renderer->assign($locals);
				$content = $renderer->fetch($filename);
	        } else {
	        	$content = '<blockquote style="padding: 10px;margin-right: 0;margin-left: 0;background: lightblue;">Error: File <strong>' . $file . '</strong> not found.</blockquote>';
	        }

			return $this->render_text($content);
		}

	    /**
	     * Will render some text.
	     *
	     * This method is useful when you want to output some text without using the template engine
	     *
	     * In case the action was already performed we will silently exit,
	     * otherwise, we set the response status and body and
	     * switch the performed_render flag to <i>TRUE</i>
	     *
	     * @param string $content [optional]the text you want to send, default is an empty string
	     * @param int $status
	     */
	    protected function render_text($content = '', $status = 200) {
    	    if ($this->performed_render) return;
            $this->performed_render = true;
            $this->response->set_status($status);
	        $this->response->set_content($content);
	        return $content;
	    }

	    /**
	     * Will render the content as JSON and set the proper headers
	     *
	     * @param unknown_type $content
	     * @return unknown
	     */
		protected function render_json($content) {
		    $this->response->set_content_type('application/json');
		    $content = json_encode($content);
		    $this->response->add_header('X-JSON', '('.$content.')');
		    return $this->render_text($content);
		}

	    /**
	     * Find and return the local path to layout
	     *
	     * @return string path to layout
	     */
	    private function get_layout_filename() {
	    	$layout_name = array();

			if(is_null($this->layout)) {
	    		return false;
	    	} elseif(!empty($this->layout)) {
	    		if(is_array($this->layout)) {

	    			// check for :except and :only directives
	    			foreach($this->layout as $key => $layout_entry) {
	    				// the :only directive overrides :except, so it should be evaluated first
	    				if(is_array($layout_entry[":only"]) && in_array($this->action, $layout_entry[":only"])) {
	    					$layout_name[] = $key;
	    					break;
	    				} elseif(is_array($layout_entry[":except"]) && !in_array($this->action, $layout_entry[":except"])) {
	    					$layout_name[] = $key;
	    					break;
	    				}
	    			}
	    		} else {
	    			$layout_name[] = $this->layout;
	    		}
	    	} else {
	    		// base the name of the layout off of the controller name
	    		$layout_name[] = $this->controller;
	    		// or fall back to a layout, called "index"
	    		$layout_name[] = 'index';
	    	}
	    	if($this->registry->app_system == 'modules'){
	    		$appsystem = 'admin';
	    	}else{
	    		$appsystem = $this->registry->app_system;
	    	}
	    	// return the first layout that exists
	    	$layouts_path = Config()->LAYOUTS_PATH .  $appsystem . '/';
	    	if(!empty($layout_name)) {
		    	foreach($layout_name as $proposed_layout) {
			    	$layout_path = $layouts_path.$proposed_layout.'_layout.htm';
			    	if(is_file($layout_path)) return $layout_path;
		    	}
	    	}
	    	return false;
	    }

		/******************************************************************
		 * FILTERS
		 ******************************************************************/

	    /**
	     * Prepends a before filter
	     *
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     *
	     */
	    protected function prepend_before_filter($filter_definition) {
			$this->prepend_filter_chain('before', $filter_definition);
	    }

	    /**
	     *  Append a before filter to the filter chain
	     *
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     *
	     */
	    protected function append_before_filter($filter_definition) {
			$this->append_filter_chain('before', $filter_definition);
	    }

	    /**
	     *  Append a before filter to the filter chain
	     *
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     *
	     */
	    protected function add_before_filter($filter_definition) {
	        $this->append_before_filter($filter_definition);
	    }

	    /**
	     * Prepends a before filter
	     *
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     *
	     */
	    protected function prepend_after_filter($filter_definition) {
    		$this->prepend_filter_chain('after', $filter_definition);
	    }

	    /**
	     *  Append an after filter to the filter chain
	     *
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     */
		protected function append_after_filter($filter_definition) {
			$this->append_filter_chain('after', $filter_definition);
		}

	    /**
	     *  Append an after filter to the filter chain
	     *
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     */
	    protected function add_after_filter($filter_definition) {
	    	$this->append_after_filter($filter_definition);
	    }

	    /**
	     *  Prepend a filter to specified filter chain
	     *
	     *  @param string $chain
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     */
		protected function prepend_filter_chain($chain = null, $filter_definition) {
			if (is_null($chain)) {
				return false;
			}
			if(is_string($filter_definition) && !empty($filter_definition)) {
	            if(!in_array($filter_definition, $this->{$chain.'_filters'}))
	                array_unshift($this->{$chain.'_filters'}, $filter_definition);
	        }
	        elseif(is_array($filter_definition)) {
	        	if(!empty($filter_definition)) {
	        		foreach(array_reverse($filter_definition, true) as $key => $filter_options) {
        				if (in_array($filter_definition, $this->{$chain.'_filters'})) continue;
        				if (is_array($filter_options)) {
        					array_unshift($this->{$chain.'_filters'}, array($key => $filter_options));
        				} else if (is_string($filter_options)) {
        					array_unshift($this->{$chain.'_filters'}, $filter_options);
        				}
	        		}
	        	} else {
					$this->before_filters = $filter_definition;
				}
	        }
		}

	    /**
	     *  Append a filter to specified filter chain
	     *
	     *  @param string $chain
	     *  @param mixed $filter_definition  String with the name of
	     *  one filter function, or array of options
	     */
		protected function append_filter_chain($chain = null, $filter_definition) {
			if (is_null($chain)) {
				return false;
			}
	        if(is_string($filter_definition) && !empty($filter_definition)) {
	            if(!in_array($filter_definition, $this->{$chain.'_filters'}))
	                $this->{$chain.'_filters'}[] = $filter_definition;
	        }
	        elseif(is_array($filter_definition)) {
	        	if(!empty($filter_definition)) {
	        		foreach($filter_definition as $key => $filter_options) {
        				if (in_array($filter_definition, $this->{$chain.'_filters'})) continue;
        				if (is_array($filter_options)) {
        					$this->{$chain.'_filters'}[] = array($key => $filter_options);
        				} else if (is_string($filter_options)) {
        					$this->{$chain.'_filters'}[] = $filter_options;
        				}
	        		}
	        	} else {
					$this->{$chain.'_filters'} = $filter_definition;
				}
	        }
		}

	    /**
	     * Execute filter chain
	     *
	     * @param string $type
	     * @access private
	     * @return void
	     */
	    private function execute_filters($type) {
	    	if (!isset($type)) return false;
	    	$filters = $this->{$type.'_filters'};

	    	if (!count($filters)) return null;

	    	foreach($filters as $key => $filter) {
	        	if (is_array($filter)) {
	        		$name = null;
					$options = current($filter);
					if (isset($options['only']) && !is_array($options['only'])) {
						$options['only'] = array($options['only']);
					}
					if (isset($options['except']) && !is_array($options['except'])) {
						$options['except'] = array($options['except']);
					}
                    if ((isset($options['only']) && in_array($this->action, $options['only']))
                    	|| (isset($options['except']) && !in_array($this->action, $options['except']))
                    	|| (!isset($options['only']) && !isset($options['except']))) {
                    	$name = trim(key($filter));
                    }

	        	} else {
	        		$name = trim($filter);
	        	}
	            // Try to create the method if the name is set
	            if(is_null($name) || !$method = $this->create_method($name)) {
	                continue;
	            }
	            // Check if is declared as protected
	            if (!$method->isProtected() || $method->isStatic()) {
	            	continue;
	            }
	            // Execute the filter and break the chain if it returns false
	            if ($this->$name() === false) {
	            	return false;
	            }
	        }
	    }

		/******************************************************************
		 * SUPPORT METHODS
		 ******************************************************************/

	    /**
	     * By using the php Reflection API we create
	     * in a safty way the method with the name $method_name on this object
	     *
	     * @return RelfectionMethod or FALSE in case of failure.
	     */
	    private function create_method($method_name) {
	        try {
	            return new ReflectionMethod($this, strtolower($method_name));
	        } catch (ReflectionException $rEx) {
	            return false;
	        }
	    }

	    /**
	     * Add to queue single js file or many js files which have to be included
	     *
	     * @param mixed $files
	     */
	    protected function include_javascript($files) {
	    	$files = is_array($files) ? $files : array($files);
	    	$this->include_javascript = array_merge($this->include_javascript, $files);
	    }

	    /**
	     * Add to queue single css file or manu css files which have to be included
	     *
	     * @param mixed $files
	     */
	    protected function include_css($files) {
	    	$files = is_array($files) ? $files : array($files);
	    	$this->include_css = array_merge($this->include_css, $files);
	    }

	    /**
	     * Get value of {@link $action}
	     *
	     * @return string
	     */
	    function get_action_name() {
	    	return $this->action;
	    }

	    /**
	     * Get value of {@link $controller}
	     *
	     * @return string
	     */
	    function get_controller_name() {
	    	return $this->controller;
	    }

	    /**
	     * Get value of {@link $controller}
	     *
	     * @return array
	     */
	    function get_action_params() {
	    	return $this->action_params;
	    }

	    function get_action_param($param) {
	    	return $this->action_params[$param];
	    }

		/**
		 * Redirects the current Response
		 *
		 * @param mixed $params action to redirect to
		 * @return void
		 */
	    protected function redirect_to($params = null) {

	    	if ($this->action_performed) return;
	    	if (is_string($params)) {
	    		if (preg_match('/^\w+:\/\/.*/', $params)) {
	    			$this->response->redirect($params);
	    		} elseif ($params == 'back' && !is_null($this->request->server('HTTP_REFERER')) && preg_match('/^\w+:\/\/.*/', $this->request->server('HTTP_REFERER'))) {
					$this->redirect_to($this->request->server('HTTP_REFERER'));
	    		} else {
	    			$this->redirect_to($this->request->get_protocol() . $this->request->get_host_and_port() . $params);
	    		}
	    	} else {
	    		$this->redirect_to(Router::getInstance()->url_for($params));
	    	}
	    }

	    /**
	     * Check if is XMLHttpRequest
	     *
	     * @return boolean
	     */
	    protected function is_xhr() {
			return $this->request->is_xhr();
	    }

	    /**
	     * Check if is POST
	     *
	     * @return boolean
	     */
	    protected function is_post() {
	    	return $this->request->is_post();
	    }


	}

?>