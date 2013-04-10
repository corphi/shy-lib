<?php

namespace Shy\Database\Metadata;

use Shy\Database\Database;



/**
 * Table metadata class that queries the database.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class LiveTableMetadata implements TableMetadata
{
	/**
	 * @var Database
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * Create a new instance for the given database and table name. Every metadata is read on demand (and not cached).
	 * @param Database $database
	 * @param string $table_name
	 */
	public function __construct(Database $database, $table_name)
	{
		$this->db = $database;
		$this->params = array(
			'db' => $database->get_name(),
			'tbl' => $table_name,
		);
	}

	public function describe()
	{
		return $this->db
			->query('DESCRIBE TABLE ' . $this->db->escape_column($this->params['tbl']))
			->fetch_array();
	}

	public function get_pk_column_name()
	{
		return $this->db
			->query("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND CONSTRAINT_NAME = 'PRIMARY'")
			->set_params($this->params)
			->fetch_value();
	}

	public function get_empty_row()
	{
		return $this->db
			->query('SELECT COLUMN_NAME, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl ORDER BY ORDINAL_POSITION')
			->set_params($this->params)
			->fetch_column('COLUMN_DEFAULT', 'COLUMN_NAME');
	}

	public function get_referenced_by()
	{
		$fetcher = function (\mysqli_result $result) {
			if (!$result) {
				return array();
			}
			$arr = array();
			while ($row = $result->fetch_assoc()) {
				$arr[$row['TABLE_NAME']][$row['COLUMN_NAME']] = $row['REFERENCED_COLUMN_NAME'];
			}
			$result->free();
			return $arr;
		};
		return $this->db->query(
			'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
			. ' WHERE TABLE_SCHEMA = :db AND REFERENCED_TABLE_SCHEMA = :db AND REFERENCED_TABLE_NAME = :tbl',
			$this->params)->fetch_custom($fetcher, MYSQLI_USE_RESULT);
	}
}
