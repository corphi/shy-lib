<?php

namespace Shy\Database;



/**
 *
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Table extends View
{
	/**
	 * @var array
	 */
	protected $referenced_by;
	/**
	 * @var string
	 */
	protected $pk_column;

	/**
	 * @param Database $db
	 * @param string $name
	 */
	public function __construct(Database $db, $name)
	{
		parent::__construct($db, $name);

		$params = array(
			'database' => $db->name(),
			'table' => $name,
		);
		$this->pk_column = $db
			->query('SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
				. ' WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table'
				. " AND CONSTRAINT_NAME = 'PRIMARY'")
			->set_params($params)
			->fetch_value();
	}

	public function fetch_tree($grpcol, $idcol = null)
	{
		if (!$idcol) {
			$idcol = $this->pk_column;
		}
		return parent::fetch_tree($grpcol, $idcol);
	}

	/**
	 * Read references from a row to other rows.
	 * @param array $subject
	 * @param string $table
	 * @param string $column
	 * @throws DatabaseException
	 */
	public function references(array $subject, $table, $column = null)
	{
		return $this->db->table($table)->referenced_by($subject, $this->name, $column);
	}
	/**
	 * Read references to a row from other rows.
	 * @param array $subject
	 * @param string $table
	 * @param string $column
	 * @throws DatabaseException
	 */
	public function referenced_by(array $subject, $table, $column = null)
	{
		if (!isset($this->referenced_by)) {
			// Load references
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
			$this->referenced_by = $db->query(
				'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
				. ' WHERE TABLE_SCHEMA = :database AND REFERENCED_TABLE_SCHEMA = :database AND REFERENCED_TABLE_NAME = :table',
				$params)->fetch_custom($fetcher, MYSQLI_USE_RESULT);
		}
		if (!isset($this->referenced_by[$table])) {
			throw new DatabaseException(sprintf('Table “%s” not referenced from table “%s”', $this->name, $table));
		}
		$references = $this->referenced_by[$table];
		if (!$column) {
			if (count($references) > 1) {
				throw new DatabaseException(sprintf('Ambiguous refererences from table “%s” to “%s”', $table, $this->name));
			}
			$column = array_keys($references);
			$column = reset($column);
		} elseif (!isset($references[$column])) {
			throw new DatabaseException(sprintf('Column “%s” not found in table “%s”', $column, $this->name));
		}

		return $this->db->table($table)->filter(array(
			$column => $subject[$references[$column]],
		));
	}

	/**
	 * Fetch a row by its primary key value.
	 * @param integer $id
	 * @return array|false
	 */
	public function by_id($id)
	{
		return $this->db
			->query("SELECT * FROM " . $this->escaped_name . " WHERE " . $this->db->escape_column($this->pk_column) . " = :id")
			->set_params(array('id' => $id))
			->fetch_row();
	}

	/**
	 * Insert a new row into the table. Return its newly assigned primary key, true or false.
	 * @param array $row
	 * @return integer|boolean
	 */
	public function insert(array $row)
	{
		$cols = array_map(array($this->db, 'escape_column'), array_keys($row));

		$sql = 'INSERT INTO ' . $this->escaped_name . ' ('
			. implode(', ', $cols) . ') VALUES ' . $this->db->escape_value($row);

		if ($this->db->execute($sql)) {
			return mysqli_insert_id($this->db->connection()) ?: true;
		}
		return false;
	}

	/**
	 * Update an existing row in the table. Will be identified by its primary key.
	 * If you set it explicitly (second parameter), you can change its value.
	 * @param array $row
	 * @param integer $pk
	 * @return boolean
	 */
	public function update(array $row, $pk = null)
	{
		if ($pk === null) {
			$pk = $row[$this->pk_column];
			unset($row[$this->pk_column]);
		}

		$sql = 'UPDATE ' . $this->escaped_name . ' SET ';
		foreach ($row as $k => $v) {
			$sql .= $this->db->escape_column($k) . ' = ' . $this->db->escape_value($v) . ', ';
		}
		$sql = substr($sql, 0, -2) . ' WHERE ' . $this->db->escape_column($this->pk_column)
			. ' = ' . $this->db->escape_value($pk) . ' LIMIT 1';

		return $this->db->execute($sql);
	}

	/**
	 * Remove rows from the table. Return the number of deleted rows; or false on error.
	 * @param array $where
	 * @return integer|false
	 */
	public function remove(array $where = array())
	{
		$sql = 'DELETE FROM ' . $this->escaped_name . $this->where($where);
		return $this->database()->execute($sql)
			? $this->database()->connection()->affected_rows
			: false;
	}
}
