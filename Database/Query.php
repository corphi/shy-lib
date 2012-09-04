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
	public function database()
	{
		return $this->db;
	}
	/**
	 * @var string
	 */
	protected $query;
	/**
	 * @var array
	 */
	protected $limit = array();

	/**
	 * Create a query for the given Database.
	 * @param Database $db
	 * @param string $query
	 */
	public function __construct(Database $db, $query)
	{
		$this->db = $db;
		$this->query = $query;
	}
	/**
	 * @return string
	 */
	public function __toString()
	{
		if (!$this->limit) {
			$sql = $this->query;
		} elseif (!isset($this->limit['offset']) || !$this->limit['offset']) {
			$sql = $this->query . ' LIMIT ' . $this->limit['limit'];
			return $sql;
		} else {
			$sql = $this->query . " LIMIT {$this->limit['offset']}, {$this->limit['limit']}";
		}
		echo $sql, "\r\n";
		return $sql;
	}

	/**
	 * Explain this query.
	 * 
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
	 * Register a closure for fetching using an identifier.
	 * @param string $fetcher
	 * @param \Closure $c
	 */
	public static function register_fetcher($fetcher, \Closure $c)
	{
		self::$custom_fetchers[$fetcher] = $c;
	}
	/**
	 * Fetch the results from the Database and leave processing to a closure.
	 * @param \Closure|string $fetcher
	 * @param integer $resultmode
	 * @return mixed
	 */
	public function fetch_custom($fetcher, $resultmode = MYSQLI_STORE_RESULT)
	{
		if (is_string($fetcher)) {
			$fetcher = self::$custom_fetchers[$fetcher];
		}
		if (!($fetcher instanceof \Closure)) {
			throw new \InvalidArgumentException('$fetcher needs to be a Closure or a string identifier for a registered fetcher.');
		}
		return $fetcher($this->db->connection()->query($this, $resultmode));
	}
	/**
	 * Returns the result as three-dimensional array; or false.
	 * The array will be grouped by values from column $grpcol and can be indexed by values from column $idcol.
	 * @param string $grpcol
	 * @param string $idcol
	 * @return mixed
	 */
	public function fetch_tree($grpcol, $idcol = null)
	{
		$rs = $this->db->connection()->query($this, MYSQLI_USE_RESULT);
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
	 * @return mixed
	 */
	public function fetch_array($idcol = null)
	{
		$rs = $this->db->connection()->query($this, MYSQLI_USE_RESULT);
		if (!$rs) {
			return false;
		}

		if ($idcol) {
			$arr = array();
			while ($row = $rs->fetch_assoc()) {
				$arr[$row[$idcol]] = $row;
			}
		} else {
			$arr = $rs->fetch_all(MYSQLI_ASSOC);
		}

		$rs->free();
		return $arr;
	}
	/**
	 * Returns a column of the result of the given SQL command as one-dimensional array.
	 * The array can be indexed by values from the column $idcol.
	 * @param $col string
	 * @param $idcol string
	 * @return mixed
	 */
	public function fetch_column($col = null, $idcol = null)
	{
		$rs = $this->db->connection()->query($this, MYSQLI_USE_RESULT);
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
	 * @param $sql string
	 * @return array|false
	 */
	public function fetch_row($sql)
	{
		$this->set_limit(1);
		$rs = $this->db->connection()->query($this, MYSQLI_STORE_RESULT);
		if ($rs && $row = $rs->fetch_assoc()) {
			$rs->free();
			return $row;
		}
		return false;
	}
	/**
	 * Returns the value of the first field in the first row from the result of the given SQL command; or false.
	 * @return string|boolean
	 */
	public function fetch_value()
	{
		$this->set_limit(1);
		$rs = $this->db->connection()->query($this, MYSQLI_STORE_RESULT);
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
	 * FIXME: It’s a dirty hack. You have been warned.
	 * @param array $params
	 * @return Query
	 */
	public function set_params(array $params)
	{
		$db = $this->database();
		foreach ($params as $param => $value) {
			$this->query = str_replace(':' . $param, $db->escape_value($value), $this->query);
		}
		return $this;
	}
}
