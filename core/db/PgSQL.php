<?php

	final class PgSQLDriver extends Sql {

		/**
		 * Opens or reuses a connection to a PgSQL server
		 *
		 * @return boolean
		 */		
		public function connect() {
			if (!extension_loaded('pgsql')) {
	            throw new SQLException('PostgreSQL extension not loaded');
	        }		
			$dsn = $this->dsn;
	        
	        $protocol = (isset($dsn['protocol'])) ? $dsn['protocol'] : 'tcp';
	        $connstr = '';
	
	        if ($protocol == 'tcp') {
	            if (!empty($dsn['hostspec'])) {
	                $connstr = "host=" . $dsn['hostspec'];
	            }
	            if (!empty($dsn['port'])) {
	                $connstr .= " port=" . $dsn['port'];
	            }
	        }
	
	        if (isset($dsn['database'])) {
	            $connstr .= " dbname='" . addslashes($dsn['database']) . "'";
	        }
	        if (!empty($dsn['username'])) {
	            $connstr .= " user='" . addslashes($dsn['username']) . "'";
	        }
	        if (!empty($dsn['password'])) {
	            $connstr .= " password='" . addslashes($dsn['password']) . "'";
	        }
	        if (!empty($dsn['options'])) {
	            $connstr .= " options=" . $dsn['options'];
	        }
	        if (!empty($dsn['tty'])) {
	            $connstr .= " tty=" . $dsn['tty'];
	        }	        
			$connection = pg_connect($connstr);
			if (!$connection) {
				throw new SqlConnectionError($this);
			}
			$this->resource = $connection;
			return true;	        	
		}
		
		/**
		 * Query processing method
		 *
		 * @param string $query
		 */
		protected function queryexec($query) {
			// execute the query
			$result = pg_query($this->resource, $query);
			if (!$result) {
				throw new SqlQueryError($this, $query);
			}
			// if it is an insert query return the last inserted id
			if (stripos($query, 'insert') === 0) {
				return (int)$this->last_inserted_id($this->resource, $query);
			}
			// else return the number of affected rows
			else if (stripos($query, 'update') === 0 || stripos($query, 'delete') === 0) { 
				//return (int)pg_affected_rows($result);
				return true;
			} 
			// return the result recorset
			else if (is_resource($result)) {
				return new PostgreResult($this->resource, $result);
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
			return pg_escape_string($this->resource, $string);
		}
		
		/**
		*  Return PgSQL specific system date syntax i.e. Oracle: SYSDATE Mysql: NOW()
		* 
		* @return string
		*/
		public function sysdate() {
			return 'CURRENT_DATE';
		}		
		
		/**
		 * Apply where clause to select all data
		 *
		 * @return string
		 */
		public function whereall() {
			return '1=1';
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
			    $query .= " LIMIT ".$limit;
			}
			if ($offset > 0) {
			    $query .= " OFFSET ".$offset;
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
			$regexp = "/into ((?:[\w_-])+|(?:(\"|')+[^\"]*(\"|')+))(?: |\()/i";
			$matches = array();
			preg_match($regexp, strtolower($query), $matches);

			// remove quotes and whitespace from end of string
			$tablename = trim($matches[1], "\"' ");
			if(!empty($tablename)) {
				// get the last id from the sequence
				$tmp_query = "SELECT relname FROM pg_class WHERE relname LIKE '".$tablename."_%_seq'";
				$tmp_res = pg_query($this->resource, $tmp_query);

				// find out what the last id is by searching its sequence table
				if(pg_num_rows($tmp_res) == 1) {
					// find out what the last id is by searching its sequence table
					$tmp_row = pg_fetch_array($tmp_res);
					$seqname = $tmp_row[0];
					$tmp_query = "SELECT last_value FROM \"$seqname\" ";
					$tmp_res = pg_query($this->resource, $tmp_query);
					$tmp_row = pg_fetch_array($tmp_res);
					$ret = $tmp_row[0];
				} else {
					// if this query returns more than one result or
					// no rows returned, something's gone bad
					$ret = false;
				}
			}
			return $ret;
	    }		
	    
	    /**
	     * Start a database transaction.
	     *
	     * @return exception or true
	     */
	    public function begin() {
	    	if (!pg_query($this->resource, "BEGIN")) {
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
	    	if (!pg_query($this->resource, "COMMIT")) {
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
	    	if (!pg_query($this->resource, "ROLLBACK")) {
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
			pg_close($this->resource);
			return $this->resource = null;
		}	
		
        /**
         * Retrieves the last error string, returned by the database
         *
         * @return string
         */

        public function get_error() {
        	return pg_last_error($this->resource);
        }

        /**
         * Retrieves the last connection error string
         *
         * @return string
         */
        public function get_connect_error() {
        	return pg_last_error();
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
			$query = "SELECT relname FROM pg_class WHERE relname = '". $table ."'";
			$result = $this->query($query);
			self::$cache['table_exists'][$table] = count($result) ? true : false;
			return self::$cache['table_exists'][$table];
		}        

		/**
		 * Retrieves information about the fields in a table/result set
		 *
		 * @param string $table
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
				'longblob' => null,
				'bool' => null
			);
			if ($table) {
				// get all keys for given table
				$keys = array();
				$q = "SELECT ic.relname AS index_name,
							 a.attname AS column_name,
							 i.indisunique AS unique_key,
							 i.indisprimary AS primary_key
				  	  FROM pg_class bc, pg_class ic, pg_index i, pg_attribute a
				  	  WHERE bc.oid = i.indrelid
				  	  AND ic.oid = i.indexrelid
				  	  AND ( i.indkey[0] = a.attnum
				  	  		OR i.indkey[1] = a.attnum
				  	  		OR i.indkey[2] = a.attnum
				  	  		OR i.indkey[3] = a.attnum
				  	  		OR i.indkey[4] = a.attnum
				  	  		OR i.indkey[5] = a.attnum
				  	  		OR i.indkey[6] = a.attnum
				  	  		OR i.indkey[7] = a.attnum)
				  	  AND a.attrelid = bc.oid
				  	  AND bc.relname = '{$table}'";
				$result = $this->query($q);
				foreach ($result as $r) {
					$keys[$r->column_name] = $r;
				}
	
				$q = "SELECT a.attname,
							 t.typname,
							 a.attlen,
							 a.atttypmod,
							 a.attnotnull,
							 a.atthasdef,
							 a.attnum
				 	  FROM pg_class c,
				 	  	   pg_attribute a,
				 	  	   pg_type t
				 	  WHERE relkind in ('r','v')
				 	  AND ( c.relname='{$table}'
				 	  		OR c.relname = LOWER('{$table}'))
				 	  AND a.attname not like '....%%'
				 	  AND a.attnum > 0
				 	  AND a.atttypid = t.oid
				 	  AND a.attrelid = c.oid
				 	  ORDER BY a.attnum";
				$result = $this->query($q);
	
				$results = array();
				$ind = 0;
	
				foreach ($result as $r) {
					$ind = $r->attname;
					$results[$ind]['name'] = $r->attname;
	
					$results[$ind]['real_type'] = 	$r->typname == 'int2' ? 'smallint' :
													( $r->typname == 'int4' ? 'int' :
													( $r->typname == 'int8' ? 'int' :
													( $r->typname == 'float8' ? 'double' :
													( $r->typname == 'bytea' ? 'blob' :
													( $r->typname == 'timestamp' ? 'datetime' : $r->typname ) ) ) ) );
	
					$results[$ind]['type'] = $r->typname;
					$results[$ind]['not_null'] = $r->attnotnull === 't';
	
					if (array_key_exists($r->attname, $keys)) {
						$results[$ind]['primary_key'] = $keys[$r->attname]->primary_key === 't';
						$results[$ind]['unique'] = $keys[$r->attname]->unique_key === 't';
					}
					else {
						$results[$ind]['primary_key'] = false;
						$results[$ind]['unique'] = false;
					}
	
					$results[$ind]['max_length'] = $r->attlen;
	
					if ($results[$ind]['max_length'] <= 0) $results[$ind]['max_length'] = $r->atttypmod - 4;
					if ($results[$ind]['max_length'] <= 0) $results[$ind]['max_length'] = -1;
					if ($results[$ind]['type'] == 'numeric') {
						$results[$ind]['scale'] = $results[$ind]['max_length'] & 0xFFFF;
						$results[$ind]['max_length'] >>= 16;
					}
	
					if ($r->typname == 'timestamp') {
						$results[$ind]['max_length'] = 16;
					}
					$results[$ind]['func'] = $functions[$results[$ind]['real_type']];
				}
	
				self::$cache['table_info'][$table] = $results;
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
	
	class PostgreResult extends SqlResult {
		
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
			$this->data = pg_fetch_assoc($this->result);
		}

	    /**
	     * Destructor
	     *
	     * Free db result resource.
	     */
	    public function __destruct() {
	          pg_free_result($this->result);
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
			pg_result_seek($this->result, 0);
			$this->data = pg_fetch_assoc($this->result);
			$this->index = 0;
		}
		
		/**
		 * Implementation of Iterator method next. 
		 * Moves the internal cursor to the next result
		 *
		 * @return array
		 */
		public function next() {
			$this->data = pg_fetch_assoc($this->result);
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
			return (int)pg_num_rows($this->result);
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