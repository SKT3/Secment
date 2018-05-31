<?php
    if(!defined('OPTIONAL')){
        define('OPTIONAL', false);
    }

    if(!defined('COMPULSORY')){
        define('COMPULSORY', true);
    }

    if(!defined('COMPULSORY_REGEX')){
        define('COMPULSORY_REGEX', '([^\/]+){1}');
    }

    /**
    * Native PHP URL rewriting
    */

	class Router {

		private $loaded_routes = array();

		public $routes_count = 0;

        private $current_routes = array();

		public static $cache = array();

		private static $instance = null;

		private function __construct() {
		}

		public function __clone() {
			new Exeption(__CLASS__ . " can't be cloned! It is singleton.");
		}

		public static function getInstance()
		{
	        if(self::$instance == null) {
	            self::$instance = new Router();
	        }

	        return self::$instance;
	    }

		function get_routes() {
			return $this->loaded_routes;
		}

        private function set_routes($v) {
            $this->loaded_routes = $v;
            $this->routes_count = count($v);
        }

        private function del_routes() {
            $this->loaded_routes = array();
            $this->routes_count = 0;
        }

        function apply_temp_routes($fname) {
            $this->current_routes = $this->get_routes();
            $this->del_routes();
            include_once($fname);
        }

        function remove_temp_routes()
        {
            $this->del_routes();
            Router()->set_routes($this->current_routes);
        }



        /**
        * Add a rewrite rule
        *
        *
        * Rules that are defined first take precedence over the rest.
        *
        * @access public
        * @param    string    $url_pattern    URL patterns have the following format:
        *
        * - <b>/static_text</b>
        * - <b>/:variable</b>  (will load $variable)
        * - <b>/*array</b> (will load $array as an array)
        * @param    array    $options    Options is an array with and array pair of field=>value
        * The following example <code>array('controller' => 'page')</code> sets var 'controler' to 'page'
        * if no 'controller' is specified in the $url_pattern param this value will be used.
        *
        * The following constants can be used as values:
        * <code>
        * OPTIONAL // 'var_name'=> OPTIONAL, will set 'var_name' as an option
        * COMPULSORY // 'var_name'=> COMPULSORY, will require 'var_name' to be set
        * </code>
        * @param    array    $requirements    $requirements holds an array with and array pair of field=>value
        * where value is a perl compatible regular expression that will be used to validate rewrite rules
        * The following example <code>array('id'=>'/\d+/')</code> will require that var 'id' must be a numeric field.
        *
        * NOTE:If option <b>'id'=>OPTIONAL</b> this requirement will be used in case 'id' is set to something
        * @return void
        */
        function connect($route_name, $url_pattern, $options = array(), $requirements = null)
        {

            if(!empty($options['requirements'])){
                $requirements = empty($requirements) ? $options['requirements'] : array_merge($options['requirements'],$requirements);
                unset($options['requirements']);
            }

            preg_match_all('/(([^\/]){1}(\/\/)?){1,}/',$url_pattern,$found);
            $url_pieces = $found[0];

            $regex_arr = array();
            $optional_pieces = array();
            $var_params = array();
            $arr_params = array();
            foreach ($url_pieces as $piece){

                $is_var = $piece[0] == ':';
                $is_arr = $piece[0] == '*';
                $is_constant = !$is_var && !$is_arr;

                $piece = $is_constant ? $piece : substr($piece,1);

                if($is_var && !isset($options[$piece])){
                    $options[$piece] = OPTIONAL;
                }

                if($is_arr && !isset($options[$piece])){
                    $options[$piece] = OPTIONAL;
                }

                //COMPULSORY

                if($is_constant){
                    $regex_arr[] = array('_constant_'.$piece => '('.$piece.'(?=(\/|$))){1}');
                }elseif(isset($requirements[$piece])){
                    if (isset($options[$piece]) && $options[$piece] !== COMPULSORY){
                        $regex_arr[] = array($piece=> '(('.trim($requirements[$piece],'/').'){1})?');
                    }elseif(isset($options[$piece]) && $options[$piece] !== OPTIONAL){
                        $regex_arr[] = array($piece=> '(('.trim($requirements[$piece],'/').'){1}|('.$options[$piece].'){1}){1}');
                    }else{
                        $regex_arr[] = array($piece=> '('.trim($requirements[$piece],'/').'){1}');
                    }
                }elseif(isset($options[$piece])){
                    if($options[$piece] === OPTIONAL){
                        $regex_arr[] = array($piece=>'[^\/]*');
                    }elseif ($options[$piece] === COMPULSORY){
                        $regex_arr[] = array($piece=> COMPULSORY_REGEX);
                    }elseif(is_string($options[$piece]) && $options[$piece][0] == '/' &&
                    ($_tmp_close_char = strlen($options[$piece])-1 || $options[$piece][$_tmp_close_char] == '/')){
                        $regex_arr[] = array($piece=> substr($options[$piece],1,$_tmp_close_char*-1));
                    }elseif ($options[$piece] != ''){
                        $regex_arr[] = array($piece=>'[^\/]*');
                        $optional_pieces[$piece] = $piece;
                    }
                }else{
                    $regex_arr[] = array($piece => $piece);
                }


                if($is_var){
                    $var_params[] = $piece;
                }
                if($is_arr){
                    $arr_params[] = $piece;
                }

                if(isset($options[$piece]) && $options[$piece] === OPTIONAL){
                    $optional_pieces[$piece] = $piece;
                }
            }

            foreach (array_reverse($regex_arr) as $pos=>$single_regex_arr){
                $var_name = key($single_regex_arr);
                if((isset($options[$var_name]) && $options[$var_name] === COMPULSORY) || (isset($requirements[$var_name]) && $requirements[$var_name] === COMPULSORY)){
                    $last_optional_var = $pos;
                    break;
                }
            }

            $regex = '/^((\/)?';
            $pieces_count = count($regex_arr);

            foreach ($regex_arr as $pos=>$single_regex_arr){
                $k = key($single_regex_arr);
                $single_regex = $single_regex_arr[$k];

                $slash_delimiter = isset($last_optional_var) && ($last_optional_var <= $pos) ? '{1}' : '?';

                if(isset($optional_pieces[$k])){
                    $terminal = (is_numeric($options[$k]) && $options[$k] > 0 && in_array($k,$arr_params)) ? '{'.$options[$k].'}' : ($pieces_count == $pos+1 ? '?' : '{1}');
                    $regex .= $is_arr ? '('.$single_regex.'\/'.$slash_delimiter.')+' : '('.$single_regex.'\/'.$slash_delimiter.')'.$terminal;
                }else{
                    $regex .= $is_arr ? $single_regex.'\/+' : $single_regex.'\/'.($pieces_count == $pos+1 ? '?' : $slash_delimiter);
                }
            }
            $regex = rtrim($regex ,'/').'){1}$/';
            $regex = str_replace('/^\$/','/^\\/?$/',$regex);

            $this->loaded_routes[] = array(
				'route_name' => $route_name,
	            'url_path' => $url_pattern,
	            'options' => $options,
	            'requirements' => $requirements,
	            'url_pieces' => $url_pieces,
	            'regex' => $regex,
	            'regex_array' => $regex_arr,
	            'optional_params' => $optional_pieces,
	            'var_params' => $var_params,
	            'arr_params' => $arr_params
            );
			$this->routes_count++;

        }

        function add($route_name, $url_pattern, $options = array(), $requirements = null)
        {
            return $this->connect($route_name, $url_pattern, $options, $requirements);
        }

        /**
	        *
	        * This function will inspect the rewrite rules and will return the params that match the first one.
	        *
	        * @access public
	        * @param    string    $url    URL to get params from.
	        * @return mixed Having the following rewrite rules:
	        * <code>
	        *
	        * $Router->map('/setup/*config_settings',array('controller'=>'setup'));
	        * $Router->map('/customize/*options/:action',array('controller'=>'themes','options'=>3));
	        * $Router->map('/blog/:action/:id',array('controller'=>'post','action'=>'list','id'=>OPTIONAL),array('id'=>'/\d{1,}/'));
	        * $Router->map('/:year/:month/:day', array('controller' => 'articles','action' => 'view_headlines','year' => COMPULSORY,'month' => 'all','day' => OPTIONAL) , array('year'=>'/(20){1}\d{2}/','month'=>'/((1)?\d{1,2}){2}/','day'=>'/(([1-3])?\d{1,2}){2}/'));
	        * $Router->map('/:webpage', array('controller' => 'page', 'action' => 'view_page', 'webpage' => 'index'),array('webpage'=>'/(\w|_)+/'));
	        * $Router->map('/', array('controller' => 'page', 'action' => 'view_page', 'webpage'=>'index'));
	        * $Router->map('/:controller/:action/:id');
	        * </code>
	        *
	        * We get the following results:
	        *
	        * <code>$Router->extract_params('/contact_us');</code>
	        * Produces: array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us');
	        *
	        * <code>$Router->extract_params('/');</code>
	        * Produces: array('controller'=>'page','action'=>'view_page','webpage'=>'index');
	        *
	        * <code>$Router->extract_params('');</code>
	        * Produces: array('controller'=>'page','action'=>'view_page','webpage'=>'index');
	        *
	        * <code>$Router->extract_params('/blog/');</code>
	        * Produces: array('controller'=>'post','action'=>'list','id'=>null);
	        *
	        * <code>$Router->extract_params('/blog/view');</code>
	        * Produces: array('controller'=>'post','action'=>'view','id'=>null);
	        *
	        * <code>$Router->extract_params('/blog/view/10/');</code>
	        * Produces: array('controller'=>'post','action'=>'view','id'=>'10');
	        *
	        * <code>$Router->extract_params('/blog/view/newest/');</code>
	        * Produces: array('controller'=>'blog','action'=>'view','id'=>'newest');
	        *
	        * <code>$Router->extract_params('/2005/10/');</code>
	        * Produces: array('controller' => 'articles','action' => 'view_headlines','year' => '2005','month' => '10', 'day' => null);
	        *
	        * <code>$Router->extract_params('/2006/');</code>
	        * Produces: array('controller' => 'articles','action' => 'view_headlines','year' => '2006','month' => 'all', 'day' => null);
	        *
	        * <code>$Router->extract_params('/user/list/12');</code>
	        * Produces: array('controller' => 'user','action' => 'list','id' => '12');
	        *
	        * <code>$Router->extract_params('/setup/themes/clone/12/');</code>
	        * Produces: array('controller' => 'setup','config_settings' => array('themes','clone','12'));
	        *
	        * <code>$Router->extract_params('/customize/blue/css/sans_serif/clone/');</code>
	        * Produces: array('controller' => 'themes','options' => array('blue','css','sans_serif'), 'action'=>'clone');
	        *
	        * This function returns false in case no rule is found for selected URL
	        */
        function extract_params($url)
        {
            $url = $url == '/' || $url == '' ? '/' : '/'.trim($url,'/').'/';
            $nurl = $url;

            foreach ($this->loaded_routes as $route){
                $params = array();

                if(preg_match($route['regex'], $url)){

                    foreach ($route['regex_array'] as $single_regex_arr){

                        $k = key($single_regex_arr);

                        $single_regex = $single_regex_arr[$k];
                        $single_regex = '/^(\/'.$single_regex.'){1}/';
                        preg_match($single_regex, $url, $got);
                        if(in_array($k,$route['arr_params'])){

                            $url_parts = strstr(trim($url,'/'),'/') ? explode('/',trim($url,'/')) : array(trim($url,'/'));

                            $pieces = (isset($route['options'][$k]) && $route['options'][$k] > 0) ? $route['options'][$k] : count($url_parts);

                            while ($pieces>0) {
                                $pieces--;
                                $url_part = array_shift($url_parts);
                                $url = substr_replace($url,'',1,strlen($url_part)+1);

                                if(preg_match($single_regex, '/'.$url_part)){
                                    $params[$k][] = $url_part;
                                }
                            }
                        }elseif(!empty($got[0])){
                            $url = substr_replace($url,'',1,strlen($got[0]));
                            if(in_array($k,$route['var_params'] )){
                                $param = trim($got[0],'/');
                                $params[$k] = $param;
                            }
                        }
                        if(isset($route['options'][$k])){

                            if($route['options'][$k] !== COMPULSORY &&
                            $route['options'][$k] !== OPTIONAL &&
                            $route['options'][$k] != '' &&
                            ((!isset($params[$k]))||(isset($params[$k]) && $params[$k] == ''))){
                                $params[$k] = $route['options'][$k];
                            }
                        }
                    }

                    if(isset($route['options'])){
                        foreach ($route['options'] as $option=>$value){
                            if($value !== COMPULSORY && $value !== OPTIONAL && $value != '' && !isset($params[$option])){
                                $params[$option] = $value;
                            }
                        }
                    }
                }

                if(count($params)){
                    $params = array_map(array(&$this,'url_decode'),$params);
                    return $params;
                }
            }
            return false;
        }

        /**
        * Url decode a strin or an array of strings
        */
        function url_decode($input)
        {
            if(!empty($input)){
                if (is_string($input)){
                    return urldecode($input);
                }elseif (is_array($input)){
                    return array_map(array(&$this,'url_decode'),$input);
                }
            }
            return '';
        }

        /**
        * Generates a custom URL, depending on current rewrite rules.
        *
        * Generates a custom URL, depending on current rewrite rules.
        *
        * @access public
        * @param    array    $params    An array with parameters to include in the url.
        * - <code>array('controller'=>'post','action'=>'view','id'=>'10')</code>
        * - <code>array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us')</code>
        * @return string Having the following rewrite rules:
        * <code>
        *
        * $Router->add('/setup/*config_settings',array('controller'=>'setup'));
        * $Router->add('/customize/*options/:action',array('controller'=>'themes','options'=>3));
        * $Router->add('/blog/:action/:id',array('controller'=>'post','action'=>'list','id'=>OPTIONAL),array('id'=>'/\d{1,}/'));
        * $Router->add('/:year/:month/:day', array('controller' => 'articles','action' => 'view_headlines','year' => COMPULSORY,'month' => 'all','day' => OPTIONAL) , array('year'=>'/(20){1}\d{2}/','month'=>'/((1)?\d{1,2}){2}/','day'=>'/(([1-3])?\d{1,2}){2}/'));
        * $Router->add('/:webpage', array('controller' => 'page', 'action' => 'view_page', 'webpage' => 'index'),array('webpage'=>'/(\w|_)+/'));
        * $Router->add('/', array('controller' => 'page', 'action' => 'view_page', 'webpage'=>'index'));
        * $Router->add('/:controller/:action/:id');
        * </code>
        *
        * We get the following results:
        *
        * <code>$Router->build_url(array('controller'=>'page','action'=>'view_page','webpage'=>'contact_us'));</code>
        * Produces: /contact_us/
        *
        * <code>$Router->build_url(array('controller'=>'page','action'=>'view_page','webpage'=>'index'));</code>
        * Produces: /
        *
        * <code>$Router->build_url(array('controller'=>'post','action'=>'list','id'=>null));</code>
        * Produces: /blog/
        *
        * <code>$Router->build_url(array('controller'=>'post','action'=>'view','id'=>null));</code>
        * Produces: /blog/view/
        *
        * <code>$Router->build_url(array('controller'=>'post','action'=>'view','id'=>'10'));</code>
        * Produces: /blog/view/10/
        *
        * <code>$Router->build_url(array('controller'=>'blog','action'=>'view','id'=>'newest'));</code>
        * Produces: /blog/view/newest/
        *
        * <code>$Router->build_url(array('controller' => 'articles','action' => 'view_headlines','year' => '2005','month' => '10', 'day' => null));</code>
        * Produces: /2005/10/
        *
        * <code>$Router->build_url(array('controller'</code> => 'articles','action' => 'view_headlines','year' => '2006','month' => 'all', 'day' => null));</code>
        * Produces: /2006/
        *
        * <code>$Router->build_url(array('controller' => 'user','action' => 'list','id' => '12'));</code>
        * Produces: /user/list/12/
        *
        * <code>$Router->build_url(array('controller' => 'setup','config_settings' => array('themes','clone','12')));</code>
        * Produces: /setup/themes/clone/12/
        *
        * <code>$Router->build_url(array('controller' => 'themes','options' => array('blue','css','sans_serif'), 'action'=>'clone'));</code>
        * Produces: /customize/blue/css/sans_serif/clone/
    */
        function build_url($params=array())
        {
            if($params['action']=='index') {
              $params['action'] = '';
            }

            $_cache_key = md5(serialize($params)).Registry()->locale;
            if(!isset(self::$cache[$_cache_key])){
                $parsed = '';
                foreach ($this->loaded_routes as $route){
                    $route_names[$route['route_name']] = true;
                }
                foreach ($this->loaded_routes as $route){
                    if(isset($params['route_name']) && $params['route_name'] != $route['route_name'] && array_key_exists($params['route_name'], $route_names)){
                        continue;
                    }
                    unset($params['route_name']);

                    $params_copy = $params;
                    $parsed = '';
                    $_controller = '';
                    foreach ($params_copy as $k=>$v){
                        if(isset($$k)){
                            unset($$k);
                        }
                    }
                    extract($params);

                    if(isset($route['options'])){
                        foreach ($route['options'] as $option=>$value){
                            if(
                            !empty($route['url_pieces']) &&
                            isset($route['options'][$option]) &&
                            array_search(':'.$option, $route['url_pieces']) === false &&
                            array_search('*'.$option, $route['url_pieces']) === false &&
                            (
                            is_string($value) ||
                            is_integer($value)) &&
                            (
                            !isset($params_copy[$option]
                            ) ||
                            $params_copy[$option] != $value
                            )
                            )
                            {
                                continue 2;
                            }
                            if(isset($params_copy[$option]) &&
                            $value == $params_copy[$option] &&
                            $value !== OPTIONAL &&
                            $value !== COMPULSORY)
                            {
                                if($option == 'controller'){
                                    $_controller = $value;
                                }
                                unset($params_copy[$option]);
                                unset($$option);
                            }
                        }
                    }


                    foreach ($route['arr_params'] as $arr_route){
                        if(isset($$arr_route) && is_array($$arr_route)){
                            $$arr_route = join('/',$$arr_route);
                        }
                    }

                    $_url_pieces = array();
                    foreach (array_reverse($route['url_pieces']) as $v){
                        if(strstr($v,':') || strstr($v,'*')){
                            $v = substr($v,1);
                            if(isset($params[$v])){
                                if (count($_url_pieces) || isset($route['options'][$v]) && $params[$v] != $route['options'][$v] || !isset($route['options'][$v]) || isset($route['options'][$v]) && $route['options'][$v] === COMPULSORY){
                                    $_url_pieces[] = is_array($params[$v]) ? join('/',$params[$v]) : $params[$v];
                                }
                            }
                        }else{
                            $_url_pieces[] = is_array($v) ? join('/',$v) : $v;
                        }
                    }


                    $parsed = str_replace('//','/','/'.join('/',array_reverse($_url_pieces)).'/');


                    // This might be faster but using eval here might cause security issues
                    //@eval('$parsed = "/".trim(str_replace("//","/","'.str_replace(array('/:','/*'),'/$','/'.join('/',$route['url_pieces']).'/').'"),"/")."/";');

                    if($parsed == '//'){
                        $parsed = '/';
                    }

                      if (!preg_match($route['regex'], $parsed)) {
                          continue;
                    }

                    if(is_string($parsed)){
                        if($parsed_arr = $this->extract_params($parsed)){
                            if($parsed == '/' && count(array_diff($params,$parsed_arr)) == 0){
                                self::$cache[$_cache_key] = '/';
                                return self::$cache[$_cache_key];
                            }

                            if( isset($parsed_arr['controller']) &&
                            ((isset($controller) && $parsed_arr['controller'] == $controller) ||
                            (isset($_controller) && $parsed_arr['controller'] == $_controller))){


                                if( isset($route['options']['controller']) &&
                                $route['options']['controller'] !== OPTIONAL &&
                                $route['options']['controller'] !== COMPULSORY &&
                                $parsed_arr['controller'] != $route['options']['controller'] &&
                                count(array_diff(array_keys($route['options']),array_keys($parsed_arr))) > 0){
                                    continue;
                                }

                                $url_params = array_merge($parsed_arr,$params_copy);

                                if($parsed != '/'){
                                    foreach ($parsed_arr as $k=>$v){
                                        if(isset($url_params[$k]) && $url_params[$k] == $v){
                                            unset($url_params[$k]);
                                        }
                                    }
                                }

                                foreach (array_reverse($route['url_pieces'], true) as $position => $piece){
                                    $piece = str_replace(array(':','*'),'', $piece);
                                    if(isset($$piece)){
                                        if(strstr($parsed,'/'.$$piece.'/')){
                                            unset($url_params[$piece]);
                                        }
                                    }
                                }

                                foreach ($url_params as $k=>$v){
                                    if($v == null){
                                        unset($url_params[$k]);
                                    }
                                }

                                if($parsed == '/' && !empty($url_params['controller'])){
                                    $parsed = '/'.join('/',array_diff(array($url_params['controller'],@$url_params['action'],@$url_params['id']),array('')));
                                    unset($url_params['controller'],$url_params['action'],$url_params['id']);
                                }

                                $parsed .= count($url_params) ? '?'.http_build_query($url_params,'', '&') : '';
                                self::$cache[$_cache_key] = $parsed;
								return $parsed;
                            }
                        }
                    }
                }

                (array)$extra_parameters = @array_diff($params_copy,$parsed_arr);


                if($parsed == '' && is_array($params)){
                    $parsed = '?'.http_build_query(array_merge($params,(array)$extra_parameters,'', '&amp;'));
                }
                if($parsed == '//'){
                    $parsed = '/';
                }

                $parsed .= empty($extra_parameters) ? '' : (strstr($parsed,'?') ? '&' : '?').http_build_query($extra_parameters,'', '&amp;');
                self::$cache[$_cache_key] = $parsed;
            }
            return self::$cache[$_cache_key];
        }

		function url_for($options) {
			if(is_string($options)) {
				return $options;
			} else if(is_array($options)) {
	            if (array_key_exists('protocol', $options)) {
	            	$protocol = $options['protocol'] . '://';
	            	unset($options['protocol']);
	            }

	            if (array_key_exists('port', $options)) {
	            	$host = Registry()->request->server('SERVER_NAME') . ':' . $options['port'];
	            	unset($options['port']);
	            }
                else {
                    if (isset($protocol)) {
                        $host = Registry()->request->server('SERVER_NAME');
                    }
                    else {
                        $host = Registry()->request->server('HTTP_HOST');
                    }
	            }

                if (!isset($protocol)) {
                    $protocol = Registry()->request->get_protocol();
                }

				$url_base = $protocol . $host . rtrim("/".trim(Config()->COOKIE_PATH, "/"), "/");

	            if (!array_key_exists('controller', $options)) {
	            	$options['controller'] = Registry()->request->get_controller();
	            }

	            $only_one_language = count(Config()->LOCALE_SHORTCUTS) == 1;
	            if (array_key_exists('appsys', $options)) {
	            	$appsys = array_search($options['appsys'], Config()->APPLICATIONS);
	            	$appsys = ($appsys == 'default') ? '' : '/' . $appsys . '/';
	            	unset($options['appsys']);
	            } else {
	            	$appsys = (!Registry()->app_is_default) ? '/' . Registry()->app_system_url . '/' : '';
	            }
	            $appsys = $only_one_language ? rtrim($appsys, '/') : ltrim($appsys, '/');

	            if($options['vendor']) {
	            	$vendor = $options['vendor'];
	            	unset($options['vendor']);

	            	$url = $this->build_url($options);
	            	return $url_base . '/' . $vendor . $url;
	            } else {
	                if (count(Config()->LOCALE_SHORTCUTS) == 1) {
	                    $url = $this->build_url($options);
	                    return rtrim($url_base . $appsys . $url, '/');
	                } else {
	                    $locale = substr(Registry()->locale, 0, 2);
	                    $url = $this->build_url($options);

	                    return rtrim($url_base . '/' . $appsys . $locale . $url, '/');
	                }
	            }
			}
		}

	    function load_routes() {
    		if(Registry()->app_system == 'public'){
	    		$locale = false;

	    		foreach(Config()->LOCALE_SHORTCUTS as $substr_locale => $long_locale){
	    			if(strpos($_SERVER['REQUEST_URI'],'/'.$substr_locale.'/') !== false){
	    				$locale = $substr_locale;
	    			}
	    		}

	    		if($locale === false){
	    			foreach($_SESSION as $sess){
	    				$locale = substr($sess['locale'],0,2);
	    			}
	    		}

	    		if($locale === false){
	    			$locale = substr(Config()->DEFAULT_LOCALE,0,2);
	    		}

	    		$fname = 'Routes_' . Registry()->app_system . '_' .$locale;
	    	}else{
	    		$fname = 'Routes_' . Registry()->app_system;
	    	}

	    	$fname = Config()->CONFIG_PATH . $fname . '.php';

	    	if(file_exists($fname)) {
	    		require_once($fname);
	    	}
	    }
	}

	function Router() {
		return Router::getInstance();
	}

	function url_for($options) {
		return Router::getInstance()->url_for($options);
	}

?>