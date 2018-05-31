<?php

	final class OracleDriver extends Sql {
		
		/**
		 * Database specific timestamp format
		 *
		 * @var string
		 * @access protected
		 */
		protected $timestamp = 'YYYY-MM-DD HH24:MI:SS';
		
	    /**
	     * Auto commit mode for oci_execute
	     * 
	     * @var int
	     * @access protected
	     */
	    protected $exec_mode = OCI_COMMIT_ON_SUCCESS;		
		
		/**
		 * Opens or reuses a connection to a Oracle server
		 *
		 * @return boolean
		 */		
		public function connect() {
	 		if (!extension_loaded('oci8')) {
	            throw new SQLException('OCI8 extension not loaded');
	        }		
			$dsn = $this->dsn;

			$user = $dsn['username'];
			$pass = $dsn['password'];
			$hostspec = $dsn['hostspec'];
			$db	= $dsn['database'];
			
			if ($db && $hostspec && $user && $pass) {
				$resource = oci_connect($user, $pass, "//$hostspec/$db", $this->charset);
			}
			elseif ($hostspec && $user && $pass) {
				$resource = oci_connect($user, $pass, $hostspec, $this->charset);
			}
			elseif ($user || $pass) {
				$resource = oci_connect($user, $pass, null, $this->charset);
			}
			else {
				$resource = false;
			}
			
			if (!$resource) {
				throw new SqlConnectionError($this);
			}
			$this->resource = $resource;
			$this->query("ALTER SESSION SET NLS_DATE_FORMAT='" . $this->timestamp . "'");
			return true;	        	
		}		
		
		function user_queryexec($query,$clobs=array()){
			return $this->queryexec($query,$clobs);
		}
		
		/**
		 * Query processing method
		 *
		 * @param string $query
		 */
		protected function queryexec($query,$clobs=array()) {
			// execute the query
			$stmt = oci_parse($this->resource, $query);

			if($clobs) {
				foreach($clobs as $c_key => &$c_value) {
					$c_value = trim($c_value,"'");
					oci_bind_by_name($stmt, ":".$c_key,$c_value);
				}
			}	
			$result = oci_execute($stmt, $this->exec_mode);
			if (!$result) {
				throw new SqlQueryError($this, $query);
			}			
			// if it is an insert query return the last inserted id
			if (stripos($query, 'insert') === 0) {
				return (int)$this->last_inserted_id($this->resource, $query);
			} 
			// else return the number of affected rows
			else if (stripos($query, 'update') === 0 || stripos($query, 'delete') === 0) { 
				//return (int)oci_num_rows($this->resource);
				return true;
			} 
			// return the result recorset
			else {
				return new OracleResult($this->resource, $stmt);
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
			return  str_replace("'", "''", $string);
		}
		
		/**
		*  Return Oracle specific system date syntax i.e. Oracle: SYSDATE Mysql: NOW()
		* 
		* @return string
		*/
		public function sysdate() {
			return 'TRUNC(SYSDATE)';
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

			
			$high_where = '';
			$lower_where = 0;
            if($offset>0 || $limit>0) {
                $high_where = '"_RN" <= '.($offset+$limit).' AND ';
                $lower_where = $offset+1;
            }
            $sql = sprintf('SELECT * FROM (SELECT ROWNUM AS "_RN", "_SUB".* FROM (%s) "_SUB") WHERE %s "_RN" >= %d',$query, $high_where, $lower_where);
			
			return $sql;
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
			$regexp = "/into ((?:[\w_-])+|(?:(\"|')+[^\"]*(\"|')+))(?: |\()/i";
			$matches = array();
			preg_match($regexp, strtolower($query), $matches);

			// remove quotes and whitespace from end of string
			$tablename = trim($matches[1], "\"' ");

			$last_id = null;
			$seq = "{$tablename}_seq";
			$q = "SELECT last_number FROM user_sequences WHERE LOWER(sequence_name)='{$seq}'";
			$stmt = oci_parse($resource, $q);
			oci_execute($stmt);
			$num_rows = oci_fetch_all($stmt, $dummy);
			oci_free_statement($stmt);
			if($num_rows) {
				$q = "SELECT {$seq}.currval FROM DUAL";
				$stmt = oci_parse($resource, $q);
				oci_execute($stmt);
				$last_id = current(oci_fetch_row($stmt));
				oci_free_statement($stmt);
			}
			return $last_id;
	    }		

	    /**
	     * Start a database transaction.
	     *
	     * @return exception or true
	     */
	    public function begin() {
	    	$this->exec_mode = OCI_DEFAULT;
	    	return true;
	    }	
	    
	    /**
	     * Commit transaction
	     *
	     * @return exception or true
	     */	    
	    public function commit() {
	    	if (!oci_commit($this->resource)) {
	    		throw new SQLException('Can not commit transaction', $this->get_error());
	    	}
	    	$this->exec_mode = OCI_COMMIT_ON_SUCCESS;
	    	return true;
	    }	    
	    
	    /**
	     * Rollback transaction
	     *
	     * @return exception or true
	     */		    
	    public function rollback() {
	    	if (!oci_rollback($this->resource)) {
	    		throw new SQLException('Could not rollback transaction', $this->get_error());
	    	}
	    	$this->exec_mode = OCI_COMMIT_ON_SUCCESS;
	    	return true;
	    }	    
	    
		/**
		* Close mysql connection
		*
		* @return boolean
		*/
		public function close() {
			oci_close($this->resource);
			return $this->resource = null;
		}	    
		
        /**
         * Retrieves the last error string, returned by the database
         *
         * @return string
         */
        public function get_error() {
        	$error = oci_error($this->resource);
        	return $error[ 'code' ] . ': ' . $error[ 'message' ];
        }

        /**
         * Retrieves the last connection error string
         *
         * @return string
         */
        public function get_connect_error() {
        	$error = oci_error();
        	return $error[ 'code' ] . ': ' . $error[ 'message' ];
        }	
        
		/**
		 * Returns whether the table exists in the currently selected database
		 *
		 * @param string
		 * @return boolean
		 */        
		public function table_exists($table) {
			// check in the cache first
			return true;  // Assume that table exists
			if(isset(self::$cache['table_exists'][$table])) return true;
			$query = "SELECT object_name FROM user_objects WHERE OBJECT_TYPE IN ('TABLE', 'VIEW') AND LOWER(object_name)='".strtolower($table)."'";
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
			
			if($table) {
			    /**
				 * Get Primary keys
				 */
				$query = "SELECT * FROM ALL_CONSTRAINTS WHERE LOWER(TABLE_NAME)='".strtolower($table)."' AND CONSTRAINT_TYPE='P'";
				$result = $this->query($query);
				$primaries = array();
				foreach ($result as $row) {
					$primaries[] = $row->constraint_name;
				}

				/**
				 * Get Unique Columns
				 */
				$query = "SELECT
							ALL_INDEXES.INDEX_NAME,
							ALL_INDEXES.UNIQUENESS,
							ALL_IND_COLUMNS.COLUMN_POSITION,
							ALL_IND_COLUMNS.COLUMN_NAME
					  FROM
					  		ALL_INDEXES,
							ALL_IND_COLUMNS
					  WHERE
					  		LOWER(ALL_INDEXES.TABLE_NAME)='".strtolower($table)."'
					  		AND ALL_IND_COLUMNS.INDEX_NAME=ALL_INDEXES.INDEX_NAME";
				$result = $this->query($query);

				$primary_keys = $uniqueness = array();
				foreach ($result as $row) {
					$cname = strtolower($row->column_name);
					if($row->uniqueness == 'UNIQUE') {
						$uniqueness[] = $cname;
					}
					if(in_array($row->index_name, $primaries)) {
						$primary_keys[] = $cname;
					}
				}

				$result = $this->query( "SELECT cname, coltype, width, SCALE, PRECISION, NULLS, DEFAULTVAL FROM col WHERE LOWER(tname)='".strtolower($table)."' ORDER BY colno");

				$functions = array(
					'tinyint' => 'intval',
					'smallint' => 'intval',
					'int'=> 'intval',
					'float' => 'doubleval',
					'double' => 'doubleval',
					'bigint' => 'intval',
					'mediumint' => 'intval',
					'year' => 'intval',
					'real' => 'doubleval'
				);

				$results = array();


				foreach ($result as $row) {
					$ind = strtolower($row->cname);
					$results[$ind]['name'] = strtolower($row->cname);
					$results[$ind]['type'] = $row->coltype == 'NCLOB' ? 'clob' : strtolower($row->coltype);
					$results[$ind]['not_null'] = $row->nulls == 'NOT NULL';
					$results[$ind]['unique'] = in_array($results[$ind]['name'], $uniqueness);
					$results[$ind]['primary_key'] = in_array($results[$ind]['name'], $primary_keys);
					$results[$ind]['binary'] = strpos($row->coltype,'BLOB') !== false;

					$results[$ind]['scale'] = null;
					$results[$ind]['real_type'] = strtolower($row->coltype);
					$results[$ind]['max_length'] = in_array($results[$ind]['type'], $this->blob_column_types) ? 0 : $row->width;
					if($results[$ind]['type'] == 'nvarchar2') {
						$results[$ind]['max_length'] /= 2;
					}

					if(is_numeric($row->scale)) {
						$results[$ind]['scale'] = $row->scale;
					}

					if ($row->coltype == 'NUMBER') {
						if($row->scale == 0) {
							$results[$ind]['real_type'] = $row->precision == 1 ? 'tinyint' : 'int';
						}
						else if($row->scale > 0) {
							$results[$ind]['real_type'] = 'float';
		    			}
		    			$results[$ind]['max_length'] = $row->precision;
		    		}

		    		if ($row->coltype == 'CLOB' || $row->coltype == 'NCLOB') {
						$results[$ind]['real_type'] = 'bigtext';
						$results[$ind]['max_length'] = 2*1024*1024*1024; //2TB
		    		}
		    		if ($row->coltype == 'DATE') {
						$results[$ind]['max_length'] = 19;
		    		}

		    		if (!$results[$ind]['binary']) {
						if ($row->defaultval != '' && $row->defaultval != 'NULL') {
							$results[$ind]['has_default'] = true;
							$results[$ind]['default_value'] = $row->defaultval;
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

	class OracleResult extends SqlResult {	
		
		/**
		 * Rows count
		 * 
		 * @var int
		 */
		private $count = 0;

		protected $index = 0;
		
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
			// fetch all results and store them in array
			// $count is the number of rows fetched or FALSE in case of an error 
			$this->count = oci_fetch_all($this->result, $this->data, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
			if ($this->count) {
				foreach ($this->data as &$data) {
					$data = array_change_key_case($data, CASE_LOWER);
				}
				#$this->data = array_change_key_case($this->data, CASE_LOWER);
			}
		}

	    /**
	     * Destructor
	     *
	     * Free db result resource.
	     */
	    public function __destruct() {
	          oci_free_statement($this->result);
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
			return (int)$this->count;
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
	        return isset($this->data[$this->index][$offset]);
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
	        return isset($this->data[$this->index][$offset]) ? $this->data[$this->index][$offset] : null;
	    }		
	    
		/**
		 * Return data array
		 * 
		 * @return array
		 */
		public function extract() {
			return $this->data[$this->index];
		}

		public function fetch() {
			return $this->data[$this->index];
		}
	
	}
?>