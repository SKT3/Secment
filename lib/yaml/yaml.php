<?php
/**
 *
 * This package is heavily inspired by the Spyc PHP YAML
 *
 */

/**
 * YAML parser.
 *
 * This class can be used to read a YAML file and convert its contents
 * into a PHP array. The native PHP parser supports a limited
 * subsection of the YAML spec, but if the syck extension is present,
 * that will be used for parsing.
 *
 */
class Yaml
{
	/**
     * Callback used for alternate YAML loader, typically exported
     * by a faster PHP extension.  This function's first argument
     * must accept a string with YAML content.
     *
     * @var callback
     */
    public static $loadfunc = 'syck_load';

    /**
     * Callback used for alternate YAML dumper, typically exported
     * by a faster PHP extension.  This function's first argument
     * must accept a mixed variable to be dumped.
     *
     * @var callback
     */
    public static $dumpfunc = 'syck_dump';

    /**
     * Whitelist of classes that can be instantiated automatically
     * when loading YAML docs that include serialized PHP objects.
     *
     * @var array
     */
    public static $allowedClasses = array('ArrayObject');

    /**
     * Load a string containing YAML and parse it into a PHP array.
     * Returns an empty array on failure.
     *
     * @param  string  $yaml   String containing YAML
     * @return array           PHP array representation of YAML content
     */
    public static function load($yaml)
    {
        if (!is_string($yaml) || !strlen($yaml)) {
            $msg = 'YAML to parse must be a string and cannot be empty.';
            throw new InvalidArgumentException($msg);
        }

        if (is_callable(self::$loadfunc)) {
            return call_user_func(self::$loadfunc, $yaml);
            return is_array($array) ? $array : array();
        }

        if (strpos($yaml, "\r") !== false) {
            $yaml = str_replace(array("\r\n", "\r"), array("\n", "\n"), $yaml);
        }
        $lines = explode("\n", $yaml);
        $loader = new YamlLoader;

        while (list(,$line) = each($lines)) {
            $loader->parse($line);
        }

        return $loader->toArray();
    }

    /**
     * Load a file containing YAML and parse it into a PHP array.
     *
     * If the file cannot be opened, an exception is thrown.  If the
     * file is read but parsing fails, an empty array is returned.
     *
     * @param  string  $filename     Filename to load
     * @return array                 PHP array representation of YAML content
     * @throws IllegalArgumentException  If $filename is invalid
     * @throws YamlException  If the file cannot be opened.
     */
    public static function loadFile($filename)
    {
        if (!is_string($filename) || !strlen($filename)) {
            $msg = 'Filename must be a string and cannot be empty';
            throw new InvalidArgumentException($msg);
        }

        $stream = @fopen($filename, 'rb');
        if (!$stream) {
            throw new FileException('Failed to open file: ', error_get_last());
        }

        return self::loadStream($stream);
    }

    /**
     * Load YAML from a PHP stream resource.
     *
     * @param  resource  $stream     PHP stream resource
     * @return array                 PHP array representation of YAML content
     */
    public static function loadStream($stream)
    {
        if (! is_resource($stream) || get_resource_type($stream) != 'stream') {
            throw new InvalidArgumentException('Stream must be a stream resource');
        }

        if (is_callable(self::$loadfunc)) {
            return call_user_func(self::$loadfunc, stream_get_contents($stream));
        }

        $loader = new YamlLoader;
        while (!feof($stream)) {
            $loader->parse(stream_get_line($stream, 100000, "\n"));
        }

        return $loader->toArray();
    }

    /**
     * Dump a PHP array to YAML.
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.
     *
     * @param  array|Traversable  $array     PHP array or traversable object
     * @param  integer            $options   Options to pass to dumper
     * @return string                        YAML representation of $value
     */
    public static function dump($value, $options = array())
    {
        if (is_callable(self::$dumpfunc)) {
            return call_user_func(self::$dumpfunc, $value);
        }

        $dumper = new YamlDumper();
        return $dumper->dump($value, $options);
    }

}

/**
 * A node, used for parsing YAML.
 *
 */
class YamlNode
{
    /**
     * @var string
     */
    public $parent;

    /**
     */
    public $id;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var integer
     */
    public $indent;

    /**
     * @var bool
     */
    public $children = false;

    /**
     * The constructor assigns the node a unique ID.
     * @return void
     */
    public function __construct($nodeId)
    {
        $this->id = $nodeId;
    }

}

/**
 * Parse YAML strings into PHP data structures
 *
 */
class YamlLoader
{
    /**
     * List of nodes with references
     * @var array
     */
    protected $_haveRefs = array();

    /**
     * All nodes
     * @var array
     */
    protected $_allNodes = array();

    /**
     * Array of node parents
     * @var array
     */
    protected $_allParent = array();

    /**
     * Last indent level
     * @var integer
     */
    protected $_lastIndent = 0;

    /**
     * Last node id
     * @var integer
     */
    protected $_lastNode = null;

    /**
     * Is the parser inside a block?
     * @var boolean
     */
    protected $_inBlock = false;

    /**
     * @var boolean
     */
    protected $_isInline = false;

    /**
     * Next node id to use
     * @var integer
     */
    protected $_nodeId = 1;

    /**
     * Last line number parsed.
     * @var integer
     */
    protected $_lineNumber = 0;

    /**
     * Create a new YAML parser.
     */
    public function __construct()
    {
        $base = new YamlNode($this->_nodeId++);
        $base->indent = 0;
        $this->_lastNode = $base->id;
    }

    /**
     * Return the PHP built from all YAML parsed so far.
     *
     * @return array PHP version of parsed YAML
     */
    public function toArray()
    {
        // Here we travel through node-space and pick out references
        // (& and *).
        $this->_linkReferences();

        // Build the PHP array out of node-space.
        return $this->_buildArray();
    }

    /**
     * Parse a line of a YAML file.
     *
     * @param  string           $line  The line of YAML to parse.
     * @return YamlNode         YAML Node
     */
    public function parse($line)
    {
        // Keep track of how many lines we've parsed for friendlier
        // error messages.
        ++$this->_lineNumber;

        $trimmed = trim($line);

        // If the line starts with a tab (instead of a space), throw a fit.
        if (preg_match('/^ *(\t) *[^\t ]/', $line)) {
            $msg = "Line {$this->_lineNumber} indent contains a tab.  "
                 . 'YAML only allows spaces for indentation.';
            throw new SwebooException($msg);
        }

        if (!$this->_inBlock && empty($trimmed)) {
            return;
        } elseif ($this->_inBlock && empty($trimmed)) {
            $last =& $this->_allNodes[$this->_lastNode];
            $last->data[key($last->data)] .= "\n";
        } elseif ($trimmed[0] != '#' && substr($trimmed, 0, 3) != '---') {
            // Create a new node and get its indent
            $node = new YamlNode($this->_nodeId++);
            $node->indent = $this->_getIndent($line);

            // Check where the node lies in the hierarchy
            if ($this->_lastIndent == $node->indent) {
                // If we're in a block, add the text to the parent's data
                if ($this->_inBlock) {
                    $parent =& $this->_allNodes[$this->_lastNode];
                    $parent->data[key($parent->data)] .= trim($line) . $this->_blockEnd;
                } else {
                    // The current node's parent is the same as the previous node's
                    if (isset($this->_allNodes[$this->_lastNode])) {
                        $node->parent = $this->_allNodes[$this->_lastNode]->parent;
                    }
                }
            } elseif ($this->_lastIndent < $node->indent) {
                if ($this->_inBlock) {
                    $parent =& $this->_allNodes[$this->_lastNode];
                    $parent->data[key($parent->data)] .= trim($line) . $this->_blockEnd;
                } elseif (!$this->_inBlock) {
                    // The current node's parent is the previous node
                    $node->parent = $this->_lastNode;

                    // If the value of the last node's data was > or |
                    // we need to start blocking i.e. taking in all
                    // lines as a text value until we drop our indent.
                    $parent =& $this->_allNodes[$node->parent];
                    $this->_allNodes[$node->parent]->children = true;
                    if (is_array($parent->data)) {
                        if (isset($parent->data[key($parent->data)])) {
                            $chk = $parent->data[key($parent->data)];
                            if ($chk === '>') {
                                $this->_inBlock = true;
                                $this->_blockEnd = '';
                                $parent->data[key($parent->data)] =
                                    str_replace('>', '', $parent->data[key($parent->data)]);
                                $parent->data[key($parent->data)] .= trim($line) . ' ';
                                $this->_allNodes[$node->parent]->children = false;
                                $this->_lastIndent = $node->indent;
                            } elseif ($chk === '|') {
                                $this->_inBlock = true;
                                $this->_blockEnd = "\n";
                                $parent->data[key($parent->data)] =
                                    str_replace('|', '', $parent->data[key($parent->data)]);
                                $parent->data[key($parent->data)] .= trim($line) . "\n";
                                $this->_allNodes[$node->parent]->children = false;
                                $this->_lastIndent = $node->indent;
                            }
                        }
                    }
                }
            } elseif ($this->_lastIndent > $node->indent) {
                // Any block we had going is dead now
                if ($this->_inBlock) {
                    $this->_inBlock = false;
                    if ($this->_blockEnd == "\n") {
                        $last =& $this->_allNodes[$this->_lastNode];
                        $last->data[key($last->data)] =
                            trim($last->data[key($last->data)]);
                    }
                }

                // We don't know the parent of the node so we have to
                // find it
                foreach ($this->_indentSort[$node->indent] as $n) {
                    if ($n->indent == $node->indent) {
                        $node->parent = $n->parent;
                    }
                }
            }

            if (!$this->_inBlock) {
                // Set these properties with information from our
                // current node
                $this->_lastIndent = $node->indent;

                // Set the last node
                $this->_lastNode = $node->id;

                // Parse the YAML line and return its data
                $node->data = $this->_parseLine($line);

                // Add the node to the master list
                $this->_allNodes[$node->id] = $node;

                // Add a reference to the parent list
                $this->_allParent[intval($node->parent)][] = $node->id;

                // Add a reference to the node in an indent array
                $this->_indentSort[$node->indent][] =& $this->_allNodes[$node->id];

                // Add a reference to the node in a References array
                // if this node has a YAML reference in it.
                $is_array = is_array($node->data);
                $key = key($node->data);
                $isset = isset($node->data[$key]);
                if ($isset) {
                    $nodeval = $node->data[$key];
                }
                if (($is_array && $isset && !is_array($nodeval) && !is_object($nodeval))
                    && (strlen($nodeval) && ($nodeval[0] == '&' || $nodeval[0] == '*') && $nodeval[1] != ' ')) {
                    $this->_haveRefs[] =& $this->_allNodes[$node->id];
                } elseif ($is_array && $isset && is_array($nodeval)) {
                    // Incomplete reference making code. Needs to be
                    // cleaned up.
                    foreach ($node->data[$key] as $d) {
                        if (!is_array($d) && strlen($d) && (($d[0] == '&' || $d[0] == '*') && $d[1] != ' ')) {
                            $this->_haveRefs[] =& $this->_allNodes[$node->id];
                        }
                    }
                }
            }
        }
    }

    /**
     * Finds and returns the indentation of a YAML line
     *
     * @param  string  $line  A line from the YAML file
     * @return int            Indentation level
     */
    protected function _getIndent($line)
    {
        if (preg_match('/^\s+/', $line, $match)) {
            return strlen($match[0]);
        } else {
            return 0;
        }
    }

    /**
     * Parses YAML code and returns an array for a node
     *
     * @param  string  $line  A line from the YAML file
     * @return array
     */
    protected function _parseLine($line)
    {
        $array = array();

        $line = trim($line);
        if (preg_match('/^-(.*):$/', $line)) {
            // It's a mapped sequence
            $key = trim(substr(substr($line, 1), 0, -1));
            $array[$key] = '';
        } elseif ($line[0] == '-' && substr($line, 0, 3) != '---') {
            // It's a list item but not a new stream
            if (strlen($line) > 1) {
                // Set the type of the value. Int, string, etc
                $array[] = $this->_toType(trim(substr($line, 1)));
            } else {
                $array[] = array();
            }
        } elseif (preg_match('/^(.+):/', $line, $key)) {
            // It's a key/value pair most likely
            // If the key is in double quotes pull it out
            if (preg_match('/^(["\'](.*)["\'](\s)*:)/', $line, $matches)) {
                $value = trim(str_replace($matches[1], '', $line));
                $key = $matches[2];
            } else {
                // Do some guesswork as to the key and the value
                $explode = explode(':', $line);
                $key = trim(array_shift($explode));
                $value = trim(implode(':', $explode));
            }

            // Set the type of the value. Int, string, etc
            $value = $this->_toType($value);
            if (empty($key)) {
                $array[] = $value;
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Finds the type of the passed value, returns the value as the new type.
     *
     * @param  string   $value
     * @return mixed
     */
    protected function _toType($value)
    {
        // Check for PHP specials
        self::_unserialize($value);
        if (!is_scalar($value)) {
            return $value;
        }

        // Used in a lot of cases.
        $lower_value = strtolower($value);

        if (preg_match('/^("(.*)"|\'(.*)\')/', $value, $matches)) {
            $value = (string)str_replace(array('\'\'', '\\\''), "'", end($matches));
            $value = str_replace('\\"', '"', $value);
        } elseif (preg_match('/^\\[(\s*)\\]$/', $value)) {
            // empty inline mapping
            $value = array();
        } elseif (preg_match('/^\\[(.+)\\]$/', $value, $matches)) {
            // Inline Sequence

            // Take out strings sequences and mappings
            $explode = $this->_inlineEscape($matches[1]);

            // Propogate value array
            $value  = array();
            foreach ($explode as $v) {
                $value[] = $this->_toType($v);
            }
        } elseif (preg_match('/^\\{(\s*)\\}$/', $value)) {
            // empty inline mapping
            $value = array();
        } elseif (strpos($value, ': ') !== false && !preg_match('/^{(.+)/', $value)) {
            // inline mapping
            $array = explode(': ', $value);
            $key = trim($array[0]);
            array_shift($array);
            $value = trim(implode(': ', $array));
            $value = $this->_toType($value);
            $value = array($key => $value);
        } elseif (preg_match("/{(.+)}$/", $value, $matches)) {
            // Inline Mapping

            // Take out strings sequences and mappings
            $explode = $this->_inlineEscape($matches[1]);

            // Propogate value array
            $array = array();
            foreach ($explode as $v) {
                $array = $array + $this->_toType($v);
            }
            $value = $array;
        } elseif ($lower_value == 'null' || $value == '' || $value == '~') {
            $value = null;
        } elseif ($lower_value == '.nan') {
            $value = NAN;
        } elseif ($lower_value == '.inf') {
            $value = INF;
        } elseif ($lower_value == '-.inf') {
            $value = -INF;
        } elseif (ctype_digit($value)) {
            $value = (int)$value;
        } elseif (in_array($lower_value,
                           array('true', 'on', '+', 'yes', 'y'))) {
            $value = true;
        } elseif (in_array($lower_value,
                           array('false', 'off', '-', 'no', 'n'))) {
            $value = false;
        } elseif (is_numeric($value)) {
            $value = (float)$value;
        } else {
            // Just a normal string, right?
            if (($pos = strpos($value, '#')) !== false) {
                $value = substr($value, 0, $pos);
            }
            $value = trim($value);
        }

        return $value;
    }

    /**
     * Handle PHP serialized data.
     *
     * @param string &$data Data to check for serialized PHP types.
     */
    protected function _unserialize(&$data)
    {
        if (substr($data, 0, 5) != '!php/') {
            return;
        }

        $first_space = strpos($data, ' ');
        $type = substr($data, 5, $first_space - 5);
        $class = null;
        if (strpos($type, '::') !== false) {
            list($type, $class) = explode('::', $type);

            if (!in_array($class, Yaml::$allowedClasses)) {
                throw new ClassProblems("$class is not in the list of allowed classes");
            }
        }

        switch ($type) {
        case 'object':
            if (!class_exists($class)) {
                throw new ClassProblems("$class is not defined");
            }

            $reflector = new ReflectionClass($class);
            if (!$reflector->implementsInterface('Serializable')) {
                throw new ClassProblems("$class does not implement Serializable");
            }

            $class_data = substr($data, $first_space + 1);
            $serialized = 'C:' . strlen($class) . ':"' . $class . '":' . strlen($class_data) . ':{' . $class_data . '}';
            $data = unserialize($serialized);
            break;

        case 'array':
        case 'hash':
            $array_data = substr($data, $first_space + 1);
            $array_data = Yaml::load('a: ' . $array_data);

            if (is_null($class)) {
                $data = $array_data['a'];
            } else {
                if (!class_exists($class)) {
                    throw new ClassProblems("$class is not defined");
                }

                $array = new $class;
                if (!$array instanceof ArrayAccess) {
                    throw new ClassProblems("$class does not implement ArrayAccess");
                }

                foreach ($array_data['a'] as $key => $val) {
                    $array[$key] = $val;
                }

                $data = $array;
            }
            break;
        }
    }

    /**
     * Used in inlines to check for more inlines or quoted strings
     *
     * @todo  There should be a cleaner way to do this.  While
     *        pure sequences seem to be nesting just fine,
     *        pure mappings and mappings with sequences inside
     *        can't go very deep.  This needs to be fixed.
     *
     * @param  string  $inline  Inline data
     * @return array
     */
    protected function _inlineEscape($inline)
    {
        $saved_strings = array();

        // Check for strings
        $regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
        if (preg_match_all($regex, $inline, $strings)) {
            $saved_strings = $strings[0];
            $inline = preg_replace($regex, 'YAMLString', $inline);
        }

        // Check for sequences
        if (preg_match_all('/\[(.+)\]/U', $inline, $seqs)) {
            $inline = preg_replace('/\[(.+)\]/U', 'YAMLSeq', $inline);
            $seqs = $seqs[0];
        }

        // Check for mappings
        if (preg_match_all('/{(.+)}/U', $inline, $maps)) {
            $inline = preg_replace('/{(.+)}/U', 'YAMLMap', $inline);
            $maps = $maps[0];
        }

        $explode = explode(', ', $inline);

        // Re-add the sequences
        if (!empty($seqs)) {
            $i = 0;
            foreach ($explode as $key => $value) {
                if (strpos($value, 'YAMLSeq') !== false) {
                    $explode[$key] = str_replace('YAMLSeq', $seqs[$i], $value);
                    ++$i;
                }
            }
        }

        // Re-add the mappings
        if (!empty($maps)) {
            $i = 0;
            foreach ($explode as $key => $value) {
                if (strpos($value, 'YAMLMap') !== false) {
                    $explode[$key] = str_replace('YAMLMap', $maps[$i], $value);
                    ++$i;
                }
            }
        }

        // Re-add the strings
        if (!empty($saved_strings)) {
            $i = 0;
            foreach ($explode as $key => $value) {
                while (strpos($value, 'YAMLString') !== false) {
                    $explode[$key] = preg_replace('/YAMLString/', $saved_strings[$i], $value, 1);
                    ++$i;
                    $value = $explode[$key];
                }
            }
        }

        return $explode;
    }

    /**
     * Builds the PHP array from all the YAML nodes we've gathered
     *
     * @return array
     */
    protected function _buildArray()
    {
        $trunk = array();
        if (!isset($this->_indentSort[0])) {
            return $trunk;
        }

        foreach ($this->_indentSort[0] as $n) {
            if (empty($n->parent)) {
                $this->_nodeArrayizeData($n);

                // Check for references and copy the needed data to complete them.
                $this->_makeReferences($n);

                // Merge our data with the big array we're building
                $trunk = $this->_array_kmerge($trunk, $n->data);
            }
        }

        return $trunk;
    }

    /**
     * Traverses node-space and sets references (& and *) accordingly
     *
     * @return bool
     */
    protected function _linkReferences()
    {
        if (is_array($this->_haveRefs)) {
            foreach ($this->_haveRefs as $node) {
                if (!empty($node->data)) {
                    $key = key($node->data);
                    // If it's an array, don't check.
                    if (is_array($node->data[$key])) {
                        foreach ($node->data[$key] as $k => $v) {
                            $this->_linkRef($node, $key, $k, $v);
                        }
                    } else {
                        $this->_linkRef($node, $key);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Helper for _linkReferences()
     *
     * @param  YamlNode  $n   Node
     * @param  string           $k   Key
     * @param  mixed            $v   Value
     * @return void
     */
    function _linkRef(&$n, $key, $k = null, $v = null)
    {
        if (empty($k) && empty($v)) {
            // Look for &refs
            if (preg_match('/^&([^ ]+)/', $n->data[$key], $matches)) {
                // Flag the node so we know it's a reference
                $this->_allNodes[$n->id]->ref = substr($matches[0], 1);
                $this->_allNodes[$n->id]->data[$key] =
                    substr($n->data[$key], strlen($matches[0]) + 1);
                // Look for *refs
            } elseif (preg_match('/^\*([^ ]+)/', $n->data[$key], $matches)) {
                $ref = substr($matches[0], 1);
                // Flag the node as having a reference
                $this->_allNodes[$n->id]->refKey = $ref;
            }
        } elseif (!empty($k) && !empty($v)) {
            if (preg_match('/^&([^ ]+)/', $v, $matches)) {
                // Flag the node so we know it's a reference
                $this->_allNodes[$n->id]->ref = substr($matches[0], 1);
                $this->_allNodes[$n->id]->data[$key][$k] =
                    substr($v, strlen($matches[0]) + 1);
                // Look for *refs
            } elseif (preg_match('/^\*([^ ]+)/', $v, $matches)) {
                $ref = substr($matches[0], 1);
                // Flag the node as having a reference
                $this->_allNodes[$n->id]->refKey = $ref;
            }
        }
    }

    /**
     * Finds the children of a node and aids in the building of the PHP array
     *
     * @param  int    $nid   The id of the node whose children we're gathering
     * @return array
     */
    protected function _gatherChildren($nid)
    {
        $return = array();
        $node =& $this->_allNodes[$nid];
        if (is_array ($this->_allParent[$node->id])) {
            foreach ($this->_allParent[$node->id] as $nodeZ) {
                $z =& $this->_allNodes[$nodeZ];
                // We found a child
                $this->_nodeArrayizeData($z);

                // Check for references
                $this->_makeReferences($z);

                // Merge with the big array we're returning, the big
                // array being all the data of the children of our
                // parent node
                $return = $this->_array_kmerge($return, $z->data);
            }
        }
        return $return;
    }

    /**
     * Turns a node's data and its children's data into a PHP array
     *
     * @param  array    $node  The node which you want to arrayize
     * @return boolean
     */
    protected function _nodeArrayizeData(&$node)
    {
        if ($node->children == true) {
            if (is_array($node->data)) {
                // This node has children, so we need to find them
                $children = $this->_gatherChildren($node->id);

                // We've gathered all our children's data and are ready to use it
                $key = key($node->data);
                $key = empty($key) ? 0 : $key;
                // If it's an array, add to it of course
                if (isset($node->data[$key])) {
                    if (is_array($node->data[$key])) {
                        $node->data[$key] = $this->_array_kmerge($node->data[$key], $children);
                    } else {
                        $node->data[$key] = $children;
                    }
                } else {
                    $node->data[$key] = $children;
                }
            } else {
                // Same as above, find the children of this node
                $children = $this->_gatherChildren($node->id);
                $node->data = array();
                $node->data[] = $children;
            }
        } else {
            // The node is a single string. See if we need to unserialize it.
            if (is_array($node->data)) {
                $key = key($node->data);
                $key = empty($key) ? 0 : $key;

                if (!isset($node->data[$key]) || is_array($node->data[$key]) || is_object($node->data[$key])) {
                    return true;
                }

                self::_unserialize($node->data[$key]);
            } elseif (is_string($node->data)) {
                self::_unserialize($node->data);
            }
        }

        // We edited $node by reference, so just return true
        return true;
    }

    /**
     * Traverses node-space and copies references to / from this object.
     *
     * @param  YamlNode  $z  A node whose references we wish to make real
     * @return bool
     */
    protected function _makeReferences(&$z)
    {
        // It is a reference
        if (isset($z->ref)) {
            $key = key($z->data);
            // Copy the data to this object for easy retrieval later
            $this->ref[$z->ref] =& $z->data[$key];
            // It has a reference
        } elseif (isset($z->refKey)) {
            if (isset($this->ref[$z->refKey])) {
                $key = key($z->data);
                // Copy the data from this object to make the node a real reference
                $z->data[$key] =& $this->ref[$z->refKey];
            }
        }

        return true;
    }

    /**
     * Merges two arrays, maintaining numeric keys. If two numeric
     * keys clash, the second one will be appended to the resulting
     * array. If string keys clash, the last one wins.
     *
     * @param  array  $arr1
     * @param  array  $arr2
     * @return array
     */
    protected function _array_kmerge($arr1, $arr2)
    {
        while (list($key, $val) = each($arr2)) {
            if (isset($arr1[$key]) && is_int($key)) {
                $arr1[] = $val;
            } else {
                $arr1[$key] = $val;
            }
        }

        return $arr1;
    }

}

/**
 * Dump PHP data structures to YAML.
 *
 */
class YamlDumper
{
    protected $_options = array();

    /**
     * Dump PHP array to YAML
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into valid YAML.
     *
     * Options:
     *    `indent`:
     *       number of spaces to indent children (default 2)
     *    `wordwrap`:
     *       wordwrap column number (default 40)
     *
     * @param  array|Traversable  $array     PHP array or traversable object
     * @param  integer            $options   Options for dumping
     * @return string                        YAML representation of $value
     */
    public function dump($value, $options = array())
    {
        // validate & merge default options
        if (!is_array($options)) {
            throw new InvalidArgumentException('Options must be an array');
        }

        $defaults = array('indent'   => 2,
                          'wordwrap' => 500);
        $this->_options = array_merge($defaults, $options);

        if (! is_int($this->_options['indent'])) {
            throw new InvalidArgumentException('Indent must be an integer');
        }

        if (! is_int($this->_options['wordwrap'])) {
            throw new InvalidArgumentException('Wordwrap column must be an integer');
        }

        // new YAML document
        $dump = "---\n";

        // iterate through array and yamlize it
        foreach ($value as $key => $value) {
            $dump .= $this->_yamlize($key, $value, 0);
        }
        return $dump;
    }

    /**
     * Attempts to convert a key / value array item to YAML
     *
     * @param  string        $key     The name of the key
     * @param  string|array  $value   The value of the item
     * @param  integer       $indent  The indent of the current node
     * @return string
     */
    protected function _yamlize($key, $value, $indent)
    {
        if ($value instanceof Serializable) {
            // Dump serializable objects as !php/object::classname serialize_data
            $data = '!php/object::' . get_class($value) . ' ' . $value->serialize();
            $string = $this->_dumpNode($key, $data, $indent);
        } elseif (is_array($value) || $value instanceof Traversable) {
            // It has children.  Make it the right kind of item.
            $string = $this->_dumpNode($key, null, $indent);

            // Add the indent.
            $indent += $this->_options['indent'];

            // Yamlize the array.
            $string .= $this->_yamlizeArray($value, $indent);
        } elseif (!is_array($value)) {
            // No children.
            $string = $this->_dumpNode($key, $value, $indent);
        }

        return $string;
    }

    /**
     * Attempts to convert an array to YAML
     *
     * @param  array    $array The array you want to convert
     * @param  integer  $indent The indent of the current level
     * @return string
     */
    protected function _yamlizeArray($array, $indent)
    {
        if (!is_array($array)) {
            return false;
        }

        $string = '';
        foreach ($array as $key => $value) {
            $string .= $this->_yamlize($key, $value, $indent);
        }
        return $string;
    }

    /**
     * Returns YAML from a key and a value
     *
     * @param  string   $key     The name of the key
     * @param  string   $value   The value of the item
     * @param  integer  $indent  The indent of the current node
     * @return string
     */
    protected function _dumpNode($key, $value, $indent)
    {
        // Do some folding here, for blocks.
        if (strpos($value, "\n") !== false
            || strpos($value, ': ') !== false
            || strpos($value, '- ') !== false) {
            $value = $this->_doLiteralBlock($value, $indent);
        } else {
            $value = $this->_fold($value, $indent);
        }

        if (is_bool($value)) {
            $value = ($value) ? 'true' : 'false';
        } elseif (is_float($value)) {
            if (is_nan($value)) {
                $value = '.NAN';
            } elseif ($value === INF) {
                $value = '.INF';
            } elseif ($value === -INF) {
                $value = '-.INF';
            }
        }

        $spaces = str_repeat(' ', $indent);

        if (is_int($key)) {
            // It's a sequence.
            $string = $spaces . $key . ': ' . $value . "\n";
        } else {
            // It's mapped.
            $string = $spaces . $key . ': ' . $value . "\n";
        }

        return $string;
    }

    /**
     * Creates a literal block for dumping
     *
     * @param  string   $value
     * @param  integer  $indent  The value of the indent.
     * @return string
     */
    protected function _doLiteralBlock($value, $indent)
    {
        $exploded = explode("\n", $value);
        $newValue = '|';
        $indent += $this->_options['indent'];
        $spaces = str_repeat(' ', $indent);
        foreach ($exploded as $line) {
            $newValue .= "\n" . $spaces . trim($line);
        }
        return $newValue;
    }

    /**
     * Folds a string of text, if necessary
     *
     * @param   $value   The string you wish to fold
     * @return  string
     */
    protected function _fold($value, $indent)
    {
        // Don't do anything if wordwrap is set to 0
        if (! $this->_options['wordwrap']) {
            return $value;
        }

        if (strlen($value) > $this->_options['wordwrap']) {
            $indent += $this->_options['indent'];
            $indent = str_repeat(' ', $indent);
            $wrapped = wordwrap($value, $this->_options['wordwrap'], "\n$indent");
            $value = ">\n" . $indent . $wrapped;
        }

        return $value;
    }

}

?>