<?php
# ---------------------------------------------------- #
# FILE: Database.php		          		     	   #
# ---------------------------------------------------- #
# DEVELOPER: PHILIP J. NEWMAN  (Primal Media Limited)  #
# ---------------------------------------------------- #
# VERSION 0.1.4		  					     	  	   #
# ---------------------------------------------------- #

# THIS CLASS PROVIDES METHODS FOR SELECT, INSERT AND UPDATE
# INTO THE WEBSITE'S DATABASE.  ITEMS THAT ARE DELETED
# SHOULD HAVE THE 'DELETED' FIELD UPDATED WITH '1'
# SCRIPT (c) PRIMAL MEDIA LIMITED

# 0.1.4 - Updated a number of comments.
# 0.1.3 - Added mysql_Prepare() and mysql_PrepareQuery
# 0.1.2 - Decleared all the vers that will be used in the future.  
#       - Now using the _set() to set dynamic vers.
# 0.1.1 - Updated veriables and declearing veriables for PHP 8.2 update.
# 0.1.0 - Updated mysql_Connect() and mysql_Query() to use the $this->autoconnect var.
# 0 0.11 - Updated mysql_Multi_Query with $this->autoconnect var.
# 0.0.10 - Added mysql_Multi_Query() to the mix.
# 0.0.9 - Updated construct.
# 0.0.8 - Added __construct to allow use with more than one database. 
# 0.0.8	- Updated reg_match("/^select (.*)/i",trim($sql) to make sure it's SELECT is at the start.
# 0.0.7	- Removed if (empty from around row count within sql.
# 0.0.6	- Added method mysql_EscapeString() for escaping stings easy.
# 0.0.5 - Updated the error method in mysql_Query() to display error.


class Database {
    
    private $mysql_Server;
    private $mysql_Login;
    private $mysql_Password;
    private $mysql_Database;

    public $_rows = [];
    public $_queries = [];
    public $_sql = [];
    private $mysqli = null;
    
    public $status = false;
    public $autoconnect = false;

    function __construct($input_data = array()) {
        $this->mysql_Server   = $input_data['server']   ?? (defined('MYSQL_SERVER')   ? MYSQL_SERVER   : null);
        $this->mysql_Login    = $input_data['login']    ?? (defined('MYSQL_LOGIN')    ? MYSQL_LOGIN    : null);
        $this->mysql_Password = $input_data['password'] ?? (defined('MYSQL_PASSWORD') ? MYSQL_PASSWORD : null);
        $this->mysql_Database = $input_data['database'] ?? (defined('MYSQL_DATABASE') ? MYSQL_DATABASE : null);
    }

    public function __set(string $name, mixed $value): void {
        $this->{$name} = $value;
    }

    public function mysql_Connect() {
        // Check if the connection object exists and the connection is still alive
        // We replace ping() with a check on the thread_id
        if ($this->mysqli instanceof mysqli && !empty($this->mysqli->thread_id)) {
            return $this->mysqli;
        }
    
        $this->mysqli = @new mysqli($this->mysql_Server, $this->mysql_Login, $this->mysql_Password, $this->mysql_Database);
        
        if ($this->mysqli->connect_errno) {
            die("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
        }
    
        $this->mysqli->set_charset("utf8mb4");
        $this->status = true;
        return $this->mysqli;
    }

    public function mysql_Close() {
        if ($this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
            $this->status = false;
        }
        return true;
    }

    // Helper to log query metadata
    private function logMetadata($sql) {
        $this->_sql['query'][] = $sql;
        $this->_queries['affected_rows'] = $this->mysqli->affected_rows;
        $this->_queries['insert_id'] = $this->mysqli->insert_id;
    }

    public function mysql_Query($sql, $params = array()) {
        $this->mysql_ClearRows();
        if (empty($sql)) return $this->_rows;

        $this->mysql_Connect();
        
        $result = $this->mysqli->query($sql);

        if ($result === false) {
            $this->_sql['error'][] = $this->mysqli->error;
            return $this->_rows;
        }

        if ($result instanceof mysqli_result) {
            $this->_queries['row_count'] = $result->num_rows;
            while ($row = $result->fetch_assoc()) {
                $this->_rows[] = $row;
            }
            $result->close();
        } else {
            $this->_queries['row_count'] = $this->mysqli->affected_rows;
        }

        $this->logMetadata($sql);
        return $this->_rows;
    }

    public function mysql_Multi_Query($sql, $params = array()) {
        $this->mysql_ClearRows();
        if (empty($sql)) return $this->_rows;

        $this->mysql_Connect();
        
        if ($this->mysqli->multi_query($sql)) {
            do {
                if ($result = $this->mysqli->store_result()) {
                    $result->free();
                }
            } while ($this->mysqli->next_result());
        } else {
            $this->_sql['error'][] = $this->mysqli->error;
        }

        $this->logMetadata($sql);
        return $this->_rows;
    }

    public function mysql_Prepare($sql) {
        if (empty($sql)) return false;
        $this->mysql_Connect();
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) $this->_sql['error'][] = $this->mysqli->error;
        return $stmt;
    }

    public function mysql_PrepareQuery($sql, $params = array()) {
        $this->mysql_ClearRows();
        $stmt = $this->mysql_Prepare($sql);
        if (!$stmt) return $this->_rows;

        if (!empty($params['values'])) {
            $stmt->bind_param($params['types'], ...$params['values']);
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $this->_rows[] = $row;
                }
                $result->close();
            }
            $this->logMetadata($sql);
        } else {
            $this->_sql['error'][] = $stmt->error;
        }

        $stmt->close();
        return $this->_rows;
    }

    public function mysql_ClearRows() {
        $this->_rows = [];
        return true;
    }

    public function mysql_EscapeString($data = "0") {
        if (empty($data)) return $data;
        $this->mysql_Connect(); // Uses existing connection instead of re-opening
        return $this->mysqli->real_escape_string($data);
    }
    
} // END Database

?>
