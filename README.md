
<h1>Database.php</h1>

## Documentation for the `Database` Class

This documentation covers the usage of the optimized version of your `Database` class. It maintains all original function names while improving performance by managing connections more intelligently.

---

### Core Connection Methods

The class handles connections using a "lazy loading" approach. While methods like `mysql_Query` will connect automatically, you can control the connection lifecycle manually.

#### 1. Opening a Connection

The `mysql_Connect()` method establishes a link to the MySQL server using the credentials provided during instantiation or defined constants. It includes a check to prevent redundant connections.

```php
$db = new Database();

// Manually open the connection
$db->mysql_Connect(); 

if ($db->status) {
    echo "Connected successfully!";
}

```

#### 2. Closing a Connection

The `mysql_Close()` method terminates the active link to the database and clears the internal `mysqli` object.

```php
// Manually close the connection
$db->mysql_Close();

```

---

### Data Operations

#### Executing Standard Queries (`mysql_Query`)

Use this for standard SQL strings. It automatically clears previous results and returns an array of rows for `SELECT` statements.

```php
$results = $db->mysql_Query("SELECT * FROM users WHERE status = 'active'");

foreach ($results as $row) {
    echo $row['username'];
}

```

#### Using Prepared Statements (`mysql_PrepareQuery`)

This is the most secure method for handling user input to prevent SQL injection. It utilizes the splat operator for efficient parameter binding.

```php
$sql = "SELECT * FROM users WHERE email = ? AND level = ?";
$params = [
    'types' => 'si', 
    'values' => ['user@example.com', 1]
];

$user = $db->mysql_PrepareQuery($sql, $params);

```

---

### Utility Methods

| Method | Purpose |
| --- | --- |
| `mysql_ClearRows()` | Manually flushes the `$this->_rows` array to free up memory. |
| `mysql_EscapeString($data)` | Secures a string for use in a query without needing to manually open/close connections repeatedly. |
| `__set($name, $value)` | Allows for dynamic property setting, ensuring compatibility with PHP 8.2+. |

---

### Best Practices

* **Automatic Connection**: You do not strictly need to call `mysql_Connect()` before every query; the class will check the connection status and connect automatically if required.
* **Resource Management**: While PHP handles connection teardown at the end of a script, calling `mysql_Close()` is recommended for long-running scripts to free up database slots.

<h1>Authenticate.php</h1>

<p>Class for authentication of user accounts includeing sessions being generated. </p>

<h1>Redirect.php</h1>

<p>Basic class for chopping URL as using as veriables within an application</p>
<p>Updated to 0.0.1</p>
<ul>
	<li>Added __construct to allow update for PHP8.0</li>
</ul>

