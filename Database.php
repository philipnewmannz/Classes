<?php
# ---------------------------------------------------- #
# FILE: Database.php		          		     	   #
# ---------------------------------------------------- #
# DEVELOPER: PHILIP J. NEWMAN  (Primal Media Limited)  #
# ---------------------------------------------------- #
# VERSION 0.0.8							     	  	   #
# ---------------------------------------------------- #

# THIS CLASS PROVIDES METHODS FOR SELECT, INSERT AND UPDATE
# INTO THE WEBSITE'S DATABASE.  ITEMS THAT ARE DELETED
# SHOULD HAVE THE 'DELETED' FIELD UPDATED WITH '1'
# SCRIPT (c) PRIMAL MEDIA LIMITED

# 0.0.8 - Added __construct to allow use with more than one database. 
# 0.0.8	- Updated reg_match("/^select (.*)/i",trim($sql) to make sure it's SELECT is at the start.
# 0.0.7	- Removed if (empty from around row count within sql.
# 0.0.6	- Added method mysql_EscapeString() for escaping stings easy.
# 0.0.5 - Updated the error method in mysql_Query() to display error.

	class Database {
		
		public $_rows = array();
		
		public $_queries = array();
		
		public $_sql = array();
		
		public $_config = array();
		
		public $mysqli;
		
		public $result;

		
	   /**
	   	* Make sure we have the construct relay the login details.
	   	*
	   	* @param String 
	   	*/
		
		function __construct($input_data=array()) {
		// lets make sure we have the right details.
		   	if (defined('MYSQL_SERVER')) { $this->mysql_Server = MYSQL_SERVER; } // END if
			if (defined('MYSQL_LOGIN')) { $this->mysql_Login = MYSQL_LOGIN;	} // END if
			if (defined('MYSQL_PASSWORD')) { $this->mysql_Password = MYSQL_PASSWORD;	} // END if
			if (defined('MYSQL_DATABASE')) { $this->mysql_Database = MYSQL_DATABASE;	} // END if
		   	
			if (!empty($input_data)) {
		   	// update the items that are in the incoming array.
		   		if (isset($input_data['server'])) { $this->mysql_Server = $input_data['server']; } // END if
				if (isset($input_data['login'])) { $this->mysql_Login = $input_data['login']; } // END if
				if (isset($input_data['password'])) { $this->mysql_Password = $input_data['password']; } // END if
				if (isset($input_data['database'])) { $this->mysql_Database = $input_data['database']; } // END if
		   	} // END if
	   	
		} // END __construct
		
	   /**
	   	* Make a connection to the database.
	   	*
	   	* @param String 
	   	*/

		public function mysql_Connect () {
			$this->mysqli = @new mysqli($this->mysql_Server,$this->mysql_Login, $this->mysql_Password, $this->mysql_Database);
			if ($this->mysqli->connect_errno) {
				die ("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
			}
			
			return $this->mysqli;
		} // END mysql_Connect
		
		/**
	   	* Close the connection to the database.
	   	*
	   	* @param String 
	   	*/

		public function mysql_Close () {
			return $this->mysqli->close();;	
		}
		
		/**
	   	* Push the query and create the output as an array. If you want to use prepare you'll need to pass
		* the incoming veriables through $params in the array ('type'=>'param') format.
		* Types: i = interger, d = double, s = string, b = blob sent as packets.
	   	*
	   	* @param String $sql to create the query
	   	*/
		
		public function mysql_Query ($sql,$params=array()) {
		// id sql is empty.
			if (empty($sql)) {
				$this->_sql['error'][] = "SQL query was empty.";
				return $this->_sql;
			} // END if
		
		// make the connection if we are currently not connected.
			$this->mysql_Connect();
			
		// create an object all result and run the query.
			if ($result = $this->mysqli->query($sql)) {
			// we need the number of rows.		
				$this->_queries['row_count'] = $this->mysqli->affected_rows;			
				
				if (isset($result->num_rows)) {
					$this->_queries['row_count'] = $result->num_rows;
				}
				
			// lets get the rows if it's a select.	
				if (preg_match("/^select (.*)/i",trim($sql)) > 0) {
					if ($this->_queries['row_count'] >= '1') {
					// get rows only if we find a select statement.
						while($row = $result->fetch_array(MYSQLI_ASSOC)) {
							$this->_rows[] = $row;
						} // END while.
							
						// close the result.
							$result->close();
					
					} // END if.
			
				} // END if select

			// add some of these vars to use later.	
				
				$this->_sql['query'][] = $sql;
				$this->_queries['affected_rows'] = $this->mysqli->affected_rows;
				$this->_queries['insert_id'] = $this->mysqli->insert_id;

			} else {
			// load the error message into the array if there is one.
				$this->_sql['error'][] = $this->mysqli->error;	
			}
			
			$this->mysql_Close();
			
			return $this->_rows;
		
		} // END mysql_Query
		
	   /**
	   	* Clear last result so there isn't an over lap...
	   	*
	   	* @param String 
	   	*/

		public function mysql_ClearRows () {
			$this->_rows = array();
			return true;	
		}
		
	   /**
	   	* use this like mysql_escape_string()
	   	*
	   	* @param String 
	   	*/

		public function mysql_EscapeString ($data="0") {
		// lets do stuff if the string isn't empty.
			if (!empty($data)) {
				$this->mysql_Connect();
				$data = $this->mysqli->real_escape_string($data);
				$this->mysql_Close();
			}

			return $data;	
		}
	
	} // END Database

?>