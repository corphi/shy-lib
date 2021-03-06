<?php

namespace Shy\Database;



/**
 * Capsulates queries to the database.
 *
 * They can be fetched in different ways. If you want to use pagination via
 * set_page() or set_limit(), you cannot use LIMIT or OFFSET inside your $query.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Query
{
	/**
	 * @var Database
	 */
	protected $db;
	/**
	 * @return Database
	 */
	public function get_database()
	{
		return $this->db;
	}

	/**
	 * The SQL query.
	 * @var string
	 */
	protected $query;

	/**
	 * Offset and limit for this query.
	 * @var array
	 */
	protected $limit = array();

	/**
	 * Create a query for the given Database.
	 * @param Database $database
	 * @param string $query
	 */
	public function __construct(Database $database, $query)
	{
		$this->db = $database;
		$this->query = (string) $query;
	}

	public function __toString()
	{
		if (!$this->limit) {
			return $this->query;
		}
		if (!isset($this->limit['offset']) || !$this->limit['offset']) {
			return $this->query . ' LIMIT ' . $this->limit['limit'];
		}
		return $this->query . ' LIMIT ' . $this->limit['offset'] . ', ' . $this->limit['limit'];
	}

	/**
	 * Explain this query.
	 * @return array
	 */
	public function explain()
	{
		$explain = new Query($this->db, 'EXPLAIN ' . $this);
		return $explain->fetch_array();
	}

	/**
	 * @var array
	 */
	protected static $custom_fetchers;
	/**
	 * Register a callable for fetching using an identifier.
	 * @param string $name
	 * @param callable $callback
	 */
	public static function register_fetcher($name, $callback)
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException(__CLASS__ . '::' . __METHOD__ . '(): $callback is not callable.');
		}
		self::$custom_fetchers[$name] = $callback;
	}
	/**
	 * Fetch the results from the Database and leave processing to a callable.
	 * @param string|callable $fetcher
	 * @param integer $resultmode
	 * @return mixed
	 */
	public function fetch_custom($fetcher, $resultmode = MYSQLI_STORE_RESULT)
	{
		if (is_string($fetcher) && isset(self::$custom_fetchers[$fetcher])) {
			$fetcher = self::$custom_fetchers[$fetcher];
		} elseif (!is_callable($fetcher)) {
			throw new \InvalidArgumentException(__CLASS__ . '::' . __METHOD__ . '(): $fetcher is neither callable nor a registered fetcher.');
		}
		return call_user_func($fetcher, $this->db->get_connection()->query($this, $resultmode));
	}
	/**
	 * Returns the result as three-dimensional array; or false.
	 * The array will be grouped by values from column $grpcol and can be indexed by values from column $idcol.
	 * @param string $grpcol
	 * @param string $idcol
	 * @return array|false
	 */
	public function fetch_tree($grpcol, $idcol = null)
	{
		$rs = $this->db->get_connection()->query($this, MYSQLI_USE_RESULT);
		if (!$rs) {
			return false;
		}

		$arr = array();
		if ($idcol) {
			while ($row = $rs->fetch_assoc()) {
				$arr[$row[$grpcol]][$row[$idcol]] = $row;
			}
		} else {
			while ($row = $rs->fetch_assoc()) {
				$arr[$row[$grpcol]][] = $row;
			}
		}
		$rs->free();
		return $arr;
	}
	/**
	 * Returns the result of the given SQL command as two-dimensional array; or false.
	 * The array can be indexed by values from column $idcol.
	 * @param $idcol string
	 * @return array|false
	 */
	public function fetch_array($idcol = null)
	{
		$rs = $this->db->get_connection()->query($this, MYSQLI_USE_RESULT);
		if (!$rs) {
			return false;
		}

		if ($idcol) {
			$arr = array();
			while ($row = $rs->fetch_assoc()) {
				$arr[$row[$idcol]] = $row;
			}
		} elseif (method_exists($rs, 'fetch_all')) {
			// Needs PHP 5.3
			$arr = $rs->fetch_all(MYSQLI_ASSOC);
		} else {
			$arr = array();
			while ($row = $rs->fetch_assoc()) {
				$arr[] = $row;
			}
		}
		$rs->free();
		return $arr;
	}
	/**
	 * Returns a column of the result of the given SQL command as one-dimensional array.
	 * The array can be indexed by values from the column $idcol.
	 * @param $col string
	 * @param $idcol string
	 * @return array|false
	 */
	public function fetch_column($col = null, $idcol = null)
	{
		$rs = $this->db->get_connection()->query($this, MYSQLI_USE_RESULT);
		if (!$rs) {
			return false;
		}

		$arr = array();
		if (!$col) {
			while ($row = $rs->fetch_row()) {
				$arr[] = $row[0];
			}
		} elseif (!$idcol) {
			while ($row = $rs->fetch_assoc()) {
				$arr[] = $row[$col];
			}
		} else {
			while ($row = $rs->fetch_assoc()) {
				$arr[$row[$idcol]] = $row[$col];
			}
		}
		$rs->free();
		return $arr;
	}
	/**
	 * Returns the first line from the result of the given SQL command as associative array; or false.
	 * @return array|false
	 */
	public function fetch_row()
	{
		$this->set_limit(1);
		$rs = $this->db->get_connection()->query($this, MYSQLI_USE_RESULT);
		if ($rs && $row = $rs->fetch_assoc()) {
			$rs->free();
			return $row;
		}
		return false;
	}
	/**
	 * Returns the value of the first field in the first row from the result of the given SQL command; or false.
	 * @return string|false
	 */
	public function fetch_value()
	{
		$this->set_limit(1);
		$rs = $this->db->get_connection()->query($this, MYSQLI_USE_RESULT);
		if ($rs && $row = $rs->fetch_row()) {
			$rs->free();
			return $row[0];
		}
		return false;
	}

	/**
	 * Pagination.
	 * @param integer $page
	 * @param integer $per_page
	 * @return self
	 */
	public function set_page($page = 1, $per_page = null)
	{
		return $this->set_limit(
			$per_page,
			($page - 1) * $per_page
		);
	}

	/**
	 * Impose a limit on query results.
	 * @param integer $limit
	 * @param integer $offset
	 * @return self
	 */
	public function set_limit($limit = null, $offset = 0)
	{
		if ($limit && intval($limit) > 0) {
			$this->limit['limit'] = intval($limit);
		} else {
			unset($this->limit['limit']);
		}

		if ($offset && intval($offset) > 0) {
			$this->limit['offset'] = intval($offset);
			if (!$limit || intval($limit) <= 0) {
				$this->limit['limit'] = 999999999;
			}
		} else {
			unset($this->limit['offset']);
		}

		return $this;
	}

	/**
	 * Define parameter values for the query.
	 * FIXME: It’s a dirty hack. You have been warned.
	 * @param array $params
	 * @return self
	 */
	public function set_params(array $params)
	{
		foreach ($params as $param => $value) {
			$this->query = str_replace(':' . $param, $this->db->escape_value($value), $this->query);
		}
		return $this;
	}
}
