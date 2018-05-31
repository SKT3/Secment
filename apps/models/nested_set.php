<?php

	class NestedSet extends ActiveRecord {

	    /**
	     * The name of the column which is used to store parent value
	     *
	     * @var string
	     */
		public $parent_ref_column_name = 'parent_id';

	    /**
	     * The name of the column which is used to store left tree value
	     *
	     * @var string
	     */
		public $left_column_name = 'lft';

	    /**
	     * The name of the column which is used to store right tree value
	     *
	     * @var string
	     */
		public $right_column_name = 'rght';

	    /**
	     * The name of the column which is used to store level tree value
	     *
	     * @var string
	     */
		public $level_column_name = 'lvl';

	    /**
	     * The name of the column which is used to store scope tree value
	     * You can limit the tree not to exceed this level
	     *
	     * @var int
	     */
		public $scope = '7';

		public function rearange_levels()
		{
			$parent = $this->find($this->parent_id);
			$this->update_all('lvl='.($parent->lvl + 1),'id='.$this->id);
			foreach($this->get_subtree() as $c) {
				$parent = $this->find($c->parent_id);
				$this->update_all('lvl='.($parent->lvl + 1),'id='.$c->id);
			}
		}

	 	/**
	 	 * Is called before validation of the record.
	 	 * Checks the parent and max scope and assigns proper left and right values.
	 	 * Must be called in the extending class if function is overridden
	 	 *
	 	 * @return void
	 	 */
		public function before_validation() {
			if ($this->get_parent_value() == 0) {
				$this->errors[Registry()->localizer->get('DB_FIELDS', 'parent_id')] = Registry()->localizer->get('DB_SAVE_ERRORS', 'not_empty');
				return false;
			}

			$parent = $this->get_parent();
			// set level
			$this->set_level_value($parent->get_level_value() + 1);

			if ($this->get_level_value() > $this->scope) {
				$this->errors[Registry()->localizer->get('DB_FIELDS', 'parent_id')] = Registry()->localizer->get_label('DB_SAVE_ERRORS',  'max_nodes') . $this->scope;
				return false;
			}
		}

	 	/**
	 	 * Is called before validation of the record on create.
	 	 * Must be called in the extending class if function is overridden
	 	 *
	 	 * @return void
	 	 */
		function before_validation_on_create() {
			$parent = $this->get_parent();

			// If the parent has no children insert node as only child
			if ($parent->get_right_value() - $parent->get_left_value() == 1) {
				$this->set_left_value($parent->get_left_value() + 1);
			}
			// else insert node as last child
			else {
				$this->set_left_value($parent->get_right_value());
			}

			$this->set_right_value($this->get_left_value() + 1);
		}

		function before_validation_on_update(){
			if($this->id==$this->parent_id) {
				$this->errors[Registry()->localizer->get('DB_FIELDS', 'parent_id')] = 'Can not assign to self.';
				return false;
			}

			$node = $this->find_by_id($this->parent_id);
			if($this->is_ancestor($node)) {
				$this->errors[Registry()->localizer->get('DB_FIELDS', 'parent_id')] = 'Can not assign to be parent because this is a descendant node.';
				return false;
			}
		}

	 	/**
	 	 * Is called before create
	 	 * Must be called in the extending class if function is overridden
	 	 *
	 	 * @return void
	 	 */
	 	public function before_create() {
	 		$parent = $this->get_parent();
			self::$db->query("UPDATE " . $this->table_name . " SET " . $this->left_column_name . " = " . $this->left_column_name . " + 2 WHERE " . $this->left_column_name . " >= " . $parent->get_right_value());
			self::$db->query("UPDATE " . $this->table_name . " SET " . $this->right_column_name . " = " . $this->right_column_name . " + 2 WHERE " . $this->right_column_name . " >= " . $parent->get_right_value());
	 	}

	 	public function before_update() {
	 		$node = $this->find_by_id($this->id);
	 		if ($node->parent_id != $this->parent_id) {
	 			$this->parent_changed = true;
	 		}
	 	}

	 	/**
	 	 * Is called before update
	 	 * Must be called in the extending class if function is overridden
	 	 *
	 	 * @return void
	 	 */
	 	public function after_update() {
	 		if ($this->parent_changed) {
	 			$this->append_to($this->get_parent());
	 			$this->parent_changed = false;
	 		}
	 	}

	 	/**
	 	 * Is called before delete()
	 	 * Must be called in the extending class if function is overridden
	 	 *
	 	 * @return void
	 	 */
		public function before_delete() {
			if ($this->lvl == 0) {
				return false;
			}
			self::$db->query("DELETE FROM " . $this->table_name . " WHERE " . $this->left_column_name . " BETWEEN " . $this->get_left_value() . " AND " . $this->get_right_value());
			self::$db->query("UPDATE " . $this->table_name . " SET " . $this->left_column_name . " = " . $this->left_column_name . " - " . ($this->get_right_value() - $this->get_left_value() + 1) . " WHERE " . $this->left_column_name . " > " . $this->get_right_value());
			self::$db->query("UPDATE " . $this->table_name . " SET " . $this->right_column_name . " = " . $this->right_column_name . " - " . ($this->get_right_value() - $this->get_left_value() + 1) . " WHERE " . $this->right_column_name . " > " . $this->get_right_value());
		}

		/**
		 * Returns the parent of the current node
		 *
		 * @return object ActiveRecord
		 */
		public function get_parent() {
			return $this->find_by_id($this->parent_id);
		}

	    /**
	     * Returns an array with all siblings of the current node. The current node is not included in this array.
	     *
	     * @return array
	     */
		public function get_siblings($sql = false) {
			$parent = $this->get_parent();
			return $this->find_all($this->left_column_name . ' > ' . $parent->{$this->left_column_name} . ' AND ' . $this->right_column_name . ' < ' . $parent->{$this->right_column_name} . $sql, $this->left_column_name .' ASC');
		}

		/**
		 * Returns an array with all parents of the current node up the tree
		 *
		 * @return array
		 */
		public function get_parents() {
			return $this->find_all($this->{$this->right_column_name} . ' BETWEEN ' . $this->left_column_name . ' AND ' . $this->right_column_name, $this->right_column_name . ' ASC');
		}

		/**
		 * Returns first parent of the current node up the tree with the provided level
		 *
		 * @return object
		 */

		public function get_parent_by_level($level = 0) {
			return $this->find_first($this->{$this->right_column_name} . ' BETWEEN ' . $this->left_column_name . ' AND ' . $this->right_column_name.' AND lvl = '.$level, $this->right_column_name . ' ASC');
		}

	    /**
	     * Returns the depth of the current node in the complete tree.
		 * The depth of the node, where the root node is always at depth 0.
		 *
	     * @return int
	     */
		public function get_depth() {
			return $this->level;
		}

		/**
		 * Change the order of the siblings
		 *
		 * @param array $ids
		 * @return true
		 */
		public function reorder_siblings($ids) {
			if (!count($ids)) return false;
			$parent = $this->find(current($ids))->get_parent();

			$brother = null;
			foreach ($ids as $key => $id) {
				$node = $this->find($id);
				if ($key == 0) {
					$node->prepend_to($parent);
				} else {
					$node->move_after($brother);
				}
				$node->save();
				$brother = $node;
			}
			return true;
		}

		/**
		 * Returns the tree in an flat array
		 *
		 * @param object NestedSet
		 * @return array
		 */
		public function get_tree($id = false) {
			if($id){
				$root = $this->find_by_id($id);
			}else{
				$root = $this->find_first('parent_id = 0');
			}
			return $this->find_all($this->left_column_name . ' BETWEEN ' . $root->get_left_value() . ' AND ' . $root->get_right_value(), $this->left_column_name . ' ASC');
		}

	    /**
	     * Returns the enire tree in a nested array
	     * Every "node" in this array is an array which has two key/value combinations:
	     * 'node': The actual node object
	     * 'children': A list of children of the node. Every child is again an array with these to key/value combinations.
	     *
	     * @param $returnrootnode - Whether the root node should be included in de result
	     * @return array
	     */
		public function get_nested_tree($returnrootnode = false, $id = false) {
	        // Fetch the flat tree
	        $rawtree = $this->get_tree($id);

	        // Init variables needed for the array conversion
	        $tree = array();
	        $node =& $tree;
	        $depth = 0;
	        $position = array();
	        $lastitem = '';

	        foreach($rawtree as $rawitem) {
	            // If its a deeper item, then make it subitems of the current item
	            if ($rawitem->get_level_value() > $depth) {
	                $position[] =& $node; //$lastitem;
	                $depth = $rawitem->get_level_value();
	                $node =& $node[$lastitem]['children'];
	            }
	            // If its less deep item, then return to a level up
	            else {
	                while ($rawitem->get_level_value() < $depth) {

	                    end($position);
	                    $node =& $position[key($position)];
	                    array_pop($position);
	                    $depth = $node[key($node)]['node']->get_level_value();
	                }
	            }

	            // Add the item to the final array
	            $node[$rawitem->id]['node'] = $rawitem;
	            // save the last items' name
	            $lastitem = $rawitem->id;
	        }

	        // we don't care about the root node
	        if (!$returnrootnode) {
	            reset($tree);
	            $tree = $tree[key($tree)]['children'];
	        }
	        return $tree;
		}

		/**
		 * Returns the subtree in an flat array
		 *
		 * @param void
		 * @return array
		 */
		public function get_subtree($sql = false) {
			return $this->find_all($this->right_column_name . ' BETWEEN ' . $this->get_left_value() . ' AND ' . $this->get_right_value() . $sql, $this->left_column_name . ' ASC');
		}

		public function get_subtree_active() {
			return $this->find_all($this->right_column_name . ' BETWEEN ' . $this->get_left_value() . ' AND ' . $this->get_right_value().' AND visibility=2', $this->left_column_name . ' ASC');
		}

		public function get_subtree_megamenu() {
			return $this->find_all($this->right_column_name . ' BETWEEN ' . $this->get_left_value() . ' AND ' . $this->get_right_value().' AND in_megamenu=1', $this->left_column_name . ' ASC');
		}
		/**
		 * Get the depth of node subtree
		 *
		 * @param void
		 * @return int
		 */
		public function get_subtree_depth() {
			return $this->max_all('lvl', $this->right_column_name . ' BETWEEN ' . $this->get_left_value() . ' AND ' . $this->get_right_value()) - $this->lvl;
		}

	    /**
		 * Returns false when the node is a leaf node (i.e. has no child nodes)
		 * True when the node has childe nodes, false when the node is a leaf.
		 * @return boolean
		 */
	    public function has_children() {
	        return $this->get_left_value() != ($this->get_right_value() - 1);
	    }

	    /**
	    * Returns the number of all nested children of this object.
	    *
	    * @return int
	    */
	    public function count_children() {
	    	return ($this->get_right_value() - $this->get_left_value() - 1) / 2;
	    }

		/**
		 * Find the Immediate Subordinates of a Node
		 * Returns the childnodes of this node in an array.
		 * This function does not return the children of the child nodes (i.e. grandchildren)
		 *
		 * @return array
		 */
		public function get_children($sql = false) {
			return $this->find_all($this->right_column_name . ' BETWEEN ' . $this->get_left_value() . ' AND ' . $this->get_right_value() . ' AND parent_id = ' . $this->id . $sql, $this->left_column_name . ' ASC');
		}

	    /**
	     * Returns the next sibling (i.e. the sibling on the right of this node)
	     *
	     * @return ActiveRecord object or false
	     */
		public function get_next_sibling() {
			return $this->find_first($this->left_column_name . ' = ' . ($this->get_right_value() + 1));
		}

	    /**
	     * Returns the previous sibling (i.e. the sibling on the left of this node)
	     *
	     * @return ActiveRecord object or false
	     */
		public function get_previous_sibling() {
			return $this->find_first($this->right_column_name . ' = ' . ($this->get_left_value() - 1));
		}

	    /**
	     * Determines if node is ancestor of subject node
	     *
	     * @return bool
	     */
	    public function is_ancestor($node) {
	    	return ($node->get_right_value() > $this->get_left_value() && $node->get_right_value() < $this->get_right_value());
	    }

	    /**
	     * Determines if node is child of subject node
	     *
	     * @return bool
	     */
	    public function is_descendant($node) {
	        return (($this->get_left_value() > $node->get_left_value()) && ($this->get_right_value() < $node->get_right_value()));
	    }

	    /**
	     * Move this node to a position before the given node. (i.e. this node becomes the left sibling of the given node)
	     *
	     * @param NestedSet $node
	     * @return boolean
	     */
	    public function move_before($node) {
	        return $this->move($node, 'left');
	    }

	    /**
	     * Move this node to a position after the given node. (i.e. this node becomes the right sibling of the given node)
	     *
	     * @param  $node
	     * @return boolean
	     */
	    public function move_after($node) {
	        return $this->move($node, 'right');
	    }

	    /**
	     * Move the node as last child of another node
	     */
	    public function append_to($node) {
	    	// No children
	        if ($node->count_children() == 0) {
	        	return $this->move($node, 'child');
	        } else {
	        	$children = $node->get_children();
	        	$sibling = end($children);
	        	return $this->move($sibling, 'right');
	        }
	    }

	    /**
	     * Move the node as first child of another node
	     */
	    public function prepend_to($node) {
	    	return $this->move($node, 'child');
	    }

		private function move($target, $position) {
		    if($this->is_new_record()){
		        $this->raise('You cannot move a new node');
		    }
		    $current_left = $this->get_left_value();
		    $current_right = $this->get_right_value();
		    // $extent is the width of the tree self and children
		    $extent = $current_right - $current_left + 1;

		    $target_left = $target->get_left_value();
		    $target_right = $target->get_right_value();

		    // detect impossible move
		    if ($this == $node || $this->is_ancestor($target)){
		        $this->raise('Cannot move node as first child of itself or into a descendant');
		    }

		    // compute new left/right for self
		    if ($position == 'child'){
		        if ($target_left < $current_left){
		            $new_left  = $target_left + 1;
		            $new_right = $target_left + $extent;
		        }else{
		            $new_left  = $target_left - $extent + 1;
		            $new_right = $target_left;
		        }
		    }elseif($position == 'left'){
		        if ($target_left < $current_left){
		            $new_left  = $target_left;
		            $new_right = $target_left + $extent - 1;
		        }else{
		            $new_left  = $target_left - $extent;
		            $new_right = $target_left - 1;
		        }
		    }elseif($position == 'right'){
		        if ($target_right < $current_right){
		            $new_left  = $target_right + 1;
		            $new_right = $target_right + $extent;
		        }else{
	                $new_left  = $target_right - $extent + 1;
	                $new_right = $target_right;
		        }
		    }else{
		        $this->raise("Position should be either child, left or right ('" . $position . "' received).");
		    }

		    // boundaries of update action
		    $left_boundary = min($current_left, $new_left);
		    $right_boundary = max($current_right, $new_right);

		    // Shift value to move self to new $position
		    $shift = $new_left - $current_left;

		    // Shift value to move nodes inside boundaries but not under self_and_children
		    $updown = ($shift > 0) ? -$extent : $extent;

		    // change null to NULL for new parent
		    if($position == 'child'){
		        $new_parent = $target->{$this->primary_keys[0]};
		        $level_diff = $target->get_level_value() - $this->get_level_value() + 1;
		    }else{
		        $target_parent = $target->parent_ref_column_name;
		        $new_parent = empty($target_parent) ? 'NULL' : $target_parent;
		        $level_diff = 0;
		    }

		    $this->update_all(
			    $this->left_column_name.' = CASE '.
			    'WHEN '.$this->left_column_name.' BETWEEN '.$current_left.' AND '.$current_right.' '.
			    'THEN '.$this->left_column_name.' + '.$shift.' '.
			    'WHEN '.$this->left_column_name.' BETWEEN '.$left_boundary.' AND '.$right_boundary.' '.
			    'THEN '.$this->left_column_name.' + '.$updown.' '.
			    'ELSE '.$this->left_column_name.' END, '.

			    $this->right_column_name.' = CASE '.
			    'WHEN '.$this->right_column_name.' BETWEEN '.$current_left.' AND '.$current_right.' '.
			    'THEN '.$this->right_column_name.' + '.$shift.' '.
			    'WHEN '.$this->right_column_name.' BETWEEN '.$left_boundary.' AND '.$right_boundary.' '.
			    'THEN '.$this->right_column_name.' + '.$updown.' '.
			    'ELSE '.$this->right_column_name.' END, '.

				$this->level_column_name.' = CASE '.
			    'WHEN '.$this->left_column_name.' BETWEEN '.$current_left.' AND '.$current_right.' '.
			    'THEN '.$this->level_column_name.' + '.$level_diff.' '.
			    'ELSE '.$this->level_column_name.' END, '.

			    $this->parent_ref_column_name.' = CASE '.
			    'WHEN '.$this->primary_keys[0].' = '. $this->{$this->primary_keys[0]}.' '.
			    'THEN '.$new_parent.' '.
			    'ELSE '.$this->parent_ref_column_name.' END ',
			    self::$db->whereall()
			);

		    $this->rearange_levels();

		    return true;
		}

		/**
		 *  Set the value in the "lft" column for this node.
		 *  Setting wrong values can destroy your hierarchical tree and may be difficult to recover.
		 *  The Left/Right/Id/ParentId/Level values are automatically set when you use the special tree manipulation functions in this class.
		 *
		 * @param $value
		 * @return void
		 */
		public function set_left_value($value) {
			$this->{$this->left_column_name} = $value;
		}

		/**
		 *  Set the value in the "rght" column for this node.
		 *  Setting wrong values can destroy your hierarchical tree and may be difficult to recover.
		 *  The Left/Right/Id/ParentId/Level values are automatically set when you use the special tree manipulation functions in this class.
		 *
		 * @param $value
		 * @return void
		 */
		public function set_right_value($value) {
			$this->{$this->right_column_name} = $value;
		}

		/**
		 *  Set the value in the "parent_id" column for this node.
		 *  Setting wrong values can destroy your hierarchical tree and may be difficult to recover.
		 *  The Left/Right/Id/ParentId/Level values are automatically set when you use the special tree manipulation functions in this class.
		 *
		 * @param $value
		 * @return void
		 */
		public function set_parent_value($value) {
			$this->{$this->parent_ref_column_name} = $value;
		}

		/**
		 *  Set the value in the "level" column for this node.
		 *  Setting wrong values can destroy your hierarchical tree and may be difficult to recover.
		 *  The Left/Right/Id/ParentId/Level values are automatically set when you use the special tree manipulation functions in this class.
		 *
		 * @param $value
		 * @return void
		 */
		public function set_level_value($value) {
			$this->{$this->level_column_name} = $value;
		}

	    /**
	     *  Get the value stored in the "lft" column for this node.
	     *
	     *  @return int
	     */
		public function get_left_value() {
			return $this->{$this->left_column_name};
		}

	    /**
	     *  Get the value stored in the "rght" column for this node.
	     *
	     *  @return int
	     */
		public function get_right_value() {
			return $this->{$this->right_column_name};
		}

	    /**
	     *  Get the value stored in the "parent_id" column for this node.
	     *
	     *  @return int
	     */
		public function get_parent_value() {
			return $this->{$this->parent_ref_column_name};
		}

	    /**
	     *  Get the value stored in the "level" column for this node.
	     *
	     *  @return int
	     */
		public function get_level_value() {
			return $this->{$this->level_column_name};
		}

	}

?>