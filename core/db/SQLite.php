<?php

	final class SQLiteDriver extends Sql {
		
		/**
		 * Opens or reuses a connection to a SQLite server
		 *
		 * @return boolean
		 */		
		public function connect() {
	 		if (!extension_loaded('sqlite')) {
	            throw new SQLException('SQLite extension not loaded');
	        }		
			$dsn = $this->dsn;
	        $file = $dsn['database'];
			if ($file === null) {
				throw new SQLException("No SQLite database specified.");
			}
			
			$mode = (isset($dsn['mode']) && is_numeric($dsn['mode'])) ? $dsn['mode'] : 0644;
			
			if ($file != ':memory:') {
			    if (!file_exists($file)) {
			        touch($file);
			        chmod($file, $mode);
			        if (!file_exists($file)) {
			            throw new SQLException("Unable to create SQLite database.");
			        }
			    }
			    if (!is_file($file)) {
			        throw new SQLException("Unable to open SQLite database: not a valid file.");
			    }
			    if (!is_readable($file)) {
			        throw new SQLException("Unable to read SQLite database.");
			    }
			}	
			
			$resource = sqlite_open($file, $mode, $errmsg);
			if (!$resource) {
				throw new SqlConnectionError($this);
			}
			$this->resource = $resource;
			return true;	        	
		}		
		
		/**
		 * Query processing method
		 *
		 * @param string $query
		 */
		protected function queryexec($query) {
			// execute the query
			$result = sqlite_query($this->resource, $query);
			if (!$result) {
				throw new SqlQueryError($this, $query);
			}
			// if it is an insert query return the last inserted id
			if (stripos($query, 'insert') === 0) {
				return (int)$this->last_inserted_id($this->resource, $query);
			} 
			// else return the number of affected rows
			else if (stripos($query, 'update') === 0 || stripos($query, 'delete') === 0) { 
				//return (int)sqlite_changes($this->resource);
				return true;
			}
			// return the result recorset
			else if (is_resource($result)) {
				return new SQLite3Result($this->resource, $result);
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
			return sqlite_escape_string($string);
		}		
		
		/**
		*  Return SQLite specific system date syntax i.e. Oracle: SYSDATE Mysql: NOW()
		* 
		* @return string
		*/
		public function sysdate() {
			return date('Y-m-d', time());
		}			
		
		/**
		 * Apply where clause to select all data
		 *
		 * @return string
		 */
		public function whereall() {
			return 'id=id';
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
				$query .= " LIMIT " . $limit . ($offset > 0 ? " OFFSET " . $offset : "");
			} elseif ($offset > 0) {
				$query .= " LIMIT -1 OFFSET " . $offset;
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
			return (int)sqlite_last_insert_rowid($resource);
	    }		
	    
	    /**
	     * Start a database transaction.
	     *
	     * @return exception or true
	     */
	    public function begin() {
	    	if (!sqlite_query($this->resource, 'BEGIN TRANSACTION')) {
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
	    	if (!sqlite_query($this->resource, 'COMMIT')) {
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
	    	if (!sqlite_query($this->resource, 'ROLLBACK')) {
	    		throw new SQLException('Could not rollback transaction', $this->get_error());
	    	}
	    	$this->connection->autocommit(true);
	    	return true;
	    }
	    
		/**
		* Close mysql connection
		*
		* @return boolean
		*/
		public function close() {
			sqlite_close($this->resource);
			return $this->resource = null;
		}	
		
        /**
         * Retrieves the last error string, returned by the database
         *
         * @return string
         */
        public function get_error() {
        	return sqlite_error_string(sqlite_last_error($this->resource));
        }

        /**
         * Retrieves the last connection error string
         *
         * @return string
         */
        public function get_connect_error() {
        	return sqlite_error_string(sqlite_last_error($this->resource));
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
			$query = "SELECT name FROM sqlite_master WHERE type='table' AND name='".$table."' UNION ALL SELECT name FROM sqlite_temp_master WHERE type='table' AND name='".$table."' ORDER BY name;";
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
				$info = $this->query("SELECT * FROM sqlite_master WHERE type='table' AND name='" . $table . "'");
				$columns = explode(',', trim(str_replace('create table images', '', strtolower($info['sql']))));
				
				$fields = $this->query("PRAGMA table_info ('" . $table . "')");
				
				$results = array();
				foreach ($fields as $key => $row) {
					$results[$key]['name'] = $row['name'];
					$results[$key]['type'] = $row['type'];
					$results[$key]['not_null'] = (int)((boolean)$row['notnull']);
					$results[$key]['binary'] = (strpos($row['type'], 'blob') !== false);
					$results[$key]['unsigned'] = (strpos($columns[$key], 'unsigned') !== false);
					$results[$key]['primary_key'] = ($row['pk'] == 1 || (strpos(strtolower($row['type']), 'primary key') !== false));
					$results[$key]['unique'] = (strpos($columns[$key], 'unique') !== false);
					$results[$key]['auto_increment'] = (strpos($columns[$key], 'autoincrement') === false) ? false : true;
					
					if (preg_match('/^([^\(]+)\(\s*(\d+)\s*,\s*(\d+)\s*\)$/', $row['type'], $matches)) {
		                $results[$key]['real_type'] = $matches[1];
		                $results[$key]['max_length'] = $matches[2];
		                $results[$key]['scale'] = $matches[3]; // aka precision    
		            } elseif (preg_match('/^([^\(]+)\(\s*(\d+)\s*\)$/', $row['type'], $matches)) {
		                $results[$key]['real_type'] = $matches[1];
		                 $results[$key]['max_length'] = $matches[2];
		            } else {
		                $results[$key]['real_type'] = $row['type'];
		            }	
		            
		            if ($row['dflt_value'] != '') {
						$results[$key]['has_default'] = true;
						$results[$key]['default_value'] = $row['dflt_value'];
					} else {
						$results[$key]['has_default'] = false;
					}
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
	
	class SQLite3Result extends SqlResult {	
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
			$this->data = sqlite_fetch_array($this->result, SQLITE_ASSOC);
		}

	    /**
	     * Destructor
	     *
	     * Free db result resource.
	     */
	    public function __destruct() {
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
			sqlite_seek($this->result, 0);
			$this->data = sqlite_fetch_array($this->result, SQLITE_ASSOC);
			$this->index = 0;
		}
		
		/**
		 * Implementation of Iterator method next. 
		 * Moves the internal cursor to the next result
		 *
		 * @return array
		 */
		public function next() {
			$this->data = sqlite_fetch_array($this->result, SQLITE_ASSOC);
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
			return (int)sqlite_num_rows($this->result);
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