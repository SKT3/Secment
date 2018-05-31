<?php

	abstract class Sql {

		/**
		 * Data source name
		 *
		 * @var string
		 * @access protected
		 */
		protected $dsn;

		/**
		 * Database resource
		 *
		 * @var resource
		 * @access protected
		 */
		protected $resource;

		/**
		 * The charset used for database connection. The property is only used
		 * for MySQL and PostgreSQL databases. Defaults to null, meaning using default charset
		 * as specified by the database.
		 *
		 * @var string
		 * @access protected
		 */
		protected $charset = 'utf8';

		/**
		 * Database specific timestamp format
		 *
		 * @var string
		 * @access protected
		 */
		protected $timestamp = 'Y-m-d H:i:s';

		/**
		 * Keeps all queries for current page
		 *
		 * @var string[]
		 * @static
		 * @access protected
		 */
		protected static $stored_queries = array();

		/**
		 * Stored info for table information and table exists
		 *
		 * @var array
		 * @static
		 * @access protected
		 */
		static protected $cache = array('table_info', 'table_exists');

		/**
		 * Keeps all SQL Queries cache
		 *
		 * @var array
		 * @static
		 * @access protected
		 */
		protected static $sql_cache = array();

		/**
		 * Constructor
		 *
		 * @param array $dsn
		 */
		function __construct(array $dsn) {
			$this->dsn = $dsn;
		}

		/**
		 * This "magic" method is invoked upon serialize() and works in tandem with the __wakeup()
		 * method to ensure that your database connection is serializable.
		 *
		 * @return array The class variable names that should be serialized.
		 * @see __wakeup()
		 */
	    public function __sleep() {
	        return array('dsn');
	    }

	    /**
	     * This "magic" method is invoked upon unserialize().
	     * This method will re-connects to the database using the information that was
	     * stored using the __sleep() method.
	     * @see __sleep()
	     */
	    public function __wakeup() {
	        $this->connect();
	    }

		/**
		 * Opens or reuses a connection to a sql server
		 *
		 * @return boolean
		 */
		abstract public function connect();

	    /**
	     * Returns false if connection is closed.
	     * @return boolean
	     */
	    public function is_connected() {
	        return !empty($this->resource);
	    }

		/**
		* Close db connection
		*
		* @return boolean
		*/
		abstract public function close();

		/**
		 * Raw query to the database with cache options
		 *
		 * @param string $query
		 * @param int $cache_ttl
		 */
		function query($query, $cache_ttl = 0) {
			$query = trim($query);

			if ($cache_ttl && stripos($query, 'select') === 0) {
				// store the query
				self::$stored_queries[] = '<font color="#CCCCCC">FROM CACHE :: ' . $query.'</font>';
				// remove extra spaces and tabs
				$query_stripped = preg_replace('/[\n\r\s\t]+/', ' ', $query);
				// generates query id
				$query_id = md5($query);
				// if the query result is already in sql_cache - return it from there
				// there is no need to access the file system to get the results
				if (isset(self::$sql_cache[$query_id]) && self::$sql_cache[$query_id]['expire'] >= time()) {
					return new SqlCacheResult(self::$sql_cache[$query_id]);
				}
				$filename = Config()->ROOT_PATH . 'cache/sql/' . $query_id . '.php';
				if (!is_file($filename)) {
					return new SqlCacheResult($this->cache_query($query, $query_id, $filename, $cache_ttl));
				}
				// include the file
				include($filename);
				// if the cache is expired - regenerate it and return the cached result recorset
				if (self::$sql_cache[$query_id]['expire'] < time()) {
					return new SqlCacheResult($this->cache_query($query, $query_id, $filename, $cache_ttl));
				}
				// so we gonna use the cache from the file included and return the cached results
				return new SqlCacheResult(self::$sql_cache[$query_id]);
			} else {
				// store the query
				self::$stored_queries[] = $query;
				// return the result
				return $this->queryexec($query);
			}
		}

		/**
		 * Raw query to the database
		 *
		 * @param string $query
		 */
		abstract protected function queryexec($query);

		/**
		 * Caches a query to the file system
		 *
		 * @param string $query
		 * @param string $query_id
		 * @param string $filename
		 * @param int $ttl
		 * @return array
		 */
		protected function cache_query($query, $query_id, $filename, $ttl = 600) {
			if ($fp = @fopen($filename, 'wb')) {
				@flock($fp, LOCK_EX);
				self::$sql_cache[$query_id] = array();
				$result = $this->queryexec($query);

				foreach ($result as $row) {
					self::$sql_cache[$query_id]['results'][] = $row->fetch();
				}

				self::$sql_cache[$query_id]['expire'] = (time() + $ttl);
				self::$sql_cache[$query_id]['count'] = count($result);

				$file = "<?php\n\n/* " . str_replace('*/', '*\/', $query) . " */\n";
				fwrite($fp, $file . "\nself::\$sql_cache['".$query_id."'] = " . var_export(self::$sql_cache[$query_id], true) . ";\n?>");
				@flock($fp, LOCK_UN);
				fclose($fp);
				@chmod($filename, 0666);
				return self::$sql_cache[$query_id];
			} else {
				return array();
			}
		}

		/**
		 * Generate and execute select query (for downwards compatability but not recommended)
		 * Added extra parameter $ttl to support the caching of select queries
		 *
		 * @final
		 * @access public
		 * @param mixed $table
		 * @param mixed	$what
		 * @param mixed	$where
		 * @param string $additional_string
		 * @param int $ttl
		 *
		 * @return resultset
		 *
		 */
		final public function select($table, $what, $where = null, $additional_string = null, $ttl = 0) {
			// construct SELECT statement
			$fields = is_array($what) ? implode(',', $what) : $what;
			$tables = is_array($table) ? implode(',',  $table) : $table;

			// construct WHERE statement
			// if null
			if (is_null($where)) {
				$where = '';
			}
			// if numeric it's the id value
			else if(is_numeric($where)) {
				$where = "WHERE id='" . $where . "' ";
			}
			// if array
			elseif(is_array($where)) {
				foreach( $where as $field => $value) {
					$vals[] = $field . "='" . $this->escape($value) . "'";
				}
				$vals = implode(' AND ', $vals);
				$where = "WHERE " . $vals . " ";
			}
			elseif (!is_null($where)) {
				$where = "WHERE " . $where . " ";
			}

			// construct the query
			$query  = "SELECT " . $fields . " FROM " . $tables . " " . $where . (string)$additional_string;
			return $this->query($query, $ttl);
		}

		/**
		 * Generate and execute insert query
		 *
		 * @final
		 * @access public
		 * @param string $table
		 * @param array	$data (field => value) pairs
		 * @return integer last insert id
		 *
		 * @uses query()
		 * @uses last_inserted_id()
		 */
	    final public function insert($table, array $data) {
	    	$last_id = 0;
	    	if (!empty($data)) {
				// prepare fields for insertion
	    		$fields = $this->table_info($table);
	    		foreach($data AS $field => $value) {
	    			if(is_null($value) || strcasecmp($value, 'NULL') == 0) {
						$data[$field] = 'NULL';
						continue;
					}

	    			if(!is_null($fields[$field]['func'])) {
	    				$data[$field] = $fields[$field]['func']($value);
	    			}

	    			$method = Inflector::modulize("update_" . $fields[$field]['type']);
	    			if(!method_exists($this, $method)) {
	    				$data[$field] = "'" . $this->escape($value) . "'";
					} else if(!mb_strlen($value)) {
						$data[$field] = 'NULL';
					}
	    		}

	    		//CLOB Fields
	    		$clobs = array();
	    		foreach($fields as $k=>$v) {
	    			if($v['type']=='clob') {
	    				$clobs[$k] = $data[$k];
						$data[$k] = ':'.$k;
	    			}
	    			if($v['type']=='date') {
	    				if($this instanceof OracleDriver) {
	    					$data[$k] = "TO_DATE(".$data[$k].")";
	    				}
	    			}
	    		}


	    		// build the query
	    		$query = "INSERT INTO " . $table . " (" . implode(', ', array_keys($data)) . ") VALUES (" . implode(', ', $data ) . ")";
				// store the query
	    		self::$stored_queries[] = $query;
	    		// execute and return the the last inserted id
	    		// if we have a custom not auto incremented primary key
	    		if(!$last_id = $this->queryexec($query, $clobs)) {
	    			$pk = current(array_filter($fields, function ($f) use($data) {return $f['primary_key'] == true && !empty($data[$f['name']]);}));
	    			$last_id = preg_replace('#^("|\')("|\')?([^"|\']+)("|\')?("|\')$#', '$3', $data[$pk['name']]);
	    		}
	    	}
	    	return $last_id;
	    }

	    /**
		 * Generate and execute update query. Return number of affected rows
		 *
		 * @final
		 * @access	public
		 * @param string $table
		 * @param array $data (field => value) pairs
		 * @param mixed $where
		 * @return integer
		 *
		 */
		final public function update($table, $data, $where=null) {
			if (!empty($data)) {
				$clobs = array();
				$vals = array();
				$fields = $this->table_info($table);

				// CLOB FIELDS
				foreach($fields as $k=>$v) {
	    			if($v['type']=='clob' && $data[$k]) {
	    				$clobs[$k] = $data[$k];
						$data[$k] = ':'.$k;
	    			}
	    		}
				foreach ($data as $field => $value ) {
	    			if(is_null($value) || strcasecmp($value, 'NULL') == 0) {
						$vals[] = $field . '=NULL';
						continue;
					}
					else if (!is_null($fields[$field]['func'])) {
						$value = $fields[$field]['func']($value);
					}


					$method = Inflector::modulize("update_{$fields[$field]['type']}");
					if(method_exists($this, $method) || array_key_exists($field, $clobs)) {
						$vals[] = $field . '=' . $value;
					} else {
						$vals[] = $field . "='" . $this->escape($value) . "'";
					}


				}


	    		// Ako e Oracle - Vsichki CLOB poleta trqbva da se napravqt taka

				$update_pairs = implode(', ', $vals);
				// construct WHERE statement
				// if null
				if (is_null($where)) {
					$where = '';
				}
				// if numeric it's the id value
				else if(is_numeric($where)) {
					$where = "WHERE id='" . $where . "' ";
				}
				// if array
				else if(is_array($where)) {
					$vals = array();
					foreach($where as $field => $value) {
						$vals[] = $field . "='" . $this->escape($value) . "'";
					}
					$vals = implode(' AND ', $vals);
					$where = "WHERE " . $vals . " ";
				}
				// if ordinary string
				else if($where) {
					$where = "WHERE $where ";
				}
				//build the query
				$query = "UPDATE " . $table . " SET " . $update_pairs . " " . $where;
				// store the query
				self::$stored_queries[] = $query;
				// execute and return the number of affected rows
				return $this->queryexec($query,$clobs);
			}
			return false;
		}

		/**
		 * Common deletion method
		 *
		 * @param string $table
		 * @param mixed $where
		 * @return int
		 * @final
		 */
		final public function delete($table, $where = null) {
			// construct WHERE statement
			// if null
			if (is_null($where)) {
				$where = '';
			}
			// if is number
			else if (is_numeric($where)) {
				$where = "WHERE id='$where' ";
			}
			// if array is given
			else if(is_array($where)) {
				foreach ( $where as $field => $value ) {
					$vals[] = $field . "='" . $this->escape($value) . "'";
				}
				$vals = implode(' AND ', $vals);
				$where = "WHERE $vals ";
			}
			// or else if is a string
			else if ($where) {
				$where = "WHERE $where ";
			}
			// build the query
			$query  = "DELETE FROM " . $table . " " . $where;
			// store the query
			self::$stored_queries[] = $query;
			// execute and return the number of affected rows
			return $this->queryexec($query);
		}

		/**
		 * Database specific escape string function
		 *
		 * @param string $string
		 * @return string
		 */
		abstract public function escape($string);

		/**
		*  Return DB specific system date syntax i.e. Oracle: SYSDATE Mysql: NOW()
		*
		* @return string
		*/
		abstract public function sysdate();

		/**
		 * Apply where clause to select all data
		 *
		 * @return string
		 */
		abstract public function whereall();

		/**
		 * Applies limit to the query
		 *
		 * @param string $query
		 * @param int $offset
		 * @param int $limit
		 * @return string
		 */
		abstract public function apply_limit($query, $offset = 0, $limit = 0);

	    /**
	     * Returns last-generated auto-increment id
	     *
	     * Note that for very large values (2,147,483,648 to 9,223,372,036,854,775,807) a string
	     * will be returned, because these numbers are larger than supported by PHP's native
	     * numeric datatypes.
	     *
	     * @param resource $resource
	     * @param string $query
	     */
	    abstract protected function last_inserted_id($resource, $query);

	    /**
	     * Start a database transaction.
	     *
	     * @return exception or true
	     */
	    abstract public function begin();

	    /**
	     * Commit transaction
	     *
	     * @return exception or true
	     */
	    abstract public function commit();

	    /**
	     * Rollback transaction
	     *
	     * @return exception or true
	     */
	    abstract public function rollback();

        /**
         * Retrieves the last error string, returned by the database
         *
         * @return string
         */
        abstract public function get_error();

        /**
         * Retrieves the last connection error string
         *
         * @return string
         */
        abstract public function get_connect_error();

		/**
		 * Returns whether the table exists in the currently selected database
		 *
		 * @param string
		 * @return boolean
		 */
		abstract public function table_exists($table);

		/**
		 * Retrieves information about the fields in a table/result set
		 *
		 * @param string $table_name
		 * @return array
		 */
		abstract public function table_info($table);

		/**
		 * Prints the executed queries
		 *
		 * @param void
		 * @return array
		 */
		final public function get_stored_queries() {
            //echo join(';',self::$stored_queries);
			d(self::$stored_queries);
		}

	}

	class SqlCommand {
	}

	class SqlQuery {
	}

	abstract class SqlResult implements Countable, Iterator, ArrayAccess {

		/**
		 * Database Connection Object
		 *
		 * @var Sqldriver
		 */
		protected $resource;

		/**
		 * Result
		 *
		 * @var resource
		 */
		protected $result;

		/**
		 * Internal data storage
		 *
		 * @var array
		 */
		protected $data;

		/**
		 * Internal index
		 *
		 * @var int
		 */
		protected $index = 0;

		/**
		 * Constructor
		 *
		 * @param Sql $driver
		 * @param result $result
		 * @return boolean
		 */
		abstract function __construct($resource, $result = null);

	    /**
	     * Destructor
	     *
	     * Free db result resource.
	     */
	    abstract public function __destruct();

		/**
		 * Overload default behaviour
		 *
		 * @param string $property
		 * @return mixed
		 */
		abstract function __get($property);

		/**
		 * Return data array
		 *
		 * @return array
		 */
		public function fetch() {
			return $this->data;
		}

	}

	class SqlCacheResult implements Countable, Iterator, ArrayAccess {

		/**
		 * Internal data storage
		 *
		 * @var array
		 */
		private $data;

		/**
		 * Internal index
		 *
		 * @var int
		 */
		private $index = 0;

		/**
		 * Count of items
		 *
		 * @var int
		 */
		private $count = 0;

		/**
		 * Constructor
		 *
		 * @param array $data
		 * @return SqlCacheResult object
		 */
		function __construct(array $data) {
			if (empty($data)) return false;
			$this->data = $data['results'];
			$this->count = $data['count'];
			$this->index = 0;
		}

		/**
		 * Overload default behaviour
		 *
		 * @param string $property
		 * @return mixed
		 */
		function __get($property){
			if(isset($this->data[$this->index][$property])){
				return $this->data[$this->index][$property];
			}else{
				return null;
			}
		}

		/**
		 * Return data array
		 *
		 * @return array
		 */
		public function fetch() {
			return $this->data[$this->index];
		}

		/**
		 * Implementation of Iterator method rewind.
		 * Sets the internal cursor to the first row
		 *
		 */
		public function rewind() {
			reset($this->data);
			$this->index = 0;
		}

		/**
		 * Implementation of Iterator method next.
		 * Moves the internal cursor to the next result
		 *
		 * @return array
		 */
		public function next() {
			$this->index++;
			return $this;
		}

		/**
		 * Implementation of Iterator method next.
		 * Moves the internal cursor to the next result
		 *
		 * @return array
		 */
		public function current() {
			return $this;
		}

		/**
		 * Implementation of Iterator method key.
		 * Returns the current index of the fetched row
		 *
		 * @return int
		 */
		public function key() {
			return $this->index;
		}

		/**
		 * Implementation of Iterator method valid.
		 * Checks if the result is valid
		 *
		 * @return boolean
		 */
		public function valid() {
			return (isset($this->data[$this->index]));
		}

		/**
		 * Implementation of Countable method count.
		 * Returns number of affected rows
		 *
		 * @return int
		 */
		public function count() {
			return $this->count;
		}

		/**
		 * Implementation of ArrayAccess method offsetSet.
		 * Method disabled by returning always false.
		 *
		 * @return false
		 */
		public function offsetSet($offset, $value) {
	        return false;
	    }

	    /**
		 * Implementation of ArrayAccess method offsetExists.
		 *
		 * @return boolean
		 */
	    public function offsetExists($offset) {
	    	return isset($this->data[$this->index][$offset]);
	    }

	    /**
		 * Implementation of ArrayAccess method offsetUnset.
		 * Method disabled by returning always false.
		 *
		 * @return false
		 */
	    public function offsetUnset($offset) {
	        return false;
	    }

	    /**
		 * Implementation of ArrayAccess method offsetGet.
		 *
		 * @return mixed
		 */
	    public function offsetGet($offset) {
	    	return $this->data[$this->index][$offset];
	    }
	}

	class SqlFactory {

	    /**
	     * Map of already established connections
	     * @see factory()
	     * @var array Hash mapping connection DSN => Connection instance
	     */
		public static $db = array();

		/**
		 * Map of built-in drivers.
		 * @var array Hash mapping phptype => driver class
		 */
		private static $drivers = array(
			'mysql' => 'MySQL',
			'mysqli'=> 'MySQLi',
			'pgsql' => 'PgSQL',
			'sqlite'=> 'SQLite',
			'oracle'=> 'Oracle'
		);

	    /**
	     * Create a new DB connection object and connect to the specified
	     * database
	     *
	     * @param mixed $dsn "data source name"
	     * @return object Newly created DB connection object
	     * @throws SQLException
		 */
		public static function factory($dsn = null) {
			$dsn = (!is_array($dsn)) ? self::parse_dsn($dsn) : $dsn;
	      	// sort $dsn by keys so the serialized result is always the same
	        // for identical connection parameters, no matter what their order is
	        ksort($dsn);
	        $id = md5(serialize($dsn));
	        // see if we already have a connection with these parameters cached
	        if(!isset(self::$db[$id])) {
	        	$driver_name = self::$drivers[$dsn['driver']];
				$class = $driver_name . 'Driver';
	        	if (!class_exists($class, false)) {
	        		if (is_file(Config()->CORE_PATH . 'db/'.$driver_name.'.php')) {
		        		require_once(Config()->CORE_PATH . 'db/'.$driver_name.'.php');
	        		} else {
	        			throw new SwebooException('Unrecognized DB Driver: <strong>' . $driver_name . '</strong>');
	        		}
	        	}
				$connection = new $class($dsn);
				$connection->connect();
	        	self::$db[$id] = $connection;
	        }
			return self::$db[$id];
		}

		/**
	     * Parse a data source name.
	     *
	     * A array with the following keys will be returned:
	     *  driver  : Database backend used in PHP (mysql, oracle etc.)
	     *  protocol: Communication protocol to use (tcp, unix etc.)
	     *  hostspec: Host specification (hostname[:port])
	     *  database: Database to use on the DBMS server
	     *  username: User name for login
	     *  password: Password for login
	     *
	     * The format of the supplied DSN is in its fullest form:
	     *
	     *  driver://username:password@protocol+hostspec/database
	     *
	     * Most variations are allowed:
	     *
	     *  driver://username:password@protocol+hostspec:110//usr/db_file.db
	     *  driver://username:password@hostspec/database
	     *  driver://username:password@unix(/path/to/socket)/database
	     *  driver://username:password@hostspec
	     *  driver://username@hostspec
	     *  driver://hostspec/database
	     *  driver://hostspec
	     *  driver
	     *
	     * @param string $dsn Data Source Name to be parsed
	     * @return array An associative array
	     */
	    private static function parse_dsn($dsn) {
	        if (is_array($dsn)) {
	            return $dsn;
	        }

	        $parsed = array(
	            'driver'  => null,
	            'username' => null,
	            'password' => null,
	            'protocol' => null,
	            'hostspec' => null,
	            'port'     => null,
	            'socket'   => null,
	            'database' => null
	        );

	        $info = parse_url($dsn);

	        // if there's only one element in result, then it must be the driver
	        if (count($info) === 1) {
	            $parsed['driver'] = array_pop($info);
	            return $parsed;
	        }

	        // some values can be copied directly
	        $parsed['driver'] = @$info['scheme'];
	        $parsed['username'] = @$info['user'];
	        $parsed['password'] = @$info['pass'];
	        $parsed['port'] = @$info['port'];

	        $host = @$info['host'];
	        if (false !== ($pluspos = strpos($host, '('))) {
	            $parsed['protocol'] = substr($host,0,$pluspos);
	            if ($parsed['protocol'] == 'unix') {
	                $parsed['socket'] = substr($info['path'], 0, strpos($info['path'], ')'));
	            } else {
	                $parsed['hostspec'] = substr($host,$pluspos+1);
	            }
	        } else {
	            $parsed['hostspec'] = $host;
	        }

	        if (isset($info['path'])) {
	        	$parsed['database'] = ($parsed['protocol'] == 'unix') ? substr($info['path'], strpos($info['path'], ')') + 2) : substr($info['path'], 1);
	        }

	        if (isset($info['query'])) {
	                $opts = explode('&', $info['query']);
	                foreach ($opts as $opt) {
	                    list($key, $value) = explode('=', $opt);
	                    if (!isset($parsed[$key])) {
	                    	// don't allow params overwrite
	                        $parsed[$key] = urldecode($value);
	                    }
	                }
	        }
	        return $parsed;
	    }
	}

    /**
     * Sql Error
     *
     */
    class SQLException extends SwebooException {

    	public $dbh;

        function __construct($dbh, $error='') {
        	$this->dbh = &$dbh;
        	parent::__construct($error);
        }
    }

    /**
     * Sql Connection Error
     *
     */
    class SqlConnectionError extends SQLException {

    	function __construct($dbh) {
    		$error = array();
    		$error[] = 'Error while trying to connect to the server.';
    		$error[] = 'The SQL server said: <strong>' . $dbh->get_connect_error() . '</strong>.';
    		parent::__construct($dbh, join("\n", $error));
    	}
    }

    /**
     * Sql Db Connection Error
     *
     */
    class SqlDbConnectionError extends SQLException {

    	function __construct($dbh) {
    		$error = array();
    		$error[] = "Error while trying to connect to the database.";
    		$error[] = "The SQL server said: <strong>".$dbh->get_error()."</strong>.";

    		parent::__construct($dbh, join("\n", $error));
    	}
    }

    /**
     * Sql Query Error
     *
     */
    class SqlQueryError extends SQLException {

    	function __construct($dbh, $query) {
    		$error = array();
    		$error[] = "Query failed!";
    		$error[] = "Tried to execute: \n<strong style=\"font-size:1.1em;\">" . $query."</strong>";
    		$error[] = "The SQL server said: <strong style=\"font-size:1.1em;\">" . $dbh->get_error()."</strong>.";
    		parent::__construct($dbh, join("\n", $error));
    	}
    }

?>