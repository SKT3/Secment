<?php

class ActiveRecordSet extends ArrayObject {
	function toXml() {
		$object = current($this);
		$root_tag_name = Inflector::tableize(get_class($object));
		$xml[] = "<$root_tag_name>";
		foreach ($this as $object) {
			$xml[] = $object->toXml();
		}
		$xml[] = "</$root_tag_name>";
		return implode(chr(13), $xml);
	}

	function toJson() {
		$array = array();

		foreach ($this as $object) {
			$array[] = $object->toJson(false);
		}

		return json_encode($array);
	}

	function pluck($column) {
		$array = array();

		foreach ($this as $object) {
			$array[] = is_object($object) ? $object->$column : $object[$column];
		}

		return $array;
	}

	function toSmartySelect($key='id', $title='title', $first = false) {
		$array = array();

		if ($first) {
			$array[''] = $first;
		}

		foreach ($this as $object) {
			$array[is_object($object) ? $object->$key : $object[$key]] = is_object($object) ? $object->$title : $object[$title];
		}

		return $array;
	}

	function chunk($key) {
		$array = array();

		foreach ($this as $object) {
			$array[is_object($object) ? $object->$key : $object[$key]][] = $object;
		}

		return $array;
	}

	function myIntersect($objects, $merged_key, $key) {
		$array = array();

		if (is_string($merged_key)) {
			$key_from = $key_to = $merged_key;
		} else {
			list($key_from, $key_to) = $merged_key;
		}

		$_destination_keys = $this->pluck($key_from);
		$_source_keys = ars($objects)->pluck($key_to);
		$keys = array_keys($_source_keys);

		foreach ($keys as $_key) {
			$idx = array_search($_source_keys[$_key], $_destination_keys);
			$this[$idx]->$key = $objects[$_key];
		}

		return (array)$this;
	}
}
?>