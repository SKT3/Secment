<?php

	/**
	 * Paginator
	 *
	 * @package Sweboo
	 * @subpackage Paginator
	 */
	class Paginator {
		
		/**
		 * Current page
		 *
		 * @var int
		 */
		public $current_page;
		
		/**
		 * Items per page
		 *
		 * @var int
		 */
		public $items_per_page;
		
		/**
		 * Pages count
		 *
		 * @var int
		 */
		public $pages_count;

		/**
		 * Constructor
		 *
		 * @param int $pages_count
		 * @param int $current_page
		 * @return Paginator
		 */
		function __construct($pages_count, $current_page = 1) {
			$this->pages_count = $pages_count;
			$this->current_page = $this->has_page($current_page) ? $current_page : 1;
		}

	    /**
    	 * Returns true if this paginator contains the page
    	 * 
    	 * @param int $page
    	 * @return boolean
      	 */
		function has_page($page) {
			return $page >= 1 && $page <= $this->pages_count;
		}

		/**
		 * First page object
		 *
		 * @return PaginatorPage
		 */
		function first() {
			return new PaginatorPage($this, 1);
		}

		/**
		 * Last page object
		 *
		 * @return PaginatorPage
		 */
		function last() {
			return new PaginatorPage($this, $this->pages_count);
		}

		/**
		 * Current page object
		 *
		 * @return PaginatorPage
		 */
		function current() {
			return new PaginatorPage($this, $this->current_page);
		}

		/**
		 * Next page object
		 *
		 * @return PaginatorPage
		 */
		function next() {
			$next_page = $this->has_page($this->current_page + 1) ? $this->current_page + 1 : $this->current_page;
			return new PaginatorPage($this, $next_page);
		}

		/**
		 * Prev page object
		 *
		 * @return PaginatorPage
		 */
		function prev() {
			$prev_page = $this->has_page($this->current_page - 1) ? $this->current_page - 1 : $this->current_page;
			return new PaginatorPage($this, $prev_page);
		}

		/**
		 * Specific page object
		 *
		 * @return PaginatorPage
		 */
		function page($page) {
			if(!$this->has_page($page)) return false;
			return new PaginatorPage($this, $page);
		}


		// 0, 1, |2|, 3, 4
		// 1, |2|, 3, 4, 5
		function range($padding = 2) {
			$first_in_range = $this->current_page - $padding;
			$last_in_range = $this->current_page + $padding;

			if(!$this->has_page($first_in_range)) {
				$last_in_range += $padding - ($this->current_page - 1);
				while(!$this->has_page($last_in_range)) {
					$last_in_range--;
				}
				$first_in_range = 1;
			}

			if(!$this->has_page($last_in_range)) {
				$first_in_range -= $padding - ($this->pages_count - $this->current_page);
				while(!$this->has_page($first_in_range)) {
					$first_in_range++;
				}
				$last_in_range = $this->pages_count;
			}

			return array(
				'first' => $this->page($first_in_range),
				'last' => $this->page($last_in_range)
			);
		}

		function pages_in_range($padding = 2) {
			$range = $this->range($padding);
			$pages = array();
			for ($i = $range['first']->page_number; $i <= $range['last']->page_number; $i++) {
				array_push($pages, $this->page($i));
			}
			return $pages;
		}

	}

	/**
	 * Paginator
	 *
	 * @package Sweboo
	 * @subpackage Paginator
	 */
	class PaginatorPage extends Paginator {

		/**
		 * Holds the current page number
		 *
		 * @var int
		 */
		public $page_number;
		
		/**
		 * Holds the current offset
		 *
		 * @var int
		 */
		private $offset;
		
		/**
		 * The current Paginator object
		 *
		 * @var object Paginator
		 */
		private $paginator;

		/**
		 * Constructor
		 *
		 * @param Paginator $paginator
		 * @param int $page_number
		 * @return new PaginatorPage
		 */
		function __construct($paginator, $page_number) {
			$this->page_number = $page_number;
			$this->paginator = $paginator;
		}

		/**
		 * To string method
		 *
		 * @return string $page_number
		 */
		function __toString() {
			return (string) $this->page_number;
		}

		/**
		 * Checks if this is the first page
		 *
		 * @return boolean
		 */
		function first() {
			return $this->page_number == 1;
		}

		/**
		 * Checks if this is the last page
		 *
		 * @return boolean
		 */
		function last() {
			return $this->page_number == $this->paginator->pages_count;
		}

		/**
		 * Check if this is the current page
		 *
		 * @return boolean
		 */
		function current() {
			return $this->page_number == $this->paginator->current_page;
		}

		/**
		 * Returns the offset
		 *
		 * @return int $offset
		 */
		function offset() {
			$this->offset = ($this->page_number - 1) * $this->paginator->items_per_page;
			return $this->offset;
		}
	}
?>