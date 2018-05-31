<?php
	
	/**
	 * Inflector Class
	 * 
	 * Convenient methods to work on english words
	 * Based on: http://dev.rubyonrails.com/file/trunk/activesupport/lib/active_support/inflections.rb
	 *
	 * @package Sweboo
	 */
	class Inflector {
		/**
		 * Stores results of each function
		 * so that we don't have to preg_match/preg_replace over and over again
		 *
		 * @var array
		 */
		static $cache = array(	'camelize' => array(),
							 	'classify' => array(),
							 	'demodulize' => array(),
								'humanize' => array(),
								'modulize' => array(),
								'ordinalize' => array(),
								'pluralize' => array(),
								'singularize' => array(),
								'tableize' => array(),
								'titleize' => array(),
								'unaccent' => array(),
								'underscore' => array(),
								'urlize' => array(),
								'variablize' => array(),
								'latinize' => array(),
								'slugalize' => array()
								);
		/**
		 * List of irregular words
		 *
		 * @var array
		 */
		static $irregular = array('person' => 'people', 'man' => 'men', 'child' => 'children', 'woman' => 'women');
		
		/**
		 * List of uncountable words
		 *
		 * @var array
		 */
		static $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep', 'police', 'news');
								
		/**
		* Pluralizes English nouns.
		*
		* @access public
		* @static
		* @param string $word English noun to pluralize
		* @return string Plural noun
		*/
	    public static function pluralize($word) {
	    	// simple caching
	    	if(isset(self::$cache['pluralize'][$word])) return self::$cache['pluralize'][$word];
	
	        $plural = array('/(quiz)$/i' => '\1zes',
					        '/^(ox)$/i' => '\1en', 						# ox
					        '/([m|l])ouse$/i' => '\1ice',				# mouse, louse
					        '/(matr|vert|ind)ix|ex$/i' => '\1ices',		# matrix, vertex, index
					        '/(x|ch|ss|sh)$/i' => '\1es',				# search, switch, fix, box, process, address
					        //'/([^aeiouy]|qu)ies$/i' => '\1y',			# seems to be a bug(?)
					        '/([^aeiouy]|qu)y$/i' => '\1ies',			# query, ability, agency
					        '/(hive)$/i' => '\1s',						# archive, hive
					        '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',	# half, safe, wife
					        '/sis$/i' => 'ses',							# basis, diagnosis
					        '/([ti])um$/i' => '\1a',					# datum, medium
					        '/(buffal|tomat)o$/i' => '\1oes',			# buffalo, tomato
					        '/(bu)s$/i' => '\1ses',						# bus
					        '/(alias|status)/i'=> '\1es',				# alias
					        '/(octop|vir)us$/i'=> '\1i',				# octopus, virus - virus has no defined plural (according to Latin/dictionary.com), but viri is better than viruses/viruss
					        '/(ax|test)is$/i'=> '\1es',					# axis, crisis
					        '/s$/i'=> 's',								# no change (compatibility)
	        				'/$/'=> 's');

	        $lowercased_word = strtolower($word);
	
	        foreach (self::$uncountable as $_uncountable){
	            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable) {
	            	// put result in cache
	                return self::$cache['pluralize'][$word] = $word;
	            }
	        }
	
	        foreach (self::$irregular as $_plural=> $_singular){
	            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
	            	// put result in cache
	                return self::$cache['pluralize'][$word] = preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
	            }
	        }
	
	        foreach ($plural as $rule => $replacement) {
	            if (preg_match($rule, $word)) {
	            	// put result in cache
	                return self::$cache['pluralize'][$word] = preg_replace($rule, $replacement, $word);
	            }
	        }
	        return false;
	    }
	
	    /**
	    * Singularizes English nouns.
	    *
	    * @access public
	    * @static
	    * @param string $word English noun to singularize
	    * @return string Singular noun.
	    */
	    public static function singularize($word) {
	    	// simple caching
	    	if(isset(self::$cache['singularize'][$word])) return self::$cache['singularize'][$word];
	
	        $singular = array ( '/(quiz)zes$/i' => '\\1',
						        '/(matr)ices$/i' => '\\1ix',
						        '/(vert|ind)ices$/i' => '\\1ex',
						        '/^(ox)en/i' => '\\1',
						        '/(alias|status)es$/i' => '\\1',
						        '/([octop|vir])i$/i' => '\\1us',
						        '/(cris|ax|test)es$/i' => '\\1is',
						        '/(shoe)s$/i' => '\\1',
						        '/(o)es$/i' => '\\1',
						        '/(bus)es$/i' => '\\1',
						        '/([m|l])ice$/i' => '\\1ouse',
						        '/(x|ch|ss|sh)es$/i' => '\\1',
						        '/(m)ovies$/i' => '\\1ovie',
						        '/(s)eries$/i' => '\\1eries',
						        '/([^aeiouy]|qu)ies$/i' => '\\1y',
						        '/([lr])ves$/i' => '\\1f',
						        '/(tive)s$/i' => '\\1',
						        '/(hive)s$/i' => '\\1',
						        '/([^f])ves$/i' => '\\1fe',
						        '/(^analy)ses$/i' => '\\1sis',
						        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis',
						        '/([ti])a$/i' => '\\1um',
						        '/(n)ews$/i' => '\\1ews',
						        '/s$/i' => '');
	
	        $lowercased_word = strtolower($word);
	        foreach (self::$uncountable as $_uncountable){
	            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
	            	// put result in cache
	                return self::$cache['singularize'][$word] = $word;
	            }
	        }
	
	        foreach (self::$irregular as $_singular => $_plural){
	            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
	            	// put result in cache
	                return self::$cache['singularize'][$word] = preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);;
	            }
	        }
	
	        foreach ($singular as $rule => $replacement) {
	            if (preg_match($rule, $word)) {
	            	// put result in cache
	                return self::$cache['singularize'][$word] = preg_replace($rule, $replacement, $word);
	            }
	        }
	        return $word;
	    }
	
	    /**
	     * Get the plural form of a word if first parameter is greater than 1
	     *
	     * @param integer $numer_of_records
	     * @param string $word
	     * @return string Pluralized string when number of items is greater than 1
	     */
	    function conditional_plural($numer_of_records, $word) {
	        return $numer_of_records > 1 ? Inflector::pluralize($word) : $word;
	    }
	
	    /**
	    * Converts an underscored or CamelCase word into a English
	    * sentence.
	    *
	    * The titleize function converts text like "WelcomePage",
	    * "welcome_page" or  "welcome page" to this "Welcome
	    * Page".
	    * If second parameter is set to 'first' it will only
	    * capitalize the first character of the title.
	    *
	    * @access public
	    * @static
	    * @param string $word Word to format as tile
	    * @param string $uppercase If set to 'first' it will only uppercase the
	    * first character. Otherwise it will uppercase all
	    * the words in the title.
	    * @return string Text formatted as title
	    */
	    public static function titleize($word, $uppercase = '') {
	    	// simple caching
	    	if(isset(self::$cache['titleize'][$word])) return self::$cache['titleize'][$word];
	        $uppercase = $uppercase == 'first' ? 'ucfirst' : 'ucwords';
	        // simple caching
	        return self::$cache['titleize'][$word] = $uppercase(Inflector::humanize(Inflector::underscore($word)));
	    }
	
	    /**
	    * Returns given word as CamelCased
	    *
	    * Converts a word like "send_email" to "SendEmail". It
	    * will remove non alphanumeric character from the word, so
	    * "who's online" will be converted to "WhoSOnline"
	    *
	    * @access public
	    * @static
	    * @see variablize
	    * @param string $word Word to convert to camel case
	    * @return string UpperCamelCasedWord
	    */
	    
	    public static function camelize($word) {
	    	// simple caching
	    	if(isset(self::$cache['camelize'][$word])) return self::$cache['camelize'][$word];

	    	if(preg_match_all('/\/(.?)/', $word, $got)){
	            foreach ($got[1] as $key => $value){
	                $got[1][$key] = '::'.strtoupper($value);
	            }
	            $word = str_replace($got[0],$got[1],$word);
	        }
	        return self::$cache['camelize'][$word] = str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9^:]+/', ' ', $word)));
	    }
	
	    /**
	    * Converts a word "into_it_s_underscored_version"
	    *
	    * Convert any "CamelCased" or "ordinary Word" into an
	    * "underscored_word".
	    *
	    * This can be really useful for creating friendly URLs.
	    *
	    * @access public
	    * @static
	    * @param string $word Word to underscore
	    * @return string Underscored word
	    */
	    public static function underscore($word) {
	    	// simple caching
	    	if(isset(self::$cache['underscore'][$word])) return self::$cache['underscore'][$word];
	    	$result = strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/', '_', preg_replace('/([a-z])([A-Z])/','\1_\2', preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2', preg_replace('/::/', '/',$word)))));
	        return self::$cache['underscore'][$word] = $result;
	    }
	
	    /**
	    * Returns a human-readable string from $word
	    *
	    * Returns a human-readable string from $word, by replacing
	    * underscores with a space, and by upper-casing the initial
	    * character by default.
	    *
	    * If you need to uppercase all the words you just have to
	    * pass 'all' as a second parameter.
	    *
	    * @access public
	    * @static
	    * @param string $word String to "humanize"
	    * @param string $uppercase If set to 'all' it will uppercase all the words
	    * instead of just the first one.
	    * @return string Human-readable word
	    */
	    public static function humanize($word, $uppercase = '') {
	    	// simple caching
	    	if(isset(self::$cache['humanize'][$word])) return self::$cache['humanize'][$word];
	
	        $uppercase = $uppercase == '1all' ? 'ucwords' : 'ucfirst';
	
	        // simple caching
	        $result = self::$cache['humanize'][$word] = $uppercase(str_replace('_',' ',preg_replace('/_id$/', '',$word)));
	        return $result;
	    }
	
	    /**
	    * Same as camelize but first char is lowercased
	    *
	    * Converts a word like "send_email" to "sendEmail". It
	    * will remove non alphanumeric character from the word, so
	    * "who's online" will be converted to "whoSOnline"
	    *
	    * @access public
	    * @static
	    * @see camelize
	    * @param string $word Word to lowerCamelCase
	    * @return string Returns a lowerCamelCasedWord
	    */
	    public static function variablize($word) {
	    	// simple caching
	    	if(isset(self::$cache['variablize'][$word])) return self::$cache['variablize'][$word];

	    	$word = Inflector::camelize($word);
	
	        // simple caching
	        return self::$cache['variablize'][$word] = strtolower($word[0]).substr($word,1);
	    }
	
	    /**
	    * Converts a class name to its table name according to rails
	    * naming conventions.
	    *
	    * Converts "Person" to "people"
	    *
	    * @access public
	    * @static
	    * @see classify
	    * @param string $class_name Class name for getting related table_name.
	    * @return string plural_table_name
	    */
	    public static function tableize($class_name) {
	    	// simple caching
	    	if(isset(self::$cache['tableize'][$class_name])) return self::$cache['tableize'][$class_name];
	
	    	// simple caching
	        return self::$cache['tableize'][$class_name] = Inflector::pluralize(Inflector::underscore($class_name));
	    }
	
	    /**
	    * Converts a table name to its class name according to rails
	    * naming conventions.
	    *
	    * Converts "people" to "Person"
	    *
	    * @access public
	    * @static
	    * @see tableize
	    * @param string $table_name Table name for getting related ClassName.
	    * @return string SingularClassName
	    */
	    public static function classify($table_name) {
	    	// simple caching
	    	if(isset(self::$cache['classify'][$table_name])) return self::$cache['classify'][$table_name];
	
	    	// simple caching
	        return self::$cache['classify'][$table_name] = Inflector::camelize(Inflector::singularize($table_name));
	    }
	
	    /**
	    * Converts number to its ordinal English form.
	    *
	    * This method converts 13 to 13th, 2 to 2nd ...
	    *
	    * @access public
	    * @static
	    * @param integer $number Number to get its ordinal value
	    * @return string Ordinal representation of given string.
	    */
	    public static function ordinalize($number) {
	    	// simple caching
	    	if(isset(self::$cache['ordinalize'][$number])) return self::$cache['ordinalize'][$number];
	
	        if (in_array(($number % 100),range(11,13))) {
	            return self::$cache['ordinalize'][$number] = $number.'th';
	        } else {
	            switch (($number % 10)) {
	                case 1:
	                	$result = $number.'st';
	                	break;
	                case 2:
	                	$result = $number.'nd';
	                	break;
	                case 3:
	                	$result = $number.'rd';
	                default:
	                	$result = $number.'th';
		                break;
	            }
	            // simple caching
	            return self::$cache['ordinalize'][$number] = $result;
	        }
	    }
	
		/**
		 * Removes the module name from a module/path, Module::name or Module_ControllerClassName.
		 *
		 *   Example:    Inflector::demodulize('admin/dashboard_controller');  //=> dashboard_controller
		 *               Inflector::demodulize('Admin_DashboardController');  //=> DashboardController
		 *               Inflector::demodulize('Admin::Dashboard');  //=> Dashboard
		 * 
		 * @access public
		 * @static
		 * @param string $module_name
		 * @return string
		 */
	    public static function demodulize($module_name) {
	    	// simple caching
	    	if(isset(self::$cache['demodulize'][$module_name])) return self::$cache['demodulize'][$module_name];
	
	        $module_name = preg_replace('/^.*::/','',$module_name);
	
	        return self::$cache['demodulize'][$module_name] = Inflector::humanize(Inflector::underscore($module_name));;
	    }

	    /**
	     * Modulize a string
	     *
	     * @access public
	     * @static 
	     * @param string $module_description
	     * @return string
	     */
	    public static function modulize($module_description) {
	    	// simple caching
	    	if(isset(self::$cache['modulize'][$module_description])) return self::$cache['modulize'][$module_description];
	    	// simple caching
	        return self::$cache['modulize'][$module_description] = Inflector::camelize(Inflector::singularize($module_description));
	    }
	
	
	    /**
	     * Transforms a string to its unaccented version.
	     * This might be useful for generating "friendly" URLs
	     * 
	     * @access public
	     * @static 
	     * @param string $text
	     * @return string
	     */
	    public static function unaccent($text) {
	    	// simple caching
	    	if(isset(self::$cache['unaccent'][$text])) return self::$cache['unaccent'][$text];
			return $text;
	    	//return self::$cache['unaccent'][$text] = 
	    	//	strtr($text, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
		    //                 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYTsaaaaaaaceeeeiiiienoooooouuuuyty');
	    }
	
		/**
		 * Creates a friendly URL from given string
		 *
		 * @access public
		 * @static 
		 * @param string $text
		 * @return string
		 */
	    public static function urlize($text) {
	    	// simple caching
	    	if(isset(self::$cache['urlize'][$text])) return self::$cache['urlize'][$text];
	        return self::$cache['urlize'][$word] = trim(Inflector::underscore(Inflector::unaccent($text)),'_');
	    }
	
	    /**
	     * Replaces cyrilic characters with their latin equivalents
	     *
	     * @access public
	     * @static 
	     * @param unknown_type $text
	     * @return unknown
	     */
	    public static function latinize($text) {
	    	// simple caching
	    	if(isset(self::$cache['urlize'][$text])) return self::$cache['urlize'][$text];
	
	    	$cyrillic = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ь', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ь', 'ю', 'я');
	    	$latin = array('A', 'B', 'V', 'G', 'D', 'E', 'J', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'CH', 'SH', 'SHT', 'Y', 'I', 'U', 'JA', 'a', 'b', 'v', 'g', 'd', 'e', 'j', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sht', 'y', 'i', 'u', 'ja');
	    	return self::$cache['latinize'][$text] = str_replace($cyrillic, $latin, $text);
	    }
	
	    /**
	     * Creates a slug
	     *
	     * @access public
	     * @static 
	     * @param string $text
	     * @return string
	     */
	    public static function slugalize($text) {
	    	// simple caching
	    	if(isset(self::$cache['slugalize'][$text])) {
	    		return self::$cache['slugalize'][$text];
            }
	    	return self::$cache['slugalize'][$text] = str_replace('_','-',Inflector::urlize(str_replace('/', '_', Inflector::latinize($text))));
	    }
	
	    /**
	     * Sanitizes a word
	     *
	     * @param string $word
	     * @return string
	     */
	    public static function sanitize($word) {
	        $word = strip_tags($word);
	        $word = htmlentities($word, ENT_NOQUOTES);
	        // Keep only one char in entities!
	        $word = preg_replace('/&(.).+?;/', '$1', $word);
	        // Remove non acceptable chars
	        $word = preg_replace('/[^A-Za-z0-9]+/', '_', $word);
	        $word = preg_replace('/^_+/', '', $word);
	        $word = preg_replace('/_+$/', '', $word);
	        // Uppercase the first character of each word in a string
	        $word = strtolower( $word );
	        preg_match('/^(.*?)(_[0-9]+)?$/', $word, $matches);
	        $base = substr( $matches[1], 0, 40 );
	        $word = $base;
	        if(isset($matches[2])) {
	            $word = $base . $matches[2];
	        }
	        return $word;
	    }	    
	    
	    /**
	    * Returns $class_name in underscored form, with "_id" tacked on at the end.
	    * This is for use in dealing with the database.
	    *
	    * @param string $class_name
	    * @return string
	    */
	    static function foreign_key($class_name, $separate_class_name_and_id_with_underscore = true) {
	        return Inflector::underscore(Inflector::demodulize($class_name)).($separate_class_name_and_id_with_underscore ? "_id" : "id");
	    }
	
	}

?>
