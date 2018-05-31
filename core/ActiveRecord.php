<?php

	/**
	 * Active Record class
	 * Each subclass of this class is associated with a database table
 	 * in the Model section of the Model-View-Controller architecture.
 	 *
	 * @package Sweboo
	 * @subpackage Sweboo Database
	 */
	class ActiveRecord {

		/**
		 *  Instance of Database connection
		 *
		 *  @var object DB
		 */
		protected static $db = null;

		/**
		 *  Description of a row in the associated table in the database
		 *
		 *  <p>Retrieved from the RDBMS by {@link setse()}.
		 *  See {@link
		 *  http://pear.php.net/manual/en/package.database.db.db-common.tableinfo.php
		 *  DB_common::tableInfo()} for the format.  <b>NOTE:</b> Some
		 *  RDBMS's don't return all values.</p>
		 *
		 *  <p>An additional element 'human_name' is added to each column
		 *  by {@link set_content_columns()}.  The actual value contained
		 *  in each column is stored in an object variable with the name
		 *  given by the 'name' element of the column description for each
		 *  column.</p>
		 *
		 *  <p><b>NOTE:</b>The information from the database about which
		 *  columns are primary keys is <b>not used</b>.  Instead, the
		 *  primary keys in the table are listed in {@link $primary_keys},
		 *  which is maintained independently.</p>
		 *  @var string[]
		 *  @see $primary_keys
		 *  @see quoted_attributes()
		 *  @see __set()
		 */
		public $content_columns = null; # info about each column in the table
		public $content_columns_i18n = null; # info about each column in the table
		public $content_columns_all = null; # info about each column in the table

		/**
		 *  Table name
		 *
		 *  Name of the table in the database associated with the subclass.
		 *  Normally set to the pluralized lower case underscore form of
		 *  the class name by the constructor.  May be overridden.
		 *  @var string
		 */
		public $orig_table_name = null;

		/**
		 *	Concatenated DB_PREFIX with {@link ActiveRecord::$table_name}
		 *
		 *  @var string
		 */
		public $table_name = null;

		/**
		 * find_all returns an array of objects each object index is off of this field
		 *
		 * @var string
		 */
		public $index_on = "id";

		/**
		 *  @todo Document this variable
		 *  @var string[]
		 */
		protected $has_many = null;

		/**
		 *  @todo Document this variable
		 *  @var string[]
		 */
		protected $has_one = null;

		/**
		 *  @todo Document this variable
		 *  @var string[]
		 */
		protected $has_and_belongs_to_many = null;

		/**
		 *  @todo Document this variable
		 *  @var string[]
		 */
		protected $belongs_to = null;

		/**
		 *  @todo Document this property
		 *  @var boolean
		 */
		protected $habtm_attributes = null;

		/**
		 *  @todo Document this property
		 *  @var boolean
		 */
		protected $save_associations = array();

		/**
		 *  @todo Document this property
		 *  @var boolean
		 */
		public $auto_save_associations = true;

		/**
		 *  @todo Document this variable
		 *  @var string[]
		 */
		protected $cache = null;

		/**
		 *  Whether this object represents a new record
		 *
		 *  - true => 	This object was created without reading a row from the
		 *				database, so use SQL 'INSERT' to put it in the database.
		 *  - false =>	This object was a row read from the database, so use
		 *				SQL 'UPDATE' to update database with new values.
		 *
		 *  @var boolean
		 */
		protected $new_record = true;

		/**
		 *  Names of automatic update timestamp columns
		 *
		 *  When a row containing one of these columns is updated and
		 *  {@link $auto_timestamps} is true, update the contents of the
		 *  timestamp columns with the current date and time.
		 *  @see $auto_timestamps
		 *  @see $auto_create_timestamps
		 *  @var string[]
		 */
		protected $auto_update_timestamps = array("updated_at","updated_on");

		/**
		 *  Names of automatic create timestamp columns
		 *
		 *  When a row containing one of these columns is created and
		 *  {@link $auto_timestamps} is true, store the current date and
		 *  time in the timestamp columns.
		 *  @see $auto_timestamps
		 *  @see $auto_update_timestamps
		 *  @var string[]
		 */
		protected $auto_create_timestamps = array("created_at","created_on");

		/**
		 *  Date format for use with auto timestamping
		 *
		 *  The format for this should be compatiable with the php {@link date() date()} function.
		 *  @var string
		 */
		protected $date_format = "Y-m-d";

		/**
		 *  Time format for use with auto timestamping
		 *
		 *  The format for this should be compatiable with the php {@link date() date()} function.
		 *  @var string
		 */
		protected $time_format = "H:i:s";

		/**
		 *  Whether to keep date/datetime fields NULL if not set
		 *
		 *  - true => If date field is not set it try to preserve NULL
		 *  - false => Don't try to preserve NULL if field is already NULL
		 *
		 *  @var boolean
		 */
		protected $preserve_null_dates = true;

		 /**
		  * @todo document this variable
		  *
		  * @var boolean
		  */
		protected $preserve_index = false;

		/**
		 *  SQL aggregate functions that may be applied to the associated
		 *  table.
		 *
		 *  SQL defines aggregate functions AVG, COUNT, MAX, MIN and SUM.
		 *  Not all of these functions are implemented by all DBMS's
		 *  @var string[]
		 */
		protected $aggregrations = array("count","sum","avg","max","min");

		/**
		 * Whether the table we're looking at has a corresponding table, holding
		 * internationalized values
		 *
		 * @var boolean
		 */
		protected $is_i18n = false;

		/**
		 * Keeps the currently selected locale identifier
		 *
		 * @var string
		 */
		protected $i18n_locale;

		/**
		 * Table name suffix for i18n tables
		 *
		 * @var string
		 */
		protected $i18n_table_suffix = '_i18n';

		/**
		 * Info about multilingual columns
		 *
		 * @var array
		 */
		protected $i18n_columns = null;

		/**
		 * International column names
		 *
		 * @var array
		 */
		protected $i18n_column_names = array();

		/**
		 * Column name to value mapping for different locales
		 *
		 * @var array
		 */
		protected $i18n_column_values = array();

		/**
		 * Name of the corresponding i18n table
		 *
		 * @var string
		 */
		protected $i18n_table;

		/**
		 * Reserved columns for the internationalized tables
		 *
		 * @var array
		 */
		protected $i18n_reserved_columns = array('i18n_foreign_key', 'i18n_locale');

		/**
		 * Name of the foreign key field for i18n tables
		 *
		 * @var string
		 */
		protected $i18n_foreign_key_field = 'i18n_foreign_key';

		/**
		 * Name of the locale field for i18n tables
		 *
		 * @var string
		 */
		protected $i18n_locale_field = 'i18n_locale';

		/**
		 *  Primary key of the associated table
		 *
		 *  Array element(s) name the primary key column(s), as used to
		 *  specify the row to be updated or deleted.  To be a primary key
		 *  a column must be listed both here and in {@link Sql::table_info()}.  <b>NOTE:</b>This
		 *  field is maintained by hand.  It is not derived from the table
		 *  description read from the database.
		 *  @var string[][]
		 *  @link Sql::table_info()
		 *  @see find()
		 *  @see find_all()
		 *  @see find_first()
		 */
		public $primary_keys = array("id");

		/**
		 *  Default for how many rows to return from {@link find_all()}
		 *  @var integer
		 */
		public $rows_per_page_default = 20;

		/**
		 *  @todo Document this variable
		 */
		public $pagination_count = 0;

		/**
		 *  Description of non-fatal errors found
		 *
		 *  For every non-fatal error found, an element describing the
		 *  error is added to $errors.  Initialized to an empty array in
		 *  {@link valid()} before validating object.  When an error
		 *  message is associated with a particular attribute, the message
		 *  should be stored with the attribute name as its key.  If the
		 *  message is independent of attributes, store it with a numeric
		 *  key beginning with 0.
		 *
		 *  @var string[]
		 *  @see add_error()
		 *  @see get_errors()
		 */
		public $errors = array();

		/**
		 *  Whether to automatically update timestamps in certain columns
		 *
		 *  @see $auto_create_timestamps
		 *  @see $auto_update_timestamps
		 *  @var boolean
		 */
		public $auto_timestamps = true;

		/**
		 * Auto insert / update $has_and_belongs_to_many tables
		 */
		public $auto_save_habtm = true;

		/**
		 *  Auto delete $has_and_belongs_to_many associations
		 */
		public $auto_delete_habtm = true;

		/**
		 *  Transactions (only use if your db supports it)
		 *  This is for transactions only to let query() know that a 'BEGIN' has been executed
		 */
		private static $begin_executed = false;

		/**
		 *  Transactions (only use if your db supports it)
		 *  This will issue a rollback command if any sql fails.
		 */
		public static $use_transactions = false;

		/**
		 * @todo Document this property
		 * @var boolean
		 */
		protected $has_mirror = null;

		/**
		 * @todo document this variable
		 * @var boolean
		 */
		protected $auto_assign_errors = true;

		/**
		 * @todo document this variable
		 * @var object[]
		 */
		protected static $cached = array();

		/**
		 * Store info about the next query cache ttl executed
		 * by $this->query. Afterwards is set back to false
		 *
		 * @var int
		 */
		private	$cache_ttl = false;

		/**
		 * @todo Document this property
		 * @var array
		 */
		private $cached_results = array();

		/**
		 * @todo Document this property
		 * @var boolean
		 */
		private static $has_update_blob = false;

		/**
		 * @todo Document this property
		 * @var string[]
		 */
		private $blob_fields = array();

		/**
		 *  Construct an ActiveRecord object
		 *
		 *  <ol>
		 *	<li>Establish a connection to the database</li>
		 *	<li>Find the name of the table associated with this object</li>
		 *	<li>Read description of this table from the database</li>
		 *	<li>Optionally apply update information to column attributes</li>
		 *  </ol>
		 *
		 *  @param string[] $attributes Updates to column attributes
		 *  @uses ActiveRecord::establish_connection()
		 *  @uses ActiveRecord::set_content_columns()
		 *  @uses ActiveRecord::$table_name
		 *  @uses ActiveRecord::set_table_name_using_class_name()
		 *  @uses ActiveRecord::update_attributes()
		 */
		function __construct($attributes = null)
		{
			if (is_null($this->has_mirror)) {
				$this->has_mirror = Config()->DB_MIRROR;
			}

			if(!self::$db) {
				$this->establish_connection();
			}

			if($this->table_name == null) {
				$this->set_table_name_using_class_name();
			}

			if($this->table_name) {
				$this->set_content_columns($this->table_name);
			}

			if(is_array($attributes)) {
				$this->update_attributes($attributes);
			}
		}

		/**
		 *  Override get() if they do $model->some_association->field_name
		 *  dynamically load the requested contents from the database.
		 *  @todo Document this API
		 *  @uses ActiveRecord::$belongs_to
		 *  @uses ActiveRecord::get_association_type()
		 *  @uses ActiveRecord::$has_and_belongs_to_many
		 *  @uses ActiveRecord::$has_many
		 *  @uses ActiveRecord::$has_one
		 *  @uses ActiveRecord::find_all_has_many()
		 *  @uses ActiveRecord::find_all_habtm()
		 *  @uses ActiveRecord::find_one_belongs_to()
		 *  @uses ActiveRecord::find_one_has_one()
		 */
		function __get($key) {
			if(in_array($key, $this->i18n_column_names)) {
				return $this->i18n_column_values[$key][$this->get_locale()];
			}

			$association_type = $this->get_association_type($key);
			switch($association_type) {
				case "has_many":
					$parameters = is_array($this->has_many) ? $this->has_many[$key] : null;
					$this->$key = $this->find_all_has_many($key, $parameters);
					break;
				case "has_one":
					$parameters = is_array($this->has_one) ? $this->has_one[$key] : null;
					$this->$key = $this->find_one_has_one($key, $parameters);
					break;
				case "belongs_to":
					$parameters = is_array($this->belongs_to) ? $this->belongs_to[$key] : null;
					$this->$key = $this->find_one_belongs_to($key, $parameters);
					break;
				case "has_and_belongs_to_many":
					$parameters = is_array($this->has_and_belongs_to_many) ? $this->has_and_belongs_to_many[$key] : null;
					$this->$key = $this->find_all_habtm($key, $parameters);
					break;
			}

			if (!is_null($association_type)) {
				return $this->$key;
			}

			if (is_array($this->cache) && array_key_exists($key, $this->cache)) {
				return $this->load_cache($key);
			}

			return null;
		}

		/**
		 *  Store column value or description of the table format
		 *
		 *  If called with key 'table_name', $value is stored as the
		 *  description of the table format in $content_columns.
		 *  Any other key causes an object variable with the same name to
		 *  be created and stored into.  If the value of $key matches the
		 *  name of a column in content_columns, the corresponding object
		 *  variable becomes the content of the column in this row.
		 *  @uses ActiveRecord::$auto_save_associations
		 *  @uses ActiveRecord::get_association_type()
		 *  @uses ActiveRecord::set_content_columns()
		 */
		function __set($key, $value) {
			if($key == "table_name") {
				$this->set_content_columns($value);
			} elseif(is_object($value) && $value instanceof self && $this->auto_save_associations) {
				if($association_type = $this->get_association_type($key)) {
					$this->save_associations[$association_type][] = $value;
					if($association_type == "belongs_to") {
						$foreign_key = Inflector::singularize($value->table_name)."_id";
						$this->$foreign_key = $value->id;
					}
				}
			}
			elseif(in_array($key, $this->i18n_column_names)) {
				if(is_array($value)) {
					foreach($value as $locale => $the_value) {
						$this->i18n_column_values[$key][$locale] = $the_value;
					}
				} else {
					$this->i18n_column_values[$key][$this->get_locale()] = $value;
				}
				return $value;
			}
			elseif(is_array($value) && $this->auto_save_associations) {
				if($association_type = $this->get_association_type($key)) {
					$this->save_associations[$association_type][] = $value;
				}
			}
			$this->$key = $value;
		}

		/**
		 *  Override call() to dynamically call the database associations
		 *  @todo Document this API
		 *  @uses ActiveRecord::$aggregrations
		 *  @uses ActiveRecord::aggregate_all()
		 *  @uses ActiveRecord::get_association_type()
		 *  @uses ActiveRecord::$belongs_to
		 *  @uses ActiveRecord::$has_one
		 *  @uses ActiveRecord::$has_and_belongs_to_many
		 *  @uses ActiveRecord::$has_many
		 *  @uses ActiveRecord::find_all_by()
		 *  @uses ActiveRecord::find_by()
		 */
		function __call($method_name, $parameters) {
			if(method_exists($this,$method_name)) {
				return $this->$method_name($parameters);
			} else {
				if(substr($method_name, 0, 4) == "set_") {
					return $this->set_field_value($method_name, $parameters);
				}
				elseif(substr($method_name, 0, 4) == "get_") {
					return $this->get_field_value($method_name, $parameters);
				}

				$association_type = $this->get_association_type($method_name);
				if($association_type && is_array($this->$association_type)) {
					$parameters = array_merge($this->{$association_type}[$method_name], $parameters);
				}

				switch($association_type) {
					case "has_many":
						return $this->find_all_has_many($method_name, $parameters);
						break;
					case "has_one":
						return $this->find_one_has_one($method_name, $parameters);
						break;
					case "belongs_to":
						return $this->find_one_belongs_to($method_name, $parameters);
						break;
					case "has_and_belongs_to_many":
						return $this->find_all_habtm($method_name, $parameters);
						break;
				}

				if(substr($method_name, -4) == "_all" && in_array(substr($method_name, 0, -4), $this->aggregrations)) {
					return $this->aggregate_all($method_name, $parameters);
				}
				elseif(strlen($method_name) > 11 && substr($method_name, 0, 11) == "find_all_by") {
					return $this->find_by($method_name, $parameters, "all");
				}
				elseif(strlen($method_name) > 7 && substr($method_name, 0, 7) == "find_by") {
					return $this->find_by($method_name, $parameters);
				}
				elseif(strlen($method_name) > 17 && substr($method_name, 0, 17) == "find_or_create_by") {
					return $this->find_by($method_name, $parameters, "find_or_create");
				}else if (is_array($this->cache) && array_key_exists($method_name, $this->cache)) {
					return $this->load_cache($method_name);
				}
			}
			return null;
		}

	 	/**
	 	 *  Sets field value according to $parameters
	 	 *  Supports i18n.
	 	 *
	 	 *  @param string $method_name Name of the method the function was called as
	 	 *  @param array $parameters Array of parameters
	 	 */
		private function set_field_value($method_name, $parameters) {
			$requested_field = substr($method_name, 4);

			if(is_array($parameters)) {
				if(is_array($parameters[0])) {
					$values_to_set = array();
					foreach($parameters as $locale => $fields) {
						foreach($fields as $field => $value) {
							$values_to_set[$field][$locale] = $value;
						}
					}

					return $this->{$requested_field} = $values_to_set;
				} elseif(count($parameters) > 1) {
					$locale = $parameters[1];
					return $this->i18n_column_values[$requested_field][$locale] = $parameters[0];
				} else {
					return $this->{$requested_field} = $parameters[0];
				}
			} else {
				return null;
			}
		}

	 	/**
	 	 *  Field value getter. Supports i18n.
	 	 *
	 	 *  - Support for retrieving all i18n versions of a field as an array.
	 	 *  - Retrieval of non-cached i18n columns directly from the database.
	 	 *
	 	 *  @access private
	 	 *  @param string $method_name
	 	 *  @param array $parameters
	 	 *  @return mixed
	 	 *
	 	 */
		private function get_field_value($method_name, $parameters) {
			$requested_field = substr($method_name, 4);

			if(in_array($requested_field, $this->i18n_column_names)) {
				$locale = isset($parameters[0]) ? $parameters[0] : $this->get_locale();

				if(!isset($this->i18n_column_values[$requested_field][$locale])) {
					$this->i18n_get_values();
				}

				return $this->i18n_column_values[$requested_field][$locale];
			} else {
				return $this->{$requested_field};
			}
		}

		/**
	 	 *  Returns a the name of the join table that would be used for the two
	 	 *  tables.  The join table name is decided from the alphabetical order
	 	 *  of the two tables.  e.g. "genres_movies" because "g" comes before "m"
	 	 *
	 	 *  @param string $first_table name two database tables
	 	 *  @param string $second_table name two database tables
	 	 *  @todo Document this API
	 	 *  @return string
	 	 */
		protected function get_join_table_name($first_table, $second_table) {
			if (is_array($this->has_and_belongs_to_many[$second_table])
				&& array_key_exists('join_table', $this->has_and_belongs_to_many[$second_table]))
			{
				return Config()->DB_PREFIX . $this->has_and_belongs_to_many[$second_table]['join_table'];
			} else {
				$tables = array();
				$tables["one"] = str_replace(Config()->DB_PREFIX, "", $first_table);
				$tables["many"] = str_replace(Config()->DB_PREFIX, "", $second_table);
				@asort($tables);
				return Config()->DB_PREFIX . @implode("_", $tables);
			}
		}

		/**
		 *  Find all records using a "has_and_belongs_to_many" relationship
	 	 * (many-to-many with a join table in between).  Note that you can also
	 	 *  specify an optional "paging limit" by setting the corresponding "limit"
	 	 *  instance variable.  For example, if you want to return 10 movies from the
	 	 *  5th movie on, you could set $this->movies_limit = "10, 5"
	 	 *
	 	 *  @param string $this_table_name The name of the database table that has the one row you are interested in. E.g. genres
	 	 *  @param string $other_table_name The name of the database table that has the many rows you are interested in.  E.g. movies
	 	 *  @todo Document this API
	 	 *  @return array Array of ActiveRecord objects. (e.g. Movie objects)
	 	 */
		private function find_all_habtm($other_table_name, $parameters = null) {
			$other_object_name = $finder_sql = $join_table = $this_foreign_key = null;
			$other_foreign_key = $additional_conditions = $order = $limit = null;

			if (!is_null($parameters)) {
				if(array_key_exists("conditions", $parameters)) {
					$additional_conditions = " AND (".$parameters['conditions'].")";
				} elseif(isset($parameters[0]) && $parameters[0] != "") {
					$additional_conditions = " AND (".$parameters[0].")";
				}
				if(array_key_exists("order", $parameters)) {
					$order = $parameters['order'];
				} elseif(isset($parameters[1]) && $parameters[1] != "") {
					$order = $parameters[1];
				}
				if(array_key_exists("limit", $parameters)) {
					$limit = $parameters['limit'];
				} elseif(isset($parameters[2]) && $parameters[2] != "") {
					$limit = $parameters[2];
				}
				if(array_key_exists("class_name", $parameters)) {
					$other_object_name = $parameters['class_name'];
					$obj = Inflector::camelize($other_object_name);
					$obj = new $obj;
					$other_table_name = $obj->orig_table_name;
				}
				if(array_key_exists("join_table", $parameters)) {
					$join_table = Config()->DB_PREFIX . $parameters['join_table'];
				}
				if(array_key_exists("foreign_key", $parameters)) {
					$this_foreign_key = $parameters['foreign_key'];
				}
				if(array_key_exists("association_foreign_key", $parameters)) {
					$other_foreign_key = $parameters['association_foreign_key'];
				}
				if(array_key_exists("finder_sql", $parameters)) {
					$finder_sql = $parameters['finder_sql'];
				}
			}

			if(!is_null($other_object_name)) {
				$other_class_name = Inflector::camelize($other_object_name);
			} else {
				$other_class_name = Inflector::classify($other_table_name);
			}

			$other_class_object = new $other_class_name();

			if($this->is_i18n && $other_class_object->is_i18n)
				$other_class_object->set_locale($this->get_locale());

			if(!is_null($finder_sql)) {
				$conditions = $finder_sql;
				$order = null;
				$limit = null;
				$joins = null;
			} else {
				if(is_null($join_table)) {
					$join_table = $this->get_join_table_name($this->table_name, $other_table_name);
				}

				$this_primary_key  = $this->primary_keys[0];
				$other_primary_key = $other_class_object->primary_keys[0];

				if(is_null($this_foreign_key)) {
					$this_foreign_key = Inflector::singularize($this->orig_table_name)."_".$this_primary_key;
				}
				if(is_null($other_foreign_key)) {
					$other_foreign_key = Inflector::singularize($other_table_name)."_".$other_primary_key;
				}

				$this_primary_key_value = is_numeric($this->$this_primary_key) ? $this->$this_primary_key : "'".$this->$this_primary_key."'";

				$other_table_name = Config()->DB_PREFIX . $other_table_name;
				$conditions = "{$join_table}.{$this_foreign_key} = {$this_primary_key_value}".$additional_conditions;
				$joins = "LEFT JOIN {$join_table} ON {$other_table_name}.{$other_primary_key} = {$join_table}.{$other_foreign_key}";

			}
			return $other_class_object->find_all($conditions, $order, $limit, $joins);
		}

		/**
	 	 *  Find all records using a "has_many" relationship (one-to-many)
	 	 *
	 	 *  @param string $other_table_name The name of the other table that contains many rows relating to this object's id.
	 	 *
	 	 *  @todo Document this API
	 	 *  @return array An array of ActiveRecord objects. (e.g. Contact objects)
	 	 */
		private function find_all_has_many($other_table_name, $parameters = null) {

			$additional_conditions = $other_object_name = $finder_sql = $foreign_key = $order = $limit = $joins = null;

			if (is_array($parameters)) {
				if(@array_key_exists("conditions", $parameters)) {
					$additional_conditions = " AND (".$parameters['conditions'].")";
				} elseif(isset($parameters[0]) && $parameters[0] != "") {
					$additional_conditions = " AND (".$parameters[0].")";
				}
				if(@array_key_exists("order", $parameters)) {
					$order = $parameters['order'];
				} elseif(isset($parameters[1]) && $parameters[1] != "") {
					$order = $parameters[1];
				}
				if(@array_key_exists("limit", $parameters)) {
					$limit = $parameters['limit'];
				} elseif(isset($parameters[2]) && $parameters[2] != "") {
					$limit = $parameters[2];
				}
				if(@array_key_exists("foreign_key", $parameters)) {
					$foreign_key = $parameters['foreign_key'];
				}
				if(@array_key_exists("class_name", $parameters)) {
					$other_object_name = $parameters['class_name'];
				}
				if(@array_key_exists("finder_sql", $parameters)) {
					$finder_sql = $parameters['finder_sql'];
				}
			}

			if(!is_null($other_object_name)) {
				$other_class_name = Inflector::camelize($other_object_name);
			} else {
				$other_class_name = Inflector::classify($other_table_name);
			}

			$other_class_object = new $other_class_name();
			if($this->is_i18n && $other_class_object->is_i18n)
				$other_class_object->set_locale($this->get_locale());

			if(!is_null($finder_sql)) {
				$conditions = $finder_sql;
				$order = null;
				$limit = null;
				$joins = null;
			} else {
				$this_primary_key = $this->primary_keys[0];

				if(!$foreign_key) {
						$foreign_key = Inflector::singularize($this->orig_table_name)."_".$this_primary_key;
				}

				$foreign_key_value = $this->$this_primary_key;

				$conditions = is_numeric($foreign_key_value) ?
					"$foreign_key = {$foreign_key_value}":
					"$foreign_key = '{$foreign_key_value}'";

				$conditions .= $additional_conditions;
			}

			return $other_class_object->find_all($conditions, $order, $limit, $joins);
		}

	 	/**
	 	 *  Find all records using a "has_one" relationship (one-to-one)
	 	 *  (the foreign key being in the other table)
	 	 *  @param string $other_table_name The name of the other table that contains many rows relating to this object's id.
	 	 *  @return array An array of ActiveRecord objects. (e.g. Contact objects)
	 	 *  @todo Document this API
	 	 */
		private function find_one_has_one($other_object_name, $parameters = null) {
			if (is_array($parameters)) {

				if(@array_key_exists("conditions", $parameters)) {

					$additional_conditions = " AND (".$parameters['conditions'].")";
				} elseif($parameters[0] != "") {
					$additional_conditions = " AND (".$parameters[0].")";
				}
				if(@array_key_exists("order", $parameters)) {
					$order = $parameters['order'];
				} elseif($parameters[1] != "") {
					$order = $parameters[1];
				}

				if(@array_key_exists("foreign_key", $parameters)) {
					$foreign_key = $parameters['foreign_key'];
				}
				elseif(@array_key_exists("foreign_key", $this->has_one[$other_object_name])) {
					$foreign_key = $this->has_one[$other_object_name]['foreign_key'];
				}

				if(@array_key_exists("class_name", $parameters)) {
					$other_object_name = $parameters['class_name'];
				}
				elseif(@array_key_exists("class_name", $this->has_one[$other_object_name])) {
					$other_object_name = $this->has_one[$other_object_name]['class_name'];
				}
			}

			$other_class_name = Inflector::camelize($other_object_name);

			$other_class_object = new $other_class_name();
			if($this->is_i18n && $other_class_object->is_i18n)
				$other_class_object->set_locale($this->get_locale());

			//$this_primary_key = $this->primary_keys[0];
			$this_primary_key = $parameters['association_foreign_key'];

			if(!$foreign_key){
				$foreign_key = Inflector::singularize($this->table_name)."_".$this_primary_key;
			}

			$foreign_key_value = $this->$this_primary_key;
			$conditions = is_numeric($foreign_key_value) ?
				"{$foreign_key} = {$foreign_key_value}" :
				"{$foreign_key} = '{$foreign_key_value}'";

			$conditions .= $additional_conditions;

			$result = $other_class_object->find_first($conditions, $order);
			if(is_object($result)) {
				return $result;
			} else {
				return null;
			}
		}

	 	/**
	 	 *  Find all records using a "belongs_to" relationship (one-to-one)
	 	 *  (the foreign key being in the table itself)
	 	 *  @param string $other_object_name The singularized version of a table name.
		* 	 	 	   E.g. If the Contact class belongs_to the
		* 	 	 	   Customer class, then $other_object_name
		* 	 	 	   will be "customer".
	 	 *  @todo Document this API
	 	 *  @return object
	 	 */
		private function find_one_belongs_to($other_object_name, $parameters = null) {

			$order = null;

			if (is_array($parameters)) {
				if(@array_key_exists("conditions", $parameters)) {
					$additional_conditions = " AND ({$parameters['conditions']})";
				} elseif($parameters[0] != "") {
					$additional_conditions = " AND ({$parameters[0]})";
				}
				if(@array_key_exists("order", $parameters)) {
					$order = $parameters['order'];
				} elseif($parameters[1] != "") {
					$order = $parameters[1];
				}
				if(@array_key_exists("foreign_key", $parameters)) {
					$foreign_key = $parameters['foreign_key'];
				}
				if(@array_key_exists("association_foreign_key", $parameters)) {
					$other_primary_key = $parameters['association_foreign_key'];
				}
				if(@array_key_exists("class_name", $parameters)) {
					$other_object_name = $parameters['class_name'];
				}
			}

			$other_class_name = Inflector::camelize($other_object_name);

			$other_class_object = new $other_class_name();

			if($this->is_i18n && $other_class_object->is_i18n)
				$other_class_object->set_locale($this->get_locale());

			$other_primary_key = !isset($other_primary_key) ? $other_class_object->primary_keys[0] : $other_primary_key;

			if(!isset($foreign_key)) {
				$foreign_key = $other_object_name."_".$other_primary_key;
			}

			$other_primary_key_value = $this->$foreign_key;
			$conditions = is_numeric($other_primary_key_value) ?
				"{$other_primary_key} = {$other_primary_key_value}":
				"{$other_primary_key} = '{$other_primary_key_value}'";

			$conditions .= $additional_conditions;


			$result = $other_class_object->find_first($conditions, $order);
			if(is_object($result)) {
				return $result;
			} else {
				return null;
			}
		}

	 	/**
	 	 *  Implement *_all() functions (SQL aggregate functions)
	 	 *
	 	 *  Apply one of the SQL aggregate functions to a column of the
	 	 *  table associated with this object.  The SQL aggregate
	 	 *  functions are AVG, COUNT, MAX, MIN and SUM.  Not all DBMS's
	 	 *  implement all of these functions.
	 	 *  @param string $agrregate_type SQL aggregate function to
	 	 * 	apply, suffixed '_all'.  The aggregate function is one of
	 	 *  the strings in {@link $aggregations}.
	 	 *  @param string[] $parameters  Conditions to apply to the
	 	 * 	aggregate function.  If present, must be an array of three
	 	 * 	strings:<ol>
	 	 * 	 <li>$parameters[0]: If present, expression to apply
	 	 * 	   the aggregate function to.  Otherwise, '*' will be used.
	 	 * 	   <b>NOTE:</b>SQL uses '*' only for the COUNT() function,
	 	 * 	   where it means "including rows with NULL in this column".</li>
	 	 * 	 <li>$parameters[1]: argument to WHERE clause</li>
	 	 * 	 <li>$parameters[2]: joins??? @todo Document this parameter</li>
	 	 * 	</ol>
	 	 *  @throws {@link ActiveRecordException}
	 	 *  @uses ActiveRecord::query()
	 	 */
		private function aggregate_all($aggregate_type, $parameters = null) {
			$aggregate_type = strtoupper(substr($aggregate_type, 0, -4));
			($parameters[0]) ? $field = $parameters[0] : $field = "*";
			$sql = "SELECT $aggregate_type($field) AS agg_result FROM $this->table_name ";

			if($this->is_i18n) {
				$sql .= "LEFT JOIN ".$this->i18n_table."
							ON {$this->table_name}.{$this->primary_keys[0]} = {$this->i18n_table}.{$this->i18n_foreign_key_field}
							AND {$this->i18n_table}.{$this->i18n_locale_field} = '".$this->get_locale()."' ";
			}

			if (!is_null($parameters)) {
				if(isset($parameters[1])) {
					$conditions = $parameters[1];
				}
				if(isset($parameters[2])) {
					$joins = $parameters[2];
				}
			}

			if($this->is_i18n && !$this->has_mirror) {
				if (!empty($conditions) ) {
					$conditions .= " AND ";
				}
				$conditions .= $this->i18n_table . '.' . $this->i18n_locale_field . " IS NOT NULL";
			}

			if(!empty($joins)) $sql .= in_array(strtolower(substr($joins, 0, 5)), array("left ", "inner", "right")) ? " $joins " : " , $joins ";
			if(!empty($conditions)) $sql .= "WHERE $conditions ";

			$result = $this->query($sql);

			if(!$result) {
				$this->raise('ActiveRecord: Could not perform aggregation.');
			} else {
				if($result["agg_result"]) {
					return $result["agg_result"];
				}
			}
			return 0;
		}

	 	/**
	 	 *  Test whether this object represents a new record
	 	 *  @uses ActiveRecord::$new_record
	 	 *  @return boolean Whether this object represents a new record
	 	 */
		function is_new_record() {
			return $this->new_record;
		}

		function have_i18n() {
			return $this->is_i18n;
		}

		function get_belongs_to(){
			return $this->belongs_to;
		}

		function get_has_many(){
			return $this->has_many;
		}

		function get_has_one(){
			return $this->has_one;
		}


		function get_habtms(){
			return $this->has_and_belongs_to_many;
		}

	 	/**
	 	 *  Check whether a column exists in the associated table
	 	 *
	 	 *  When called, {@link $content_columns} lists the columns in
	 	 *  the table described by this object.
	 	 *  @param string Name of the column
	 	 *  @return boolean true=>the column exists; false=>it doesn't
	 	 *  @uses ActiveRecord::content_columns
	 	 */
		function column_attribute_exists($attribute) {
			return is_array($this->content_columns_all) && array_key_exists($attribute, $this->content_columns_all);
		}

	 	/**
	 	 *  Get contents of one column of record selected by id and table
	 	 *
	 	 *  When called, {@link $id} identifies one record in the table
	 	 *  identified by {@link $table}.  Fetch from the database the
	 	 *  contents of column $column of this record.
	 	 *  @param string Name of column to retrieve
	 	 *  @uses ActiveRecord::$db
	 	 *  @uses ActiveRecord::column_attribute_exists()
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		function send($column) {
			if($this->column_attribute_exists($column) && ($conditions = $this->get_primary_key_conditions())) {
				$sql = "SELECT $column FROM $this->table_name WHERE $conditions";
				$result = self::$db->select_one($sql);
			}
			return $result;
		}

	 	/**
	 	 * Only used if you want to do transactions and your db supports transactions
	 	 */
		function begin() {
			self::$db->begin();
			$this->begin_executed = true;
		}

	 	/**
	 	 *  Only used if you want to do transactions and your db supports transactions
	 	 */
		function commit() {
			self::$db->commit();
			$this->begin_executed = false;
		}

	 	/**
	 	 *  Only used if you want to do transactions and your db supports transactions
	 	 */
		function rollback() {
			self::$db->rollback();
		}

		/**
		 * Sets time for caching for executed query.
		 *
		 * Usage: $model->cache(time)->find_*
		 *
		 * After the find_* method is executed the cache is set back to false
		 *
		 * @param int $time
		 * @return object $this
		 */
		final public function cache($time = false) {
			$this->cache_ttl = $time;
			return $this;
		}

		/**
	 	 *  Perform an SQL query and return the results
	 	 *
	 	 *  @param string $sql  SQL for the query command
	 	 *  @uses ActiveRecord::$db
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		function query($sql) {
			return self::$db->query($sql, $this->cache_ttl);
		}

	 	/**
	 	 *  Implement find_by_*() and find_all_by_* methods
	 	 *
	 	 *  Converts a method name beginning 'find_by_' or 'find_all_by_'
	 	 *  into a query for rows matching the rest of the method name and
	 	 *  the arguments to the function.  The part of the method name
	 	 *  after '_by' is parsed for columns and logical relationships
	 	 *  (AND and OR) to match.  For example, the call
	 	 * 	find_by_fname('Ben')
	 	 *  is converted to
	 	 * 	SELECT * ... WHERE fname='Ben'
	 	 *  and the call
	 	 * 	find_by_fname_and_lname('Ben','Dover')
	 	 *  is converted to
	 	 * 	SELECT * ... WHERE fname='Ben' AND lname='Dover'
	 	 *
	 	 *  @uses ActiveRecord::find_all()
	 	 *  @uses ActiveRecord::find_first()
	 	 */
		private function find_by($method_name, $parameters, $find_type = null) {
			if($find_type == "find_or_create") {
				$explode_len = 18;
			} elseif($find_type == "all") {
				$explode_len = 12;
			} else {
				$explode_len = 8;
			}
			$conditions = '';

			$method_name = substr(strtolower($method_name), $explode_len);
			$method_parts = explode("|", str_replace("_and_", "|AND|", $method_name));
			if(count($method_parts)) {
				$options = array();
				$create_fields = array();
				$param_index = 0;
				foreach($method_parts as $part) {

					if($part == "AND") {
						$conditions .= " AND ";
						$param_index++;
					} else {
						if (is_array($parameters[$param_index])) {
							$value = $parameters[$param_index];
							$value = array_map(array(self::$db, 'escape'), $value);
							$value = "'".implode("', '", $value)."'";
							$conditions .= "{$part} IN ({$value})";
						} else {
							$value = is_numeric($parameters[$param_index]) ? $parameters[$param_index] : "'".self::$db->escape($parameters[$param_index])."'";
							$create_fields[$part] = $parameters[$param_index];
							$conditions .= "{$part} = {$value}";
						}
					}
				}

				if(isset($parameters[++$param_index]) && $last_param = $parameters[$param_index]) {
					if(is_string($last_param)) {
						$options['order'] = $last_param;
					} elseif(is_array($last_param)) {
						$options = $last_param;
					}
				}

				if(isset($options['conditions']) && $conditions) {
					$options['conditions'] = "(".$options['conditions'].") AND (".$conditions.")";
				} else {
					$options['conditions'] = $conditions;
				}

				if($find_type == "find_or_create") {
					$object = $this->find($options);
					if(is_object($object)) {
						$object->is_created = false;
						return $object;
					} elseif(count($create_fields)) {
						foreach($create_fields as $field => $value) {
							$this->$field = $value;
						}
						$this->save();
						$object = $this->find($options);
						$object->is_created = true;
						return $object;
					}
				} elseif($find_type == "all") {
					return $this->find_all($options);
				} else {
					return $this->find($options);
				}
			}
		}

	 	/**
	 	 *  Return rows selected by $conditions
	 	 *
	 	 *  If no rows match, an empty array is returned.
	 	 *  @param string  SQL to use in the query.  If
	 	 * 	$conditions contains "SELECT", then $order, $limit and
	 	 * 	$joins are ignored and the query is completely specified by
	 	 * 	$conditions.  If $conditions is omitted or does not contain
	 	 * 	"SELECT", "SELECT * FROM" will be used.  If $conditions is
	 	 * 	specified and does not contain "SELECT", the query will
	 	 * 	include "WHERE $conditions".  If $conditions is null, the
	 	 * 	entire table is returned.
	 	 *  @param string  Argument to "ORDER BY" in query.
	 	 * 	If specified, the query will include
	 	 * 	"ORDER BY $order". If omitted, no ordering will be
	 	 * 	applied.
	 	 *  @param integer[] Page, rows per page???
	 	 *  @param string ???
	 	 *  @todo Document the $limit and $joins parameters
	 	 *  @uses ActiveRecord::$rows_per_page_default
	 	 *  @uses ActiveRecord::$rows_per_page
	 	 *  @uses ActiveRecord::$offset
	 	 *  @uses ActiveRecord::$page
	 	 *  @uses ActiveRecord::$new_record
	 	 *  @uses ActiveRecord::query()
	 	 *  @return object[] Array of objects of the same class as this
	 	 * 	object, one object for each row returned by the query.
	 	 * 	If the column 'id' was in the results, it is used as the key
	 	 * 	for that object in the array.
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		function find_all($conditions = null, $order = null, $limit = null, $joins = null) {
			$offset = null;
			$per_page = null;
			$select = null;
			$index_on = null;


			if(is_array($conditions)) {
				if(array_key_exists("per_page", $conditions) && !is_numeric($conditions['per_page'])) {
					extract($conditions);
					$per_page = 0;
				} else {
					extract($conditions);
				}

				if(is_array($conditions)) {
					$conditions = null;
				}
			}

			if(substr($conditions, 0, 6) == "SELECT") {
				$sql = $conditions;
			} else {

				if (isset($select)) {
					$sql  = "SELECT {$select} FROM ".$this->table_name." ";
				} else if($this->is_i18n) {
					$sql  = "SELECT {$this->table_name}.*, {$this->i18n_table}.* FROM ".$this->table_name." ";
				} else {
					$sql  = "SELECT {$this->table_name}.* FROM ".$this->table_name." ";
				}

				if(!is_null($joins)) {
					if(!in_array(strtolower(substr($joins, 0, 5)), array("left ", "right", "inner"))) $sql .= ",";
					$sql .= " $joins ";
				}

				if($this->is_i18n) {
					$sql .= "LEFT JOIN ".$this->i18n_table."
								ON {$this->table_name}.{$this->primary_keys[0]} = {$this->i18n_table}.{$this->i18n_foreign_key_field}
								AND {$this->i18n_table}.{$this->i18n_locale_field} = '" . $this->get_locale() . "' ";

					if(!$this->has_mirror) {
						if (!empty($conditions)) {
							$conditions .= " AND ";
						}
						$conditions .= $this->i18n_table . '.' . $this->i18n_locale_field . " IS NOT NULL";
					}
				}

				if(!empty($conditions)) {
					$sql .= "WHERE $conditions ";
				}

				if(!is_null($order)) {
					$sql .= "ORDER BY $order ";
				}

				if(!$this->is_find_first && (is_numeric($limit) || is_numeric($offset) || is_numeric($per_page))) {
					if(is_numeric($limit)) {
						$this->rows_per_page = $limit;
					}
					if(is_numeric($per_page)) {
						$this->rows_per_page = $per_page;
					}
					if ($this->rows_per_page <= 0) {
						$this->rows_per_page = $this->rows_per_page_default;
					}

					$this->page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 0;
					if($this->page <= 0) {
						$this->page = 1;
					}

					if(is_null($offset)) {
						$offset = ($this->page - 1) * $this->rows_per_page;
					}

					#$sql .= "LIMIT {$this->rows_per_page} OFFSET {$offset}";
					$sql = self::$db->apply_limit($sql,$offset, $limit);

					if($count = $this->count_all($this->primary_keys[0], $conditions, $joins)) {
						$this->pagination_count = $count;
						$this->pages = ceil( $count / $this->rows_per_page );
					}
				}

				if ($this->is_find_first) {
					$sql = self::$db->apply_limit($sql, $offset, $limit);
					#$sql .= "LIMIT {$limit}";
				}
			}

			$md5_sql = md5(get_class($this).serialize(func_get_args()));

			if (self::$cached[$md5_sql]) {
				$this->cache_ttl = false;
				return self::$cached[$md5_sql];
			}

			$rs = $this->query($sql);
			$this->cache_ttl = false;

			if(!$rs) {
				$this->raise('ActiveRecord: Query error!');
			}

			$objects = array();

			foreach ($rs as $row) {
				$class_name = $this->get_class_name();

				$object = new $class_name();
				if($object->is_i18n) {
					$object->set_locale($this->get_locale());
				}
				$object->new_record = false;
				foreach($row->fetch() as $field => $value) {


					$object->$field = $value;
					if (isset($index_on)) {
					}
					if($field == $this->index_on || $field === $index_on) {
						$objects_key = $value;
					}
				}

				$object->pagination_count = $this->pagination_count;
				$object->pages = $this->pages;

				if ($this->preserve_index || isset($index_on)) {
					$objects[$objects_key] = $object;
				}
				else {
					$objects[] = $object;
				}

				unset($object);
				unset($objects_key);
			}

			self::$cached[$md5_sql] = $objects;
			return $objects;
		}

	 	/**
	 	 *  Find row(s) with specified value(s)
	 	 *
	 	 *  Find all the rows in the table which match the argument $id.
	 	 *  Return zero or more objects of the same class as this
	 	 *  class representing the rows that matched the argument.
	 	 *  @param mixed[] $id  If $id is an array then a query will be
	 	 * 	generated selecting all of the array values in column "id".
	 	 * 	If $id is a string containing "=" then the string value of
	 	 * 	$id will be inserted in a WHERE clause in the query.  If $id
	 	 * 	is a scalar not containing "=" then a query will be generated
	 	 * 	selecting the first row WHERE id = '$id'.
	 	 * 	<b>NOTE</b> The column name "id" is used regardless of the
	 	 * 	value of {@link $primary_keys}.  Therefore if you need to
	 	 * 	select based on some column other than "id", you must pass a
	 	 * 	string argument ready to insert in the SQL SELECT.
	 	 *  @param string $order Argument to "ORDER BY" in query.
	 	 * 	If specified, the query will include "ORDER BY
	 	 * 	$order". If omitted, no ordering will be applied.
	 	 *  @param integer[] $limit Page, rows per page???
	 	 *  @param string $joins ???
	 	 *  @todo Document the $limit and $joins parameters
	 	 *  @uses ActiveRecord::find_all()
	 	 *  @uses ActiveRecord::find_first()
	 	 *  @return mixed Results of query.  If $id was a scalar then the
	 	 * 	result is an object of the same class as this class and
	 	 * 	matching $id conditions, or if no row matched the result is
	 	 * 	null.
	 	 *
	 	 * 	If $id was an array then the result is an array containing
	 	 * 	objects of the same class as this class and matching the
	 	 * 	conditions set by $id.  If no rows matched, the array is
	 	 * 	empty.
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		function find($id, $order = null, $limit = null, $joins = null) {
			$find_all = false;
			if(is_array($id)) {
				if(isset($id[0])) {
					$id = array_map(array(self::$db, 'escape'), $id);
					$primary_key = $this->primary_keys[0];
					$primary_key_values = is_numeric($id[0]) ? implode(",", $id) : "'".implode("','", $id)."'";
					$options['conditions'] = "{$this->table_name}.{$primary_key} IN({$primary_key_values})";
					$find_all = true;
				} else {
					$options = $id;
				}
			} elseif(strpos($id, "=") !== false) {
				$options['conditions'] = $id;
			} else {
				$primary_key = $this->primary_keys[0];
				$primary_key_value = is_numeric($id) ? $id : "'".self::$db->escape($id)."'";
				$options['conditions'] = "{$this->table_name}.{$primary_key} = {$primary_key_value}";
			}

			if(!is_null($order)) $options['order'] = $order;
			if(!is_null($limit)) $options['limit'] = $limit;
			if(!is_null($joins)) $options['joins'] = $joins;
			$options['offset'] = 0;

			if($find_all) {
				return $this->find_all($options);
			} else {
				return $this->find_first($options);
			}
		}

	 	/**
	 	 *  Return first row selected by $conditions
	 	 *
	 	 *  If no rows match, null is returned.
	 	 *  @param string $conditions SQL to use in the query.  If
	 	 * 	$conditions contains "SELECT", then $order, $limit and
	 	 * 	$joins are ignored and the query is completely specified by
	 	 * 	$conditions.  If $conditions is omitted or does not contain
	 	 * 	"SELECT", "SELECT * FROM" will be used.  If $conditions is
	 	 * 	specified and does not contain "SELECT", the query will
	 	 * 	include "WHERE $conditions".  If $conditions is null, the
	 	 * 	entire table is returned.
	 	 *  @param string $order Argument to "ORDER BY" in query.
	 	 * 	If specified, the query will include
	 	 * 	"ORDER BY $order". If omitted, no ordering will be
	 	 * 	applied.
	 	 *  FIXME This parameter doesn't seem to make sense
	 	 *  @param integer[] $limit Page, rows per page??? @todo Document this parameter
	 	 *  FIXME This parameter doesn't seem to make sense
	 	 *  @param string $joins ??? @todo Document this parameter
	 	 *  @uses ActiveRecord::find_all()
	 	 *  @return mixed An object of the same class as this class and
	 	 * 	matching $conditions, or null if none did.
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		function find_first($conditions, $order = null, $limit=1, $joins = null) {
			if(is_array($conditions)) {
				$options = $conditions;
			} else {
				$options['conditions'] = $conditions;
			}
			if(!is_null($order)) $options['order'] = $order;
			if(!is_null($limit)) $options['limit'] = $limit;
			if(!is_null($joins)) $options['joins'] = $joins;

			$this->is_find_first = true;
			$result = $this->find_all($options);
			$this->is_find_first = false;

			return @current($result);
		}

	 	/**
	 	 *  Return all the rows selected by the SQL argument
	 	 *
	 	 *  If no rows match, an empty array is returned.
	 	 *  @param string $sql SQL to use in the query.
	 	 */
		function find_by_sql($sql) {
			return $this->find_all($sql);
		}

		/**
		* Returns array useful for select box / Default attribute for names is 'title'
		*/
		public function find_all_for_smarty($conditions,$name = 'title'){
			$array_of_object = $this->find_all($conditions);
			foreach($array_of_object as $object) {
				$value = $object->{$name};
				if(isset($value) && !empty($value)) {
					$result[$object->id] = $value;
				}
			}
			return $result;
		}



	 	/**
	 	 *  Reloads the attributes of this object from the database.
	 	 *  @uses ActiveRecord::get_primary_key_conditions()
	 	 *  @todo Document this API
	 	 */
		function reload($conditions = null) {
			if(is_null($conditions)) {
				$conditions = $this->get_primary_key_conditions();
			}
			$object = $this->find($conditions);
			if(is_object($object)) {
				foreach($object as $key => $value) {
					$this->$key = $value;
				}
				return true;
			}
			return false;
		}

	 	/**
	 	 *  Loads into current object values from the database.
	 	 */
		function load($conditions = null) {
			return $this->reload($conditions);
		}

	 	/**
	 	 *  @todo Document this API.  What's going on here?  It appears to
	 	 * 	 	either create a row with all empty values, or it tries
	 	 * 	 	to recurse once for each attribute in $attributes.
	 	 *  FIXME: resolve calling sequence
	 	 *  Creates an object, instantly saves it as a record (if the validation permits it).
	 	 *  If the save fails under validations it returns false and $errors array gets set.
	 	 */
		function create($attributes, $dont_validate = false) {
			$first_key = array_keys($attributes);

			if(is_array($attributes[$first_key[0]])) {
				foreach($attributes as $attr) {
					$this->create($attr, $dont_validate);
				}
			} else {
				$class_name = $this->get_class_name();
				$object = new $class_name();
				$result = $object->save($attributes, $dont_validate);
				return ($result ? $object : false);
			}
		}

	 	/**
	 	 *  Finds the record from the passed id, instantly saves it with the passed attributes
	 	 *  (if the validation permits it). Returns true on success and false on error.
	 	 *  @todo Document this API
	 	 *  @return boolean
	 	 */
		function update($id, $attributes, $dont_validate = false) {
			if(is_array($id)) {
				foreach($id as $update_id) {
					$this->update($update_id, $attributes[$update_id], $dont_validate);
				}
			} else {
				$object = $this->find($id);
				return $object->save($attributes, $dont_validate);
			}
		}

	 	/**
	 	 *  Updates all records with the SET-part of an SQL update statement in updates and
	 	 *  returns an integer with the number of rows updates. A subset of the records can
	 	 *  be selected by specifying conditions.
	 	 *  Example:
	 	 * 	$model->update_all("category = 'cooldude', approved = 1", "author = 'John'");
	 	 *  @uses ActiveRecord::query()
	 	 *  @throws {@link ActiveRecordException}
	 	 *  @todo Document this API
	 	 *  @return boolean
	 	 */
		function update_all($updates, $conditions = null) {
			$sql = "UPDATE $this->table_name SET $updates WHERE $conditions";
			$result = $this->query($sql);
			if (!$result) {
				$this->raise('ActiveRecord: Cannot update record.');
			} else {
				return true;
			}
		}

	 	/**
	 	 *  Save without valdiating anything.
	 	 *  @todo Document this API
	 	 */
		function save_without_validation($attributes = null) {
			return $this->save($attributes, true);
		}

	 	/**
	 	 *  Create or update a row in the table with specified attributes
	 	 *
	 	 *  @param string[] $attributes List of name => value pairs giving
	 	 * 	name and value of attributes to set.
	 	 *  @param boolean $dont_validate true => Don't call validation
	 	 * 	routines before saving the row.  If false or omitted, all
	 	 * 	applicable validation routines are called.
	 	 *  @uses ActiveRecord::add_record_or_update_record()
	 	 *  @uses ActiveRecord::update_attributes()
	 	 *  @uses ActiveRecord::valid()
	 	 *  @return boolean
	 	 * 	 	  <ul>
	 	 * 	 	 	<li>true => row was updated or inserted successfully</li>
	 	 * 	 	 	<li>false => insert failed</li>
	 	 * 	 	  </ul>
	 	 */
		function save($attributes = null, $dont_validate = false) {

			$this->blob_fields = array();
			if(!is_null($attributes)) {
				$this->update_attributes($attributes);
			}

			if ($dont_validate || $this->valid()) {
				return $this->add_record_or_update_record();
			} else {
				if($this->auto_assign_errors && isset(Registry()->controller) && Registry()->controller instanceof ActionController) {
					Registry()->controller->errors = $this->get_errors();
				}
				return false;
			}
		}

	 	/**
	 	 *  Create or update a row in the table
	 	 *
	 	 *  If this object represents a new row in the table, insert it.
	 	 *  Otherwise, update the exiting row.  before_?() and after_?()
	 	 *  routines will be called depending on whether the row is new.
	 	 *  @uses ActiveRecord::add_record()
	 	 *  @uses ActiveRecord::after_create()
	 	 *  @uses ActiveRecord::after_update()
	 	 *  @uses ActiveRecord::before_create()
	 	 *  @uses ActiveRecord::before_save()
	 	 *  @uses ActiveRecord::$new_record
	 	 *  @uses ActiveRecord::update_record()
	 	 *  @return boolean
	 	 * 	 	  <ul>
	 	 * 	 	 	<li>true => row was updated or inserted successfully</li>
	 	 * 	 	 	<li>false => insert failed</li>
	 	 * 	 	  </ul>
	 	 */
		private function add_record_or_update_record() {
			$this->before_custom_save();
			$this->before_save();
			if($this->new_record) {
				$this->before_create();
				$result = $this->add_record();
				$this->after_create();
			} else {
				$this->before_update();
				$result = $this->update_record();
				$this->after_update();
			}
			$this->after_save();

			// init user custom cache
			if (is_array($this->cache)) {
				$this->rebuild_cache();
			}

			if($this->blob_fields) {
				$this->update_blob_fields();
			}
			return $result;
		}

		private function update_blob_fields() {
			foreach($this->blob_fields AS $column_name => $options) {
				list($table_name, $blob_type, $value, $i18n_values) = $options;
				if($i18n_values) {
					foreach($i18n_values AS $i18n_locale => $value) {
						$where = "{$this->i18n_foreign_key_field}={$this->id} AND {$this->i18n_locale_field}='{$i18n_locale}'";
						self::$db->UpdateBlob($table_name, $column_name, $value, $where, strtoupper($blob_type));
					}
				}
				else {
					$where = $this->get_primary_key_conditions();
					self::$db->UpdateBlob($table_name, $column_name, $value, $where, strtoupper($blob_type));
				}
			}
			$this->blob_fields = array();
		}

	 	/**
	 	 *  Insert a new row in the table associated with this object
	 	 *
	 	 *  Build an SQL INSERT statement getting the table name from
	 	 *  {@link $table_name}, the column names from {@link
	 	 *  $content_columns} and the values from object variables.
	 	 *  Send the insert to the RDBMS.
	 	 *  FIXME: Shouldn't we be saving the insert ID value as an object
	 	 *  variable $this->id?
	 	 *  @uses ActiveRecord::$auto_save_habtm
	 	 *  @uses ActiveRecord::add_habtm_records()
	 	 *  @uses ActiveRecord::before_create()
	 	 *  @uses ActiveRecord::get_insert_id()
	 	 *  @uses ActiveRecord::query()
	 	 *  @uses ActiveRecord::get_inserts()
	 	 *  @uses ActiveRecord::raise()
	 	 *  @uses ActiveRecord::$table_name
	 	 *  @return boolean
	 	 * 	 	  <ul>
	 	 * 	 	 	<li>true => row was inserted successfully</li>
	 	 * 	 	 	<li>false => insert failed</li>
	 	 * 	 	  </ul>
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		private function add_record() {
			$attributes = $this->get_inserts();

			$result = self::$db->insert($this->table_name, $attributes);
			if (!$result) {
				$this->raise('ActiveRecord: Error while adding record');
			} else {
				$this->id = $result;
				if($this->is_i18n)
					$this->i18n_add_record_data();
				if($this->id > 0) {
					if($this->auto_save_habtm) {
						$habtm_result = $this->add_habtm_records($this->id);
					}
					$this->save_associations();
				}
				return ($result && $habtm_result);
			}
		}

		private function i18n_add_record_data()
		{
			$inserts = $this->i18n_get_inserts();

			foreach($inserts as $locale => $attributes)
			{
				$attributes[$this->i18n_foreign_key_field] = $this->id;
				$attributes[$this->i18n_locale_field] = $locale;

				self::$db->insert($this->i18n_table, $attributes);
			}

			return true;
		}

		private function i18n_update_record_data() {
			$attributes = $this->i18n_get_inserts();

			foreach($attributes as $locale => $fields) {
				$conditions = array($this->i18n_foreign_key_field => $this->id, $this->i18n_locale_field => $locale);
				$result = self::$db->select($this->i18n_table, '*', $conditions);
				if(count($result)) {
					self::$db->update($this->i18n_table, $fields, $conditions);
				}
				else {
					self::$db->insert($this->i18n_table, array_merge($fields, $conditions));
				}
			}
			return true;
		}

		private function i18n_delete_record($conditions) {
			return self::$db->delete($this->i18n_table, "{$this->i18n_foreign_key_field} = '$conditions'");
		}

		public function i18n_create_view($force = false)
		{
			$view_suffix = '_view';
			$view_name = $this->i18n_table.$view_suffix;
			$locale = $this->get_locale();

			if(self::$db->table_exists($view_name))
			{
				if($force)
				{
					$qry = "DROP VIEW $view_name";
	   				$this->query($qry);
				}
				else {
					Messages::push(Messages::M_ERROR, "Cannot create view $view_name. View already exists.");
					return false;
				}
			}

			$qry  = "CREATE VIEW
						{$view_name}
						AS
						SELECT *
							FROM {$this->table_name}
							LEFT JOIN {$this->i18n_table}
								ON 	{$this->table_name}.id = {$this->i18n_table}.{$this->i18n_foreign_key_field}
								AND {$this->i18n_table}.{$this->i18n_locale_field} = '{$locale}'";

	   		$this->query($qry);
			return true;
		}

	 	/**
	 	 *  Update the row in the table described by this object
	 	 *
	 	 *  The primary key attributes must exist and have appropriate
	 	 *  non-null values.  If a column is listed in {@link
	 	 *  $content_columns} but no attribute of that name exists, the
	 	 *  column will be set to the null string ''.
	 	 *  @todo Describe habtm automatic update
	 	 *  @uses ActiveRecord::get_updates_sql()
	 	 *  @uses ActiveRecord::get_primary_key_conditions()
	 	 *  @uses ActiveRecord::query()
	 	 *  @uses ActiveRecord::raise()
	 	 *  @uses ActiveRecord::update_habtm_records()
	 	 *  @return boolean
	 	 * 	 	  <ul>
	 	 * 	 	 	<li>true => row was updated successfully</li>
	 	 * 	 	 	<li>false => update failed</li>
	 	 * 	 	  </ul>
	 	 *  @throws {@link ActiveRecordException}
	 	 */
		private function update_record() {
			$attributes = $this->get_updates();
			$conditions = $this->get_primary_key_conditions();

			$result = false;
			if ( !empty($attributes) ) {
				$result = self::$db->update($this->table_name, $attributes, $conditions);
			}

			if(!$result && $updates) {
				$this->raise('ActiveRecord: Error while updating record.');
			} else {
				$habtm_result = true;
				if($this->is_i18n) $result = $this->i18n_update_record_data();

				if($this->id > 0) {
					if($this->auto_save_habtm) {
						$habtm_result = $this->update_habtm_records($this->id);
					}
					$this->save_associations();
				}
				return ($result && $habtm_result);
			}
		}

	 	/**
	 	 *  returns the association type if defined in child class or null
	 	 *  @todo Document this API
	 	 *  @uses ActiveRecord::$belongs_to
	 	 *  @uses ActiveRecord::$has_and_belongs_to_many
	 	 *  @uses ActiveRecord::$has_many
	 	 *  @uses ActiveRecord::$has_one
	 	 *  @return mixed Association type, one of the following:
	 	 *  <ul>
	 	 * 	<li>"belongs_to"</li>
	 	 * 	<li>"has_and_belongs_to_many"</li>
	 	 * 	<li>"has_many"</li>
	 	 * 	<li>"has_one"</li>
	 	 *  </ul>
	 	 *  if an association exists, or null if no association
	 	 */
		function get_association_type($association_name) {
			$type = null;
			if(is_string($this->has_many)) {
				if(preg_match("/\b$association_name\b/", $this->has_many)) {
					$type = "has_many";
				}
			} elseif(is_array($this->has_many)) {
				if(array_key_exists($association_name, $this->has_many)) {
					$type = "has_many";
				}
			}
			if(is_string($this->has_one)) {
				if(preg_match("/\b$association_name/\b", $this->has_one)) {
					$type = "has_one";
				}
			} elseif(is_array($this->has_one)) {
				if(array_key_exists($association_name, $this->has_one)) {
					$type = "has_one";
				}
			}
			if(is_string($this->belongs_to)) {
				if(preg_match("/\b$association_name\b/", $this->belongs_to)) {
					$type = "belongs_to";
				}
			} elseif(is_array($this->belongs_to)) {
				if(array_key_exists($association_name, $this->belongs_to)) {
					$type = "belongs_to";
				}
			}
			if(is_string($this->has_and_belongs_to_many)) {
				if(preg_match("/\b$association_name\b/", $this->has_and_belongs_to_many)) {
					$type = "has_and_belongs_to_many";
				}
			} elseif(is_array($this->has_and_belongs_to_many)) {
				if(array_key_exists($association_name, $this->has_and_belongs_to_many)) {
					$type = "has_and_belongs_to_many";
				}
			}

			return $type;
		}

		private function save_associations() {

			if(count($this->save_associations) && $this->auto_save_associations) {
				foreach(array_keys($this->save_associations) as $type) {
					if(count($this->save_associations[$type])) {
						foreach($this->save_associations[$type] as $object_or_array) {
							if(is_object($object_or_array)) {
								$this->save_association($object_or_array, $type);
							} elseif(is_array($object_or_array)) {
								foreach($object_or_array as $object) {
									$this->save_association($object, $type);
								}
							}
						}
					}
				}
			}
		}

		private function is_child_of_self($object)
		{
			while($object) {
				if($object == __CLASS__) return true;
				$object = get_parent_class($object);
			}
			return false;
		}

		private function save_association($object, $type)
		{
		    if(is_object($object) && $this->is_child_of_self($object) && $type)
			{
				switch($type) {
					case "has_many":
					case "has_one":
						$foreign_key = Inflector::singularize($this->orig_table_name)."_id";
						$object->$foreign_key = $this->id;
						break;
				}
				$object->save();
			}
		}

	 	/**
	 	 *  Deletes the record with the given $id or if you have done a
	 	 *  $model = $model->find($id), then $model->delete() it will delete
	 	 *  the record it just loaded from the find() without passing anything
	 	 *  to delete(). If an array of ids is provided, all ids in array are deleted.
	 	 *  @uses ActiveRecord::$errors
	 	 *  @todo Document this API
	 	 */
		function delete($id = null) {
			if($this->id > 0 && is_null($id)) {
				$id = $this->id;
			}

			if(is_null($id)) {
				$this->errors[] = "No id specified to delete on.";
				return false;
			}

			$this->before_delete();
			$result = $this->delete_all("id IN ($id)");

			if($this->is_i18n) {
				$this->i18n_delete_record($id);
			}

			if($this->auto_delete_habtm) {
				if(is_string($this->has_and_belongs_to_many)) {
					$habtms = explode(",", $this->has_and_belongs_to_many);
					foreach($habtms as $other_table_name) {
						$this->delete_all_habtm_records(trim($other_table_name), $id);
					}
				} elseif(is_array($this->has_and_belongs_to_many)) {
					foreach($this->has_and_belongs_to_many as $other_table_name => $values) {
						$this->delete_all_habtm_records($other_table_name, $id);
					}
				}
			}
			$this->after_delete();

			if (is_array($this->cache)) {
				$this->rebuild_cache();
			}

			return $result;
		}

		function delete_all($conditions = null) {
			if(is_null($conditions)) {
				$this->errors[] = "No conditions specified to delete on.";
				return false;
			}

			if(!$this->query("DELETE FROM $this->table_name WHERE $conditions")) {
				$this->raise('ActiveRecord: Error while deleting record.');
			}

			$this->id = 0;
			$this->new_record = true;
			return true;
		}

		private function set_habtm_attributes($attributes) {
			if(is_array($attributes)) {
				$this->habtm_attributes = array();
				foreach($attributes as $key => $habtm_array) {
					if(is_array($habtm_array)) {
						if(is_string($this->has_and_belongs_to_many)) {
							if(preg_match("/$key/", $this->has_and_belongs_to_many)) {
								$this->habtm_attributes[$key] = $habtm_array;
							}
						} elseif(is_array($this->has_and_belongs_to_many)) {
							if(array_key_exists($key, $this->has_and_belongs_to_many)) {
								$this->habtm_attributes[$key] = $habtm_array;
							}
						}
					}
				}
			}
		}

		private function update_habtm_records($this_foreign_value) {
			return $this->add_habtm_records($this_foreign_value);
		}

		private function add_habtm_records($this_foreign_value) {
			if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
				if($this->delete_habtm_records($this_foreign_value)) {
					reset($this->habtm_attributes);
					foreach($this->habtm_attributes as $other_table_name => $other_foreign_values) {
						$table_name = $this->get_join_table_name($this->table_name, $other_table_name);
						$other_foreign_key = Inflector::singularize($other_table_name)."_id";
						$this_foreign_key = Inflector::singularize($this->orig_table_name)."_id";

						if(($habtm = $this->has_and_belongs_to_many[$other_table_name])
							&& is_array($habtm)
							&& array_key_exists('association_foreign_key', $habtm))
						{
							$other_foreign_key = $habtm['association_foreign_key'];
						}

						if(($habtm = $this->has_and_belongs_to_many[$other_table_name])
							&& is_array($habtm)
							&& array_key_exists('foreign_key', $habtm))
						{
							$this_foreign_key = $habtm['foreign_key'];
						}


						foreach($other_foreign_values as $other_foreign_value) {
							unset($attributes);
							$attributes[$this_foreign_key] = $this_foreign_value;
							$attributes[$other_foreign_key] = $other_foreign_value;
							$attributes = $this->quoted_attributes($attributes);
							$fields = @implode(', ', array_keys($attributes));
							$values = @implode(', ', array_values($attributes));
							$sql = "INSERT INTO $table_name ($fields) VALUES ($values)";
							$result = $this->query($sql);
							if (!$result) {
								//$this->raise('ActiveRecord: Error while adding records.');
							}
						}
					}
				}
			}
			return true;
		}

		private function delete_habtm_records($this_foreign_value) {
			if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
				reset($this->habtm_attributes);
				foreach($this->habtm_attributes as $other_table_name => $values) {
					$this->delete_all_habtm_records($other_table_name, $this_foreign_value);
				}
			}
			return true;
		}

		private function delete_all_habtm_records($other_table_name, $this_foreign_value) {

			if($other_table_name && $this_foreign_value > 0) {
				$habtm_table_name = $this->get_join_table_name($this->table_name,$other_table_name);
				$this_foreign_key = Inflector::singularize($this->orig_table_name)."_id";
				$sql = "DELETE FROM $habtm_table_name WHERE $this_foreign_key = $this_foreign_value";
				$result = $this->query($sql);
				if(!$result) {
					$this->raise('ActiveRecord: Error while deleting records.');
				}
			}
		}

	 	/**
	 	 *  Apply automatic timestamp updates
	 	 *
	 	 *  If automatic timestamps are in effect (as indicated by
	 	 *  {@link $auto_timestamps} == true) and the column named in the
	 	 *  $field argument is of type "timestamp" and matches one of the
	 	 *  names in {@link auto_create_timestamps} or {@link
	 	 *  auto_update_timestamps}(as selected by {@link $new_record}),
	 	 *  then return the current date and  time as a string formatted
	 	 *  to insert in the database.  Otherwise return $value.
	 	 *  @uses ActiveRecord::$new_record
	 	 *  @uses ActiveRecord::$content_columns
	 	 *  @uses ActiveRecord::$auto_timestamps
	 	 *  @uses ActiveRecord::$auto_create_timestamps
	 	 *  @uses ActiveRecord::$auto_update_timestamps
	 	 *  @param string $field Name of a column in the table
	 	 *  @param mixed $value Value to return if $field is not an
	 	 * 	 	 	 	 	  automatic timestamp column
	 	 *  @return mixed Current date and time or $value
	 	 */
		private function check_datetime($field, $value) {
			if($this->auto_timestamps) {
				if(is_array($value)) {
					list($value, $db_method) = $value;
				}
				$content_columns = self::$db->table_info($this->table_name);
				if(is_array($content_columns) && array_key_exists($field, $content_columns)) {
					$field_info = $content_columns[$field];
					if(stristr($field_info['type'], "date")) {
						$format = ($field_info['type'] == "date") ? $this->date_format : "{$this->date_format} {$this->time_format}";
						if($this->new_record) {
							if(in_array($field, $this->auto_create_timestamps)) {
								// nasty fix or just the proper way? must be checked
								$format = "{$this->date_format} {$this->time_format}";
								$timestamp = $db_method ? self::$db->$db_method(date($format)) : date($format);
								$this->{$field} = $timestamp;
								return $timestamp;
							} elseif($this->preserve_null_dates && !($value) && !$field_info['not_null']) {
								return 'NULL';
							} elseif ($value != 'NULL') {
								return $db_method ? self::$db->$db_method($value) : date($format, strtotime($value));
							}
						} elseif(!$this->new_record) {
							if(in_array($field, $this->auto_update_timestamps)) {
								// nasty fix or just the proper way? must be checked
								$timestamp = $db_method ? self::$db->$db_method(date($format)) : date($format);
								$this->{$field} = $timestamp;
								return $timestamp;
							} elseif($this->preserve_null_dates && is_null($value) && !$field_info['not_null']) {
								return 'NULL';
							} elseif ($value != 'NULL') {
								return $db_method ? self::$db->$db_method($value) : date($format, strtotime($value));
							}
						}
					}
				}
			}
			return $value;
		}

	 	/**
	 	 *  Apply automatic timestamp updates
	 	 *
	 	 *  If automatic timestamps are in effect (as indicated by
	 	 *  {@link $auto_timestamps} == true) and the column named in the
	 	 *  $field argument is of type "timestamp" and matches one of the
	 	 *  names in {@link auto_create_timestamps} or {@link
	 	 *  auto_update_timestamps}(as selected by {@link $new_record}),
	 	 *  then return the current date and  time as a string formatted
	 	 *  to insert in the database.  Otherwise return $value.
	 	 *  @param string $field Name of a column in the table
	 	 *  @param mixed $value Value to return if $field is not an
	 	 * 	 	 	 	 	  automatic timestamp column
	 	 *  @return mixed Current date and time or $value
	 	 */
		private function check_datetime_i18n($field, $value) {
			if($this->auto_timestamps) {
				if(is_array($value)) {
					list($value, $db_method) = $value;
				}
				$table_name_i18n = $this->table_name.$this->i18n_table_suffix;
				$i18n_columns = self::$db->table_info($table_name_i18n);
				if(is_array($i18n_columns) && array_key_exists($field, $i18n_columns)) {
					$field_info = $i18n_columns[$field];
					if(stristr($field_info['type'], "date")) {
						$format = ($field_info['type'] == "date") ? $this->date_format : "{$this->date_format} {$this->time_format}";
						if($this->new_record) {
							if(in_array($field, $this->auto_create_timestamps)) {
								// nasty fix or just the proper way? must be checked
								$timestamp = $db_method ? self::$db->$db_method(date($format)) : date($format);
								$this->{$field} = $timestamp;
								return $timestamp;
							} elseif($this->preserve_null_dates && (is_null($value) || !mb_strlen($value)) && !$field_info['not_null']) {
								return 'NULL';
							} elseif ($value) {
								return $db_method ? self::$db->$db_method($value) : date($format, strtotime($value));
							}
						} elseif(!$this->new_record) {
							if(in_array($field, $this->auto_update_timestamps)) {
								// nasty fix or just the proper way? must be checked
								$timestamp = $db_method ? self::$db->$db_method(date($format)) : date($format);
								$this->{$field} = $timestamp;
								return $timestamp;
							} elseif($this->preserve_null_dates && (is_null($value) || !mb_strlen($value)) && !$field_info['not_null']) {
								return 'NULL';
							} elseif ($value) {
								return $db_method ? self::$db->$db_method($value) : date($format, strtotime($value));
							}
						}
					}
				}
			}
			return $value;
		}

	 	/**
	 	 * Get the locale string from the application environment or
	 	 * the current instance of the ActiveRecord object
	 	 *
	 	 * @uses ActiveRecord::Settings
	 	 * @return string
	 	 */
		protected function get_locale() {
			if(!is_null($this->i18n_locale)) {
				return $this->i18n_locale;
			} elseif($locale = Registry()->locale) {
				return $this->set_locale($locale);
			} else {
				return $this->set_locale(Config()->DEFAULT_LOCALE);
			}
		}

	 	/**
	 	 * Sets the locale string for i18n tables
	 	 *
	 	 * @param string Locale string
	 	 */
		function set_locale($locale) {
			$this->i18n_locale = $locale;
			return $this->i18n_locale;
		}

	 	/**
	 	 *  Update object attributes from list in argument
	 	 *
	 	 *  The elements of $attributes are parsed and assigned to
	 	 *  attributes of the ActiveRecord object.  Date/time fields are
	 	 *  treated according to the
	 	 *  @param string[] $attributes List of name => value pairs giving
	 	 * 	name and value of attributes to set.
	 	 *  @uses ActiveRecord::$auto_save_associations
	 	 *  @todo Figure out and document how datetime fields work
	 	 */

		function update_attributes($attributes) {
			if(is_array($attributes)) {
		 	 	//  Test each attribute to be updated
		 	 	//  and process according to its type
				foreach($attributes as $field => $value) {
					# datetime / date parts check
					if(preg_match('/^\w+\(.*i\)$/i', $field)) {
						//  The name of this attribute ends in '(?i)'
						//  indicating that it's part of a date or time
						$datetime_field = substr($field, 0, strpos($field, '('));
						if(!in_array($datetime_field, $datetime_fields)) {
							$datetime_fields[] = $datetime_field;
						}
					} elseif(is_object($value) && get_parent_class($value) == __CLASS__ && $this->auto_save_associations) {
						# this elseif checks if first its an object if its parent is ActiveRecord
						if($association_type = $this->get_association_type($field)) {
							$this->save_associations[$association_type][] = $value;
							if($association_type == "belongs_to") {
								$foreign_key = Inflector::singularize($value->table_name)."_id";
								$this->$foreign_key = $value->id;
							}
						}
					} elseif(is_array($value) && $this->auto_save_associations) {
						# this elseif checks if its an array of objects and if its parent is ActiveRecord
						if($association_type = $this->get_association_type($field)) {
							$this->save_associations[$association_type][] = $value;
						} else {
							$this->$field = $value;
						}
					} else {
		 	 	 		//  Just a simple attribute, copy it
						$this->$field = $value;
					}
				}

				// If any date/time fields were found, assign the
				// accumulated values to corresponding attributes
				if(count($datetime_fields)) {
					foreach($datetime_fields as $datetime_field) {
						$datetime_format = '';
						$datetime_value = '';

						if($attributes[$datetime_field.'(1i)']
							&& $attributes[$datetime_field.'(2i)']
							&& $attributes[$datetime_field.'(3i)']) {
							$datetime_value = $attributes[$datetime_field.'(1i)']
							. '-' . $attributes[$datetime_field.'(2i)']
							. '-' . $attributes[$datetime_field.'(3i)'];
							$datetime_format = $this->date_format;
						}

						$datetime_value .= ' ';

						if($attributes[$datetime_field.'(4i)']
							&& $attributes[$datetime_field.'(5i)']) {
							$datetime_value .= $attributes[$datetime_field.'(4i)']
							. ':' . $attributes[$datetime_field.'(5i)'];
							$datetime_format .= ' '.$this->time_format;
						}

						if($datetime_value = trim($datetime_value)) {
							$datetime_value = date($datetime_format, strtotime($datetime_value));
							//error_log('($field) $datetime_field = $datetime_value');
							$this->$datetime_field = $datetime_value;
						}
					}
				}

				$this->set_habtm_attributes($attributes);
			}
		}

	 	/**
	 	 *  Return pairs of column-name:column-value
	 	 *
	 	 *  Return the contents of the object as an array of elements
	 	 *  where the key is the column name and the value is the column
	 	 *  value.  Relies on a previous call to
	 	 *  {@link set_content_columns()} for information about the format
	 	 *  of a row in the table.
	 	 *  @uses ActiveRecord::$content_columns
	 	 *  @see set_content_columns
	 	 *  @see quoted_attributes()
	 	 */
		function get_attributes() {
			$attributes = array();
			$content_columns = self::$db->table_info($this->table_name);
			if(is_array($content_columns)) {
				foreach($content_columns as $column) {
					if(isset($this->$column['name'])) {
						$db_method = Inflector::camelize('update_'.$column['type']);
						$attributes[$column['name']] = array($this->$column['name'], method_exists(self::$db, $db_method) ? $db_method : null);
						if(self::$has_update_blob && in_array($column['type'], self::$db->blob_column_types)) {
							$this->blob_fields[$column['name']] = array($this->table_name, $column['type'], null);
						}
					}
				}
			}

			return $attributes;
		}

	 	/**
	 	 *  Populates the $i18n_column_values array with
	 	 *  values from the database
	 	 *
	 	 *  @param string Locale string
	 	 */
		function i18n_get_attributes() {
			$attributes = array();

			$table_name_i18n = $this->table_name.$this->i18n_table_suffix;
			$i18n_columns = self::$db->table_info($table_name_i18n);

			if(is_array($i18n_columns)) {
				foreach($i18n_columns as $column) {
					if(isset($this->i18n_column_values[$column['name']]) && is_array($this->i18n_column_values[$column['name']])) {
						$db_method = Inflector::camelize('update_'.$column['type']);
						foreach($this->i18n_column_values[$column['name']] as $locale => $i18n_value) {
							$attributes[$locale][$column['name']] = array($i18n_value, method_exists(self::$db, $db_method) ? $db_method : null);
							if(self::$has_update_blob && in_array($column['type'], self::$db->blob_column_types)) {
								$this->blob_fields[$column['name']] = array($table_name_i18n, $column['type'], null, null);
							}
						}
					} else {
						continue;
					}
				}
			}

			return $attributes;
		}

	 	/**
	 	 *  Populates the $i18n_column_values array with
	 	 *  values from the database
	 	 *
	 	 *  @param string Locale string
	 	 */
		function i18n_get_values($locale = null) {
			$query  = "SELECT * FROM ".$this->i18n_table." WHERE {$this->i18n_foreign_key_field}='".$this->{$this->primary_keys[0]}."' " . (is_null($locale) ? '' : "AND {$this->i18n_locale_field} = '$locale' ");

			$result = $this->query($query);
			if(!$result) $this->raise('ActiveRecord: Query error!');

			foreach ($result as $row) {
				$locale = $row->locale;
				foreach($row->fetch() as $field => $value) {
					if(!in_array($field, $this->i18n_reserved_columns))
						$this->i18n_column_values[$field][$locale] = $value;
				}
			}
		}

	 	/**
	 	 *  Return pairs of column-name:quoted-column-value
	 	 *
	 	 *  Return pairs of column-name:quoted-column-value where the key
	 	 *  is the column name and the value is the column value with
	 	 *  automatic timestamp updating applied and characters special to
	 	 *  SQL quoted.
	 	 *
	 	 *  If $attributes is null or omitted, return all columns as
	 	 *  currently stored in {@link content_columns()}.  Otherwise,
	 	 *  return the name:value pairs in $attributes.
	 	 *  @param string[] $attributes Name:value pairs to return.
	 	 * 	If null or omitted, return the column names and values
	 	 * 	of the object as stored in $content_columns.
	 	 *  @return string[]
	 	 *  @uses ActiveRecord::get_attributes()
	 	 *  @see set_content_columns()
	 	 */
		function quoted_attributes($attributes = null) {
			if(is_null($attributes)) {
				$attributes = $this->get_attributes();
			}

			$return = array();
			foreach ($attributes as $key => $value) {
				$value = $this->check_datetime($key, $value);

				if(self::$has_update_blob && array_key_exists($key, $this->blob_fields)) {
			   		$this->blob_fields[$key][2] = $value;
			   		$return[$key] = strtoupper("empty_{$this->blob_fields[$key][1]}()");
			   		continue;
				}

//				if(!(preg_match('/^(NOW|MD5|CONCAT|RAND|COUNT)\(.*\)$/U', $value)) && !(strcasecmp($value, 'NULL') == 0) && (string)(float)$value !== (string)$value) {
//					$return[$key] = "'" . self::$db->escape($value) . "'";
//				} else {
//					$return[$key] = $value;
//				}
				$return[$key] = $value;
			}

			return $return;
		}

	 	/**
	 	 *  Internationalized version of quoted_attributes
	 	 *
	 	 *  Return pairs of column-name:quoted-column-value where the key
	 	 *  is the column name and the value is the column value with
	 	 *  automatic timestamp updating applied and characters special to
	 	 *  SQL quoted.
	 	 *
	 	 *  If $attributes is null or omitted, return all columns as
	 	 *  currently stored in {@link content_columns()}.  Otherwise,
	 	 *  return the name:value pairs in $attributes.
	 	 *  @param string[] $attributes Name:value pairs to return.
	 	 * 	If null or omitted, return the column names and values
	 	 * 	of the object as stored in $content_columns.
	 	 *  @return string[]
	 	 *  @uses ActiveRecord::get_attributes()
	 	 *  @see set_content_columns()
	 	 */
		function i18n_quoted_attributes($attributes = null) {
			if(is_null($attributes)) {
				$attributes = $this->i18n_get_attributes();
			}

			$return = array();
			foreach ($attributes as $locale => $fields) {
				foreach($fields as $key => $value) {
					$value = $this->check_datetime_i18n($key, $value);

					if(self::$has_update_blob && array_key_exists($key, $this->blob_fields)) {
	  					$this->blob_fields[$key][2] = null;
	  					$this->blob_fields[$key][3][$locale] = $value;
						$return[$locale][$key] = 'NULL';
						continue;
					}

//					if(!(preg_match('/^\w+\(.*\)$/U', $value)) && !(strcasecmp($value, 'NULL') == 0) && (string)(float)$value !== (string)$value) {
//						$return[$locale][$key] = self::$db->escape($value);
//					} else {
//						$return[$locale][$key] = $value;
//					}
 					$return[$locale][$key] = $value;
				}
			}
			return $return;
		}

	 	/**
	 	 *  Return column values for SQL insert statement
	 	 *
	 	 *  Return an array containing the column names and values of this
	 	 *  object, filtering out the primary keys, which are not set.
	 	 *
	 	 *  @uses ActiveRecord::$primary_keys
	 	 *  @uses ActiveRecord::quoted_attributes()
	 	 */
		function get_inserts() {
		  $attributes = $this->quoted_attributes();

		  $inserts = array();
		  foreach($attributes as $key => $value) {
			  if(!in_array($key, $this->primary_keys) || ($value != "''")) {
				  $inserts[$key] = $value;
			  }
		  }
		  return $inserts;
	  }



	 	/**
	 	 *  Internationalized version of get_inserts()
	 	 *
	 	 *  Return an array containing the column names and values of this
	 	 *  object, filtering out the primary keys, which are not set.
	 	 *
	 	 *  @uses ActiveRecord::$primary_keys
	 	 *  @uses ActiveRecord::i18n_quoted_attributes()
	 	 */
		function i18n_get_inserts() {
			$attributes = $this->i18n_quoted_attributes();

			$inserts = array();
			foreach($attributes as $locale => $fields) {
				foreach($fields as $key => $value) {
					$inserts[$locale][$key] = $value;
				}
			}
			return $inserts;
		}

	 	/**
	 	 *  Return argument for a "WHERE" clause specifying this row
	 	 *
	 	 *  Returns a string which specifies the column(s) and value(s)
	 	 *  which describe the primary key of this row of the associated
	 	 *  table.  The primary key must be one or more attributes of the
	 	 *  object and must be listed in {@link $content_columns} as
	 	 *  columns in the row.
	 	 *
	 	 *  Example: if $primary_keys = array("id", "ssn") and column "id"
	 	 *  has value "5" and column "ssn" has value "123-45-6789" then
	 	 *  the string "id = '5' AND ssn = '123-45-6789'" would be returned.
	 	 *  @uses ActiveRecord::$primary_keys
	 	 *  @uses ActiveRecord::quoted_attributes()
	 	 *  @return string Column name = 'value' [ AND name = 'value']...
	 	 */
		function get_primary_key_conditions() {
			$attributes = $this->quoted_attributes(array_intersect_key($this->get_attributes(), array_combine($this->primary_keys, $this->primary_keys)));

			$conditions = array();
			foreach($attributes as $key => $value) {
				$conditions[] = "$key = $value";
			}
			return empty($conditions) ? null : implode(" AND ", $conditions);
		}

	 	/**
	 	 *  Return column values of object formatted for SQL update statement
	 	 *
	 	 *  Return a string containing the column names and values of this
	 	 *  object in a format ready to be inserted in a SQL UPDATE
	 	 *  statement.  Automatic update has been applied to timestamps if
	 	 *  enabled and characters special to SQL have been quoted.
	 	 *  @uses ActiveRecord::quoted_attributes()
	 	 *  @return string Column name = 'value', ... for all attributes
	 	 */
		function get_updates() {
			return $this->quoted_attributes(array_diff_key($this->get_attributes(), array_combine($this->primary_keys, $this->primary_keys)));
		}

	 	/**
	 	 *  Set {@link $table_name} from the class name of this object
	 	 *
	 	 *  By convention, the name of the database table represented by
	 	 *  this object is derived from the name of the class.
	 	 *  @uses Inflector::tableize()
	 	 */
		function set_table_name_using_class_name() {
			if(!$this->table_name) {
				$class_name = $this->get_class_name();
				$this->orig_table_name = Inflector::tableize($class_name);
				$this->table_name = Config()->DB_PREFIX . $this->orig_table_name;
			}
		}

	 	/**
	 	 * Get class name of child object this will return the
	 	 * manually set name or get_class($this)
	 	 *
	 	 * @return string child class name
	 	 */
		private function get_class_name() {
			return !is_null($this->class_name) ? $this->class_name : get_class($this);
		}

	 	/**
	 	 *  Populate object with information about the table it represents
	 	 *
	 	 *  @param string $table_name  Name of table to get information about
	 	 */
		function set_content_columns($table_name) {
			$this->content_columns = $this->content_columns_all = self::$db->table_info($table_name);
			$table_name_i18n = $table_name.$this->i18n_table_suffix;

			if($this->is_i18n && self::$db->table_exists($table_name_i18n)) {
				$reserved_columns = $this->i18n_reserved_columns;
				$this->content_columns_i18n = $i18n_columns = self::$db->table_info($table_name_i18n);
				$this->content_columns_all = array_merge($this->content_columns, $i18n_columns);

				foreach($i18n_columns as $key => $col) {
					if(in_array($col['name'], $reserved_columns)) {
						unset($i18n_columns[$key]);
					} else {
						$this->i18n_column_names[] = $col['name'];
					}
				}
				$this->i18n_table = $table_name_i18n;
			} else {
				$this->is_i18n = false;
			}
		}

	 	/**
	 	 * Sets the current language preference
	 	 *
	 	 * @param string A valid locale
	 	 */
		function set_language($locale) {
			$this->i18n_locale = $locale;
		}

	 	/**
	 	 *  Returns the autogenerated id from the last insert query
	 	 *
	 	 *  @return int
	 	 */
		function get_insert_id() {
			return self::$db->last_inserted_id();
		}

	 	/**
	 	 *  Open a database connection if one is not currently open
	 	 *
	 	 * 	@return object $db
	 	 */
		function establish_connection() {
	   		self::$db = Registry()->db = SqlFactory::factory(Config()->DSN);
	   		self::$has_update_blob = method_exists(self::$db, 'UpdateBlob');
			return self::$db;
		}

	 	/**
	 	 *  Throw an exception describing an error in this object
	 	 *	@param string $message
	 	 */
		function raise($message) {
			$error_message  = "Model Class: ".$this->get_class_name()."<br />";
			$error_message .= "Error Message: ".$message;
			throw new ActiveRecordException($error_message, "500");
		}

		/**
	 	 *  Add or overwrite description of an error to the list of errors
	 	 *  @param string $error Error message text
	 	 *  @param string $key Key to associate with the error (in the
	 	 * 	simple case, column name).  If omitted, numeric keys will be
	 	 * 	assigned starting with 0.  If specified and the key already
	 	 * 	exists in $errors, the old error message will be overwritten
	 	 * 	with the value of $error.
	 	 *  @uses ActiveRecord::$errors
	 	 */
		function add_error($error, $key = null) {
			if(!is_null($key)) {
				$this->errors[$key] = $error;
			} else {
				$this->errors[] = $error;
			}
		}

	 	/**
	 	 *  Return description of non-fatal errors
	 	 *
	 	 *  @uses ActiveRecord::$errors
	 	 *  @param boolean $return_string
	 	 * 	<ul>
	 	 * 	  <li>true => Concatenate all error descriptions into a string
	 	 * 	 	using $seperator between elements and return the
	 	 * 	 	string</li>
	 	 * 	  <li>false => Return the error descriptions as an array</li>
	 	 * 	</ul>
	 	 *  @param string $seperator  String to concatenate between error
	 	 * 	descriptions if $return_string == true
	 	 *  @return mixed Error description(s), if any
	 	 */
		function get_errors($return_string = false, $seperator = "<br>") {
			if($return_string && count($this->errors)) {
				return implode($seperator, $this->errors);
			} else {
				return $this->errors;
			}
		}

	 	/**
	 	 *  Return errors as a string.
	 	 *
	 	 *  Concatenate all error descriptions into a stringusing
	 	 *  $seperator between elements and return the string.
	 	 *  @param string $seperator  String to concatenate between error
	 	 * 	descriptions
	 	 *  @return string Concatenated error description(s), if any
	 	 */
		function get_errors_as_string($seperator = "<br>") {
			return $this->get_errors(true, $seperator);
		}

	 	/**
	 	 *  Runs validation routines for update or create
	 	 *
	 	 *  @uses ActiveRecord::after_validation_on_create();
	 	 *  @uses ActiveRecord::after_validation_on_update();
	 	 *  @uses ActiveRecord::after_validation();
	 	 *  @uses ActiveRecord::before_validation_on_create();
	 	 *  @uses ActiveRecord::before_validation_on_update();
	 	 *  @uses ActiveRecord::before_validation();
	 	 *  @uses ActiveRecord::$errors
	 	 *  @uses ActiveRecord::$new_record
	 	 *  @uses ActiveRecord::validate();
	 	 *  @uses ActiveRecord::validate_model_attributes();
	 	 *  @uses ActiveRecord::validate_on_create();
	 	 *  @return boolean
	 	 * 	<ul>
	 	 * 	  <li>true => Valid, no errors found.
	 	 * 	 	{@link $errors} is empty</li>
	 	 * 	  <li>false => Not valid, errors in {@link $errors}</li>
	 	 * 	</ul>
	 	 */
		function valid() {
			$this->errors = array();

			if($this->new_record) {
				$this->before_validation();
				$this->before_validation_on_create();
				$this->validate_on_create();
				$this->validate();
				$this->validate_model_attributes();
				$this->after_validation_on_create();
				$this->after_validation();
			} else {
				$this->before_validation();
				$this->before_validation_on_update();
				$this->validate_on_update();
				$this->validate();
				$this->validate_model_attributes();
				$this->after_validation_on_update();
				$this->after_validation();
			}

			return count($this->errors) ? false : true;
		}

	 	/**
	 	 *  Call every method named "validate_*()" where * is a column name
	 	 *
	 	 *  Find and call every method named "validate_something()" where
	 	 *  "something" is the name of a column.  The "validate_something()"
	 	 *  functions are expected to return an array whose first element
	 	 *  is true or false (indicating whether or not the validation
	 	 *  succeeded), and whose second element is the error message to
	 	 *  display if the first element is false.
	 	 *
	 	 *  @return boolean
	 	 * 	<ul>
	 	 * 	  <li>true => Valid, no errors found.
	 	 * 	 	{@link $errors} is empty</li>
	 	 * 	  <li>false => Not valid, errors in {@link $errors}.
	 	 * 	 	$errors is an array whose keys are the names of columns,
	 	 * 	 	and the value of each key is the error message returned
	 	 * 	 	by the corresponding validate_*() method.</li>
	 	 * 	</ul>
	 	 *  @uses ActiveRecord::$errors
	 	 *  @uses ActiveRecord::get_attributes()
	 	 */
		final function validate_model_attributes() {
			$validated_ok = true;
			$attrs = $this->get_attributes();
			$methods = get_class_methods($this->get_class_name());
			foreach($methods as $method) {
				if(preg_match('/^validate_(.+)/', $method, $matches)) {
	 	 	 	 	# If we find, for example, a method named validate_name, then
	 	 	 	 	# we know that that function is validating the 'name' attribute
	 	 	 	 	# (as found in the (.+) part of the regular expression above).
					$validate_on_attribute = $matches[1];
	 	 	 	 	# Check to see if the string found (e.g. 'name') really is
	 	 	 	 	# in the list of attributes for this object...
					if(array_key_exists($validate_on_attribute, $attrs)) {
	 	 	 	 	 	# ...if so, then call the method to see if it validates to true...
						$result = $this->$method();
						if(is_array($result)) {
	 	 	 	 	 	 	# $result[0] is true if validation went ok, false otherwise
	 	 	 	 	 	 	# $result[1] is the error message if validation failed
							if($result[0] == false) {
	 	 	 	 	 	 	 	# ... and if not, then validation failed
								$validated_ok = false;
	 	 	 	 	 	 	 	# Mark the corresponding entry in the error array by
	 	 	 	 	 	 	 	# putting the error message in for the attribute,
	 	 	 	 	 	 	 	# e.g. $this->errors['name'] = "can't be empty"
	 	 	 	 	 	 	 	# when 'name' was an empty string.
								$this->errors[$validate_on_attribute] = $result[1];
							}
						}
					}
				}
			}
			return $validated_ok;
		}

	 	/**
	 	 *  Ðœethod for validation checks on all saves and
	 	 *  use $this->errors[] = "My error message."; or
	 	 *  for invalid attributes $this->errors['attribute'] = "Attribute is invalid.";
	 	 *  @todo Document this API
	 	 */
		final function validate() {
			foreach($this->content_columns_all as $field) {
				if ($field['primary_key']
					|| ($this->is_i18n && $this->content_columns_i18n && in_array($field['name'], $this->i18n_reserved_columns))
					|| ($this->auto_timestamps && (in_array($field['name'], $this->auto_create_timestamps) || in_array($field['name'], $this->auto_update_timestamps)))
				) {
					continue;
				}

				if ($field['unique']
					&& ($method = 'find_by_'.$field['name'])
					&& ($obj = $this->$method($this->$field['name']))
					&& ($obj instanceof ActiveRecord)
					&& $obj->{$obj->primary_keys[0]} != $this->{$obj->primary_keys[0]}
				) {
					$this->errors[Registry()->is_admin ? Inflector::humanize(Registry()->localizer->get_label('DB_FIELDS',$field['name'])) : $field['name']] = Inflector::humanize(Registry()->localizer->get_label('DB_SAVE_ERRORS', 'already_exists'));
				}
				else if($field['not_null'] && !mb_strlen(trim(preg_replace('/(&nbsp;|<br[^>]*>)/ixm', '', $this->$field['name'])))) {
					$this->errors[Registry()->is_admin ? Inflector::humanize(Registry()->localizer->get_label('DB_FIELDS',$field['name'])) : $field['name']] = Inflector::humanize(Registry()->localizer->get_label('DB_SAVE_ERRORS', 'not_empty'));
				}
				else if($field['max_length'] > 0 && mb_strlen($this->$field['name']) > $field['max_length']) {
					$this->errors[Registry()->is_admin ? Inflector::humanize(Registry()->localizer->get_label('DB_FIELDS',$field['name'])) : $field['name']] = Inflector::humanize(Registry()->localizer->get_label('DB_SAVE_ERRORS', 'too_long'));
				}
				else if(isset($_POST[$field['name']. '_confirm']) && $_POST[$field['name']] !== $_POST[$field['name']. '_confirm'] ) {
					$this->errors[Registry()->is_admin ? Inflector::humanize(Registry()->localizer->get_label('DB_FIELDS',$field['name'])) : $field['name']] = Inflector::humanize(Registry()->localizer->get_label('DB_SAVE_ERRORS', $field['name'] . '_not_match'));
				}
			}
		}

	 	/**
	 	 *  Override this method for validation checks used only on creation.
	 	 *  @todo Document this API
	 	 */
	 	function validate_on_create() {}

	 	/**
	 	 *  Override this method for validation checks used only on updates.
	 	 *  @todo Document this API
	 	 */
	 	function validate_on_update() {}

	 	/**
	 	 *  Is called before validate().
	 	 *  @todo Document this API
	 	 */
	 	function before_validation(){}

	 	/**
	 	 *  Is called after validate().
	 	 *  @todo Document this API
	 	 */
	 	function after_validation() {}

	 	/**
	 	 *  Is called before validate() on new objects that haven't been saved yet (no record exists).
	 	 *  @todo Document this API
	 	 */
	 	function before_validation_on_create() {}

	 	/**
	 	 *  Is called after validate() on new objects that haven't been saved yet (no record exists).
	 	 *  @todo Document this API
	 	 */
	 	function after_validation_on_create()  {}

	 	/**
	 	 *  Is called before validate() on existing objects that has a record.
	 	 *  @todo Document this API
	 	 */
	 	function before_validation_on_update() {}

	 	/**
	 	 *  Is called after validate() on existing objects that has a record.
	 	 *  @todo Document this API
	 	 */
	 	function after_validation_on_update()  {}

	 	/**
	 	 *  Is called before save() (regardless of whether its a create or update save)
	 	 *  @todo Document this API
	 	 */
		function before_save(){}

	 	/**
	 	 *  Is called before save() (regardless of whether its a create or update save)
	 	 *  @todo Document this API
	 	 */
		private function before_custom_save()
		{
			$fields = self::$db->table_info($this->table_name);
			foreach($fields AS $field)
			{
				if(!strlen($this->$field['name'])) {
					$this->$field['name'] = 'NULL';
				}
				if(method_exists($this, 'preSave_'.$field['name'])) {
					$this->{'preSave_'.$field['name']}($field);
				}
			}
		}

	 	/**
	 	 *  Is called after save (regardless of whether its a create or update save).
	 	 *  @todo Document this API
	 	 */
	 	function after_save() {}

	 	/**
	 	 *  Is called before save() on new objects that havent been saved yet (no record exists).
	 	 *  @todo Document this API
	 	 */
	 	function before_create() {}

	 	/**
	 	 *  Is called after save() on new objects that havent been saved yet (no record exists).
	 	 *  @todo Document this API
	 	 */
	 	function after_create() {}

	 	/**
	 	 *  Is called before save() on existing objects that has a record.
	 	 *  @todo Document this API
	 	 */
	 	function before_update() {}

	 	/**
	 	 *  Is called after save() on existing objects that has a record.
	 	 *  @todo Document this API
	 	 */
	 	function after_update() {}

	 	/**
	 	 *  Is called before delete().
	 	 *  @todo Document this API
	 	 */
	 	function before_delete() {}

	 	/**
	 	 *  Is called after delete().
	 	 *  @todo Document this API
	 	 */
	 	function after_delete() {}

	 	/**
	 	 * Return filter for SQL or for paginator
	 	 *
	 	 * @access public
	 	 * @param array $filter
	 	 * @param boolean $to_sql
	 	 * @return string
	 	 */
		public function get_filter(array $filter, $to_sql = true) {
			if( $to_sql === true) {
				return implode(" AND ", $this->prepare_filter($filter, $to_sql));
			}
			unset($filter['page']);
			return http_build_query($filter);
		}

		/**
		 * Used from get_filter. Parsed any element of the $filter.
		 *
		 * @access private
		 * @param array $filter
		 * @param boolean $to_sql
		 * @return array
		 */
		private function prepare_filter(array $filter=array(), $to_sql) {
			$filter_array = array();

			$fields = self::$db->table_info($this->table_name);

			$table_name_i18n = $this->table_name.$this->i18n_table_suffix;
			if($is_18n=($this->is_i18n && self::$db->table_exists($table_name_i18n))) {
				$fields_i18n = self::$db->table_info($table_name_i18n);
				$fields = array_merge($fields, $fields_i18n);
			}
			foreach($fields AS $field) {
				if(isset($filter[$field["name"]]) && !empty($filter[$field["name"]]) && $to_sql) {
					if($field["real_type"] == 'varchar') {
						$filter_array[] = "{$field["name"]} LIKE '".self::$db->escape($filter[$field["name"]])."'";
					}
					else {
						$filter_array[] = "{$field["name"]}='".self::$db->escape($filter[$field["name"]])."'";
					}
				}
			}

			if (!$to_sql) {
				foreach($_GET AS $key=>$val) {
					if ($key == 'order' || $key == 'page' || empty($val)) {
						continue;
					}
					$filter_array[] = "{$key}=".urlencode($val);
				}
			}
			return $filter_array;
		}

		/**
		 * Used for generation of order string in the ListHelper
		 *
		 * @return string $ret
		 */
		final function get_order() {
			$ret = null;
			if(isset($_GET['order'])) {
				$order = $_GET['order'];
				if(strlen($order) >= 6 && ($parts = preg_split('/\s+/', $order, 2))
				   && count($parts) == 2 && in_array(strtolower($parts[1]), array('asc', 'desc'))
				   && preg_match('/^[a-z_]+$/i', $parts[0]))
				{
					$ret = $order;
				}
			}

			if (is_null($ret)) {
				$_GET['order'] = null;
			}
			return $ret;
		}

	 	/**
	 	 *  Rebuilds static cache information. If called with specific name
	 	 *  rebuilds only that cache info.
	 	 *
	 	 * 	Syntax:
	 	 *
	 	 * 	protected $cache = array(
		 *		'<name of cache>' => array('fields' => array('<field 1>', ... , '<belongs_to_association>' => array('<field 1>', ... , '<field N>'), '<field N>'), 'conditions' => '...', 'order' => '...', 'limit' => '...')
		 *	);
		 *
	 	 *  @param string $cache_name = null
	 	 */
		final public function rebuild_cache($cache_name = null) {

			// if we want to rebuild specific kind of cache or all of it
			$rebild_cache = (!is_null($cache_name) && array_key_exists($cache_name, $this->cache)) ? array($cache_name => $this->cache[$cache_name]) : $this->cache;

			foreach ($rebild_cache as $name => $options) {
				if (!array_key_exists('fields', $options)) continue;

				$options = array_merge(array('conditions' => null, 'order' => null, 'limit' => null), $options);

				// find if the array of fields is multidimensional array - so we got to consider the the associations
				if (count($options['fields']) == count($options['fields'], 1)) {
					// no multidimensional array so no associations
					$associations = array();
					// keys to fetch are all 'fields' elements
					$keys = array_combine($options['fields'], $options['fields']);
				} else {
					// filter the associations out
					$associations = array_filter($options['fields'], 'is_array');
					// get only the fields which are no associations
					$keys = array_diff_key($options['fields'], $associations);
					// keys to fetch from main object
					$keys = array_combine($keys, $keys);
				}

				$store = array();
				// we loop through all locales
				foreach (Config()->LOCALE_SHORTCUTS as $lang) {
					$this->set_locale($lang);
					// preserve index for better cache structure
					$preserve_index = $this->preserve_index;
					$this->preserve_index = true;
					// find all elements according to conditions, order and limit
					$items = $this->find_all($options['conditions'], $options['order'], $options['limit']);
					// return preserve index to its original state
					$this->preserve_index = $preserve_index;

					foreach ($items as $id => $item) {
						// fetch the coresponding fields from each item
						$values = array();
						foreach($keys as $k) {
						  $values[$k] = $item->$k;
						}
						// store them according to i18n settings
						if ($this->is_i18n == true) {
							$store[$lang][$id] = $values;
						} else {
							$store[$id] = $values;
						}

						// loop through associations and fetch their data
						foreach ($associations as $association => $fields) {
							// fetch the coresponding fields from each association
							$values = array_intersect_key((array)($item->$association()), array_combine($fields, $fields));
							if (empty($values)) continue;
							// store them according to i18n settings
							if ($this->is_i18n == true) {
								$store[$lang][$id][$association] = $values;
							} else {
								$store[$id][$association] = $values;
							}
						}
					}
				}

				// write cache
				$cache_with_model = Inflector::tableize($this->get_class_name()) . '_' . $name;
				$cache_file = Config()->ROOT_PATH . 'cache/site/' . $cache_with_model . '.cache';
				$file = fopen($cache_file, "w");
				@flock($file, LOCK_EX);
				fwrite($file, "<?php\n\$this->cached_results['" . $cache_with_model . "'] = " . var_export($store, true) . ";\n?>");
				@flock($file, LOCK_UN);
				fclose($file);
				@chmod($cache_file, 0666);
			}

			// return back to original locale
			$this->set_locale(Registry()->locale);
		}

		/**
		 * Used to load static cache information
		 *
		 * @param string $cache_name
		 * @return array
		 */
		final protected function load_cache($cache_name) {
			$cache_with_model = Inflector::tableize($this->get_class_name()) . '_' . $cache_name;
			$cache_file = Config()->ROOT_PATH . 'cache/site/' . $cache_with_model . '.cache';

			if (is_file($cache_file)) {
				include($cache_file);
				return ($this->is_i18n) ? $this->cached_results[$cache_with_model][Registry()->locale] : $this->cached_results[$cache_with_model];
			}
			return null;
		}

		function __toString() {
			return $this->get_class_name() . '::' . $this->id;
		}
	}

	/**
	 * Function for getting the ActiveRecordSet object
	 *
	 * @param array $array
	 *
	 * @return ActiveRecordSet instance
	 */
	function ars(array $array) {
		require_once(Config()->CORE_PATH.'ActiveRecordSet.php');
		return new ActiveRecordSet($array);
	}

?>