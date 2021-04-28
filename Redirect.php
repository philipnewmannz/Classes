<?php

# ---------------------------------------------------- #
# FILE: Redirect.php		            	     	   #
# ---------------------------------------------------- #
# DEVELOPER: PHILIP J. NEWMAN  (Primal Media Limited)  #
# ---------------------------------------------------- #
# VERSION 0.0.1							     	  	   #
# ---------------------------------------------------- #

# THIS CLASS PROVIDES METHODS WORKING WITH INVOICES
# AND PERSONAL ACCOUNT SETTINGS.
# SCRIPT (c) PRIMAL MEDIA LIMITED

# 0.0.1 28-Apr-2021 - Updated class to work with PHP8.0 as some items were deprecated

class Redirect {

	/**
	 * The page being requested
	 *
	 * @var string
	 */
	var $_page;

	/**
	 * Theme (if specified)
	 *
	 * @var string
	 */
	var $_theme;

	/**
	 * Variables to be used in the page
	 *
	 * @var string
	 */
	var $_parts;

	/**
	 * Just load this so we can continue.
	 *
	 * @var return array()
	 */

	function __construct() {

		$net_uri = str_replace('/' ,' ',$_SERVER['REQUEST_URI']);
		$parts = explode('/',$_SERVER['REQUEST_URI']);
		$this->_page = array_shift($parts);
		
		if(strpos($parts[count($parts) - 1],'.html') !== false) {
			$this->_theme = array_pop($parts);
		}

		$this->_parts = $parts;

	}

	/**
	 * Gets a string to identify the page
	 *
	 * @return string
	 */
	public function getPage() {
		return $this->_page;
	}

	/**
	 * Gets the theme (if specified)
	 *
	 * @return unknown
	 */
	public function getTheme() {
		return $this->_theme;
	}

	/**
	 * Gets the page variables
	 *
	 * @return array
	 */
	public function getRawVars() {
		return $this->_parts;
	}
}

?>