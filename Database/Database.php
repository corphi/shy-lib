<?php

namespace Shy\Database;

use Shy\Database\Metadata\Metadata;
use Shy\Database\Metadata\LiveMetadata;



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
	public function get_connection()
	{
		return $this->conn;
	}

	/**
	 * @var string
	 */
	protected $name;
	/**
	 * Return the name of the selected database.
	 * @return string
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * @var Metadata
	 */
	protected $metadata;
	/**
	 * Return the metadata for the database.
	 * @return Metadata
	 */
	public function get_metadata()
	{
		return $this->metadata;
	}

	/**
	 * Set up the database connection. Uses LiveMetadata if none are given.
	 * @param array $credentials
	 * @param Metadata $metadata
	 */
	public function __construct(array $credentials = array(), Metadata $metadata = null)
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

		$this->name = $credentials['database'] ?: $this->query('SELECT DATABASE()')->fetch_value();

		$this->metadata = $metadata ?: new LiveMetadata($this);
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
	 * Query the database.
	 * @param string $query
	 * @param array $params
	 * @return Query
	 */
	public function query($query, array $params = array())
	{
		if (!$params) {
			return new Query($this, $query);
		}
		$q = new Query($this, $query);
		return $q->set_params($params);
	}

	/**
	 * @var array
	 */
	protected $tables;
	/**
	 * Request a table from the database. Caches used ones.
	 * @param string $name
	 * @return Table
	 * @throws DatabaseException when the table doesn’t exist.
	 */
	public function get_table($name)
	{
		if (isset($this->tables[$name])) {
			return $this->tables[$name];
		}
		$metadata = $this->metadata->get_table_metadata($name);
		if (!$metadata) {
			throw new DatabaseException("There is no table “{$name}”.");
		}
		return $this->tables[$name] = new Table($this, $name, $metadata);
	}

	/**
	 * Escape a table, column or database name for use as identifier.
	 * Also handles arrays.
	 * @param array|string $column
	 * @return string
	 */
	public function escape_column($column)
	{
		if (is_array($column)) {
			return '(' . implode(', ', array_map(array($this, 'escape_column'), $column)) . ')';
		}
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
		if (is_array($value)) {
			return '(' . implode(', ', array_map(array($this, 'escape_value'), $value)) . ')';
		}
		if (is_int($value) || is_float($value) || $value instanceof Literal) {
			return (string) $value;
		}
		return "'" . $this->conn->escape_string($value) . "'";
	}
}
