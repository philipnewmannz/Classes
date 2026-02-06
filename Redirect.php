<?php

# ---------------------------------------------------- #
# FILE: Redirect.php                                   #
# ---------------------------------------------------- #
# DEVELOPER: PHILIP J. NEWMAN  (Primal Media Limited)  #
# ---------------------------------------------------- #
# VERSION 0.0.2                                        #
# ---------------------------------------------------- #

# THIS CLASS PROVIDES METHODS WORKING WITH INVOICES
# AND PERSONAL ACCOUNT SETTINGS.
# SCRIPT (c) PRIMAL MEDIA LIMITED

# 0.0.1 07-Feb-2025 - Updated class to work with PHP 8.4 as some items were deprecated
# 0.0.1 28-Apr-2021 - Updated class to work with PHP 8.0 as some items were deprecated

class Redirect {

	/**
	 * The page being requested
	 * @var string
	 */
	var $_page;

	/**
	 * Theme (if specified)
	 * @var string
	 */
	var $_theme;

	/**
	 * Variables to be used in the page
	 * @var array
	 */
	var $_parts;

	/**
	 * Constructor: Parses the REQUEST_URI to determine the page, 
	 * theme, and additional URL parameters.
	 */
	function __construct() {

		// Standardize the URI by removing leading/trailing slashes and splitting into segments
		$net_uri = str_replace('/' ,' ',$_SERVER['REQUEST_URI']); 
		$parts = explode('/', $_SERVER['REQUEST_URI']);

		// The first segment of the URI is treated as the primary page/controller
		$this->_page = array_shift($parts);
		
		// Check if the final segment contains a '.html' extension to identify a specific theme file
		if(count($parts) > 0 && strpos($parts[count($parts) - 1], '.html') !== false) {
			$this->_theme = array_pop($parts);
		}

		// Any remaining segments are stored as parameters/variables
		$this->_parts = $parts;
	}

	/**
	 * Returns the primary page identifier
	 * @return string
	 */
	public function getPage() {
		return $this->_page;
	}

	/**
	 * Returns the theme filename if one was detected in the URL
	 * @return string|null
	 */
	public function getTheme() {
		return $this->_theme;
	}

	/**
	 * Returns the array of URL segments following the page identifier
	 * @return array
	 */
	public function getRawVars() {
		return $this->_parts;
	}
}

?>
