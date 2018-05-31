<?php

	final class MySQLDriver extends Sql {

		/**
		 * Opens or reuses a connection to a MySQL server
		 *
		 * @return boolean
		 */		
		public function connect() {
	 		if (!extension_loaded('mysql')) {
	            throw new SQLException('MySQL extension not loaded');
	        }		
			$dsn = $this->dsn;
	        if (isset($dsn['protocol']) && $dsn['protocol'] == 'unix') {
	            $dbhost = ':' . $dsn['socket'];
	        } else {
	            $dbhost = $dsn['hostspec'] ? $dsn['hostspec'] : 'localhost';
	            if (!empty($dsn['port'])) {
	                $dbhost .= ':' . $dsn['port'];
	            }
	        }
			$resource = mysql_connect($dbhost, $dsn['username'], $dsn['password']);
			if (!$resource) {
				throw new SqlConnectionError($this);
			}
			$this->resource = $resource;
			if (!mysql_select_db($dsn['database'], $this->resource) ) {
				throw new SqlDbConnectionError($this);
			}			
			$this->query("SET NAMES " . $this->charset);
			return true;	        	
		}
		
		/**
		 * Query processing method
		 *
		 * @param string $query
		 */
		protected function queryexec($query) {
			// execute the query
			$result = mysql_query($query, $this->resource);
			if (!$result) {
				throw new SqlQueryError($this, $query);
			}			
			// if it is an insert query return the last inserted id
			if (stripos($query, 'insert') === 0) {
				return (int)$this->last_inserted_id($this->resource, $query);
			} 
			// else return the number of affected rows
			else if (stripos($query, 'update') === 0 || stripos($query, 'delete') === 0) { 
				//return (int)mysql_affected_rows($this->resource);
				return true;
			} 
			// else return the result recorset
			else if (is_resource($result)) {
				return new MySQLResult($this->resource, $result);
			}
			return true;			
		}
		
		/**
		 * Database specific escape string function
		 *
		 * @param string $string
		 * @return string
		 */
		public function escape($string) {
			return mysql_real_escape_string($string, $this->resource);
		}
		
		/**
		*  Return mySQL specific system date syntax i.e. Oracle: SYSDATE Mysql: NOW()
		* 
		* @return string
		*/
		public function sysdate() {
			return 'NOW()';
		}		
		
		/**
		 * Apply where clause to select all data
		 *
		 * @return string
		 */
		public function whereall() {
			return '1';
		}
		
		/**
		 * Applies limit to the query
		 * 
		 * @param string $query 
		 * @param int $offset
		 * @param int $limit
		 * @return string
		 */
		public function apply_limit($query, $offset = 0, $limit = 0) {
		    if ($limit > 0) {
		        $query .= ' LIMIT ' . ($offset > 0 ? $offset . ', ' : '') . $limit;
		    } else if ($offset > 0) {
		        $query .= ' LIMIT ' . $offset . ', 18446744073709551615';
		    }
		    return $query;
		}		
		
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
	    public function last_inserted_id($resource, $query) {
			$insert_id = mysql_insert_id($resource);
			if ($insert_id < 0) {
				$insert_id = null;
				$result = $result = mysql_query('SELECT LAST_INSERT_ID()', $resource);
				if ($result) {
					$row = mysql_fetch_row($result);
					$insert_id = $row ? $row[0] : null;
				}
			}
			return $insert_id;
	    }		
	    
	    /**
	     * Start a database transaction.
	     *
	     * @return exception or true
	     */
	    public function begin() {
	    	if (!mysql_query("BEGIN", $this->resource)) {
	    		throw new SQLException('Could not begin transaction', $this->get_error());
	    	}
	    	return true;
	    }	

	    /**
	     * Commit transaction
	     *
	     * @return exception or true
	     */	    
	    public function commit() {
	    	if (!mysql_query("COMMIT", $this->resource)) {
	    		throw new SQLException('Can not commit transaction', $this->get_error());
	    	}
	    	return true;
	    }

	    /**
	     * Rollback transaction
	     *
	     * @return exception or true
	     */		    
	    public function rollback() {
	    	if (!mysql_query("ROLLBACK", $this->resource)) {
	    		throw new SQLException('Could not rollback transaction', $this->get_error());
	    	}
	    	return true;
	    }
		
		/**
		* Close mysql connection
		*
		* @return boolean
		*/
		public function close() {
			mysql_close($this->resource);
			return $this->resource = null;
		}	
		
        /**
         * Retrieves the last error string, returned by the database
         *
         * @return string
         */
        public function get_error() {
        	return mysql_error($this->resource);
        }

        /**
         * Retrieves the last connection error string
         *
         * @return string
         */
        public function get_connect_error() {
        	return mysql_error();
        }			
        
		/**
		 * Returns whether the table exists in the currently selected database
		 *
		 * @param string
		 * @return boolean
		 */        
		public function table_exists($table) {
			// check in the cache first
			if(isset(self::$cache['table_exists'][$table])) return true;
			$query = "SHOW TABLES LIKE '".$table."'";
			$result = $this->query($query);
			self::$cache['table_exists'][$table] = count($result) ? true : false;
			return self::$cache['table_exists'][$table];
		}        
		
		/**
		 * Retrieves information about the fields in a table/result set
		 *
		 * @param string $table_name
		 * @return array
		 */
		public function table_info($table) {
			// check the cache first
			if(isset(self::$cache['table_info'][$table])) return self::$cache['table_info'][$table];

			// second check the files cache
			if(!Config()->DEVELOPMENT && file_exists(Config()->ROOT_PATH . 'cache/table_info/'.$table.'.cache')) {
			    $table_info = file_get_contents(Config()->ROOT_PATH . 'cache/table_info/'.$table.'.cache');
			    $table_info = self::$cache['table_info'][$table] = unserialize($table_info);
			    return $table_info;
			}

			// if not found check in the database
			if($table) {
				$result = $this->query("SHOW COLUMNS FROM " . $table);

				$functions = array(
					'tinyint'=> 'intval',
					'smallint' => 'intval',
					'int'=> 'intval',
					'float' => 'doubleval',
					'double' => 'doubleval',
					'bigint' => 'intval',
					'mediumint' => 'intval',
					'datetime' => null,
					'year' => 'intval',
					'real' => 'doubleval',
					'text' => null,
					'mediumtext' => null,
					'longtext' => null,
					'varchar' => null,
					'char' => null,
					'blob' => null,
					'mediumblob' => null,
					'longblob' => null
				);

				$results = array();

				foreach ($result as $row) {
					$ind = $row->Field;
					$results[$ind]['name'] = $row->Field;
					$results[$ind]['type'] = $row->Type;
					$results[$ind]['not_null'] = ($row->Null != 'YES');
					$results[$ind]['primary_key'] = strpos($row->Key, "PRI") === 0;
					$results[$ind]['unique'] = strpos($row->Key, "UNI") === 0;
					$results[$ind]['auto_increment'] = (strpos($row->Extra, 'auto_increment') !== false);
					$results[$ind]['binary'] = (strpos($row->Type,'blob') !== false);
					$results[$ind]['unsigned'] = (strpos($row->Type,'unsigned') !== false);
					$results[$ind]['zerofill'] = (strpos($row->Type,'zerofill') !== false);

					$results[$ind]['scale'] = null;
					if (preg_match("/^(.+)\((\d+),(\d+)/", $row->Type, $query_array)) {
						$results[$ind]['real_type'] = $query_array[1];
						$results[$ind]['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
						$results[$ind]['scale'] = is_numeric($query_array[3]) ? $query_array[3] : -1;
					} elseif (preg_match("/^(.+)\((\d+)/", $row->Type, $query_array)) {
						$results[$ind]['real_type'] = $query_array[1];
						$results[$ind]['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
					} elseif (preg_match("/^(enum)\((.*)\)$/i", $row->Type, $query_array)) {
						$results[$ind]['real_type'] = $query_array[1];
						$results[$ind]['max_length'] = max(array_map("strlen",explode(",",$query_array[2]))) - 2; // PHP >= 4.0.6
						$results[$ind]['max_length'] = ($results[$ind]['max_length'] == 0 ? 1 : $results[$ind]['max_length']);
					} else {
						$results[$ind]['real_type'] = $row->Type;
						$results[$ind]['max_length'] = -1;
					}

					if (!$results[$ind]['binary'] ) {
						if ($row->Default != '' && $row->Default != 'NULL') {
							$results[$ind]['has_default'] = true;
							$results[$ind]['default_value'] = $row->Default;
						} else {
							$results[$ind]['has_default'] = false;
						}
					}
					$results[$ind]['func'] = $functions[$results[$ind]['real_type']];
				}

				// store in cache
				self::$cache['table_info'][$table] = $results;

				// store in file cache
				if($results) {
					$fh = fopen(Config()->ROOT_PATH . 'cache/table_info/'.$table.'.cache', 'w+');
					fwrite($fh, serialize($results));
					fclose($fh);
				}
				return $results;
			}
			return false;
		}		
		
	}
	
	class MySQLResult extends SqlResult {
		
		/**
		 * Constructor
		 *
		 * @param resource $resource
		 * @param resource $result
		 * @return boolean
		 */
		function __construct($resource, $result = null) {
			if (!$resource || !$result) return false;
			$this->resource = $resource;
			$this->result = $result;
			$this->data = mysql_fetch_assoc($this->result);
		}

	    /**
	     * Destructor
	     *
	     * Free db result resource.
	     */
	    public function __destruct() {
	          mysql_free_result($this->result);
	    }		
		
		/**
		 * Overload default behaviour
		 *
		 * @param string $property
		 * @return mixed
		 */
		function __get($property){
			if(isset($this->data[$property])){
				return $this->data[$property];
			}else{
				return null;
			}
		}			
		
		/**
		 * Returns number of affected rows
		 *
		 * @return int
		 */
		public function num_rows() {
			return $this->count();
		}
		
		/**
		 * Implementation of Iterator method rewind. 
		 * Sets the internal cursor to the first row
		 *
		 */			
		public function rewind() {
			mysql_data_seek($this->result, 0);
			$this->data = mysql_fetch_assoc($this->result);
			$this->index = 0;
		}
		
		/**
		 * Implementation of Iterator method next. 
		 * Moves the internal cursor to the next result
		 *
		 * @return array
		 */
		public function next() {
			$this->data = mysql_fetch_assoc($this->result);
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
			return (!is_null($this->data) && $this->data !== false);
		}
		
		/**
		 * Implementation of Countable method count. 
		 * Returns number of affected rows
		 *
		 * @return int
		 */
		public function count() {
			return (int)mysql_num_rows($this->result);
		}
		
		/**
		 * Implementation of ArrayAccess method offsetSet. 
		 * Method disabled by returning always false.
		 * We want this to be read-only
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
	        return isset($this->data[$offset]);
	    }

	    /**
		 * Implementation of ArrayAccess method offsetUnset. 
		 * Method disabled by returning always false.
		 * We want this to be read-only
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
	        return isset($this->data[$offset]) ? $this->data[$offset] : null;
	    }		
	}
	
?>