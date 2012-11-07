<?php

namespace Shy\Database;



/**
 * Your friendly neighbourhood database connection.
 * 
 * It internally uses mysqli. The connection credentials can be passed on create,
 * or will be read from configuration.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Database
{
	/**
	 * @var \mysqli
	 */
	protected $conn;
	/**
	 * The connection to the database.
	 * @return \mysqli
	 */
	public function connection()
	{
		return $this->conn;
	}

	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Setup the database connection. Allows loading all tables from the database.
	 * @param array $credentials
	 * @param boolean $load_tables
	 */
	public function __construct(array $credentials = array(), $load_tables = false)
	{
		// These null values will be replaced with config defaults by \mysqli.
		$credentials += array(
			'host'     => null,
			'user'     => null,
			'password' => null,
			'database' => null,
			'port'     => null,
			'socket'   => null,
		);

		// Connect to the database
		$conn = new \mysqli(
			$credentials['host'],
			$credentials['user'],
			$credentials['password'],
			$credentials['database'],
			$credentials['port'],
			$credentials['socket']
		);
		$conn->set_charset('utf8');
		$this->conn = $conn;

		$this->name = $credentials['database'];

		if ($load_tables) {
			$tables = $conn->query('SHOW TABLES')->fetch_all();
			foreach ($tables as $table) {
				$this->table($table[0]);
			}
		}
	}

	/**
	 * Execute a query on the database.
	 * @param string $query
	 * @return \mysqli_result|boolean
	 */
	public function execute($query)
	{
		return $this->conn->query($query);
	}

	/**
	 * Prepare a statement.
	 * @param string $query
	 * @return \mysqli_stmt
	 */
	public function prepare($query)
	{
		return $this->conn->prepare($query);
	}

	/**
	 * Query the database.
	 * @param string $query
	 * @param array $params
	 * @return Query
	 */
	public function query($query, array $params = array())
	{
		$q = new Query($this, $query);
		return $q->set_params($params);
	}

	/**
	 * @var array
	 */
	protected $tables;
	/**
	 * Request a table from the database. Caches used tables.
	 * @param string $name
	 * @param array $filter
	 * @return Table
	 */
	public function table($name, array $filter = array())
	{
		if (!isset($this->tables[$name])) {
			$this->tables[$name] = $this->read_table($name);
		}
		return $this->tables[$name]->filter($filter); 
	}
	/**
	 * Read table metadata from the database.
	 * @param string $name
	 * @param boolean $have_faith
	 * @return Table|View
	 */
	protected function read_table($name, $have_faith = false)
	{
		if (!$have_faith) {
			static $db_name = null;
			if (!$db_name) {
				$db_name = $this->escape_value($this->name);
			}
			$table_type = $this->query(
				'SELECT TABLE_TYPE FROM information_schema.TABLES'
				. " WHERE TABLE_SCHEMA = $db_name AND TABLE_NAME = " . $this->escape_value($name)
			)->fetch_value();
			if ($table_type !== 'BASE TABLE') {
				// VIEW or SYSTEM VIEW
				return new View($this, $name);
			}
		}
		return new Table($this, $name);
	}

	/**
	 * Escape a table, column or database name for use as identifier.
	 * @param string $column
	 * @return string
	 */
	public function escape_column($column)
	{
		return '`' . str_replace('`', '``', $column) . '`';
	}
	/**
	 * Escape a value for use with the database.
	 * @param mixed $value
	 * @return string
	 */
	public function escape_value($value)
	{
		if ($value === null) {
			return 'NULL';
		}
		if (is_int($str) || is_float($str) || ctype_digit((string) $str)) {
			return (string) $value;
		}
		return "'" . $this->conn->escape_string($value) . "'";
	}
}
