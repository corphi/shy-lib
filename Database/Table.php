<?php

namespace Shy\Database;

use Shy\Database\Metadata\TableMetadata;



/**
 * A table in the database.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Table extends TableQuery
{
	/**
	 * @var array
	 */
	protected $referenced_by;

	/**
	 * @var TableMetadata
	 */
	protected $metadata;
	/**
	 * The table’s metadata.
	 * @return TableMetadata
	 */
	public function get_metadata()
	{
		return $this->metadata;
	}

	/**
	 * @var string
	 */
	protected $name;
	/**
	 * Return the name of the table.
	 * @return string
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * @param Database $database
	 * @param string $name
	 * @param TableMetadata $metadata
	 */
	public function __construct(Database $database, $name, TableMetadata $metadata = null)
	{
		$this->db = $database;
		$this->query = 'SELECT * FROM ' . $database->escape_column($name);
		parent::__construct($this);

		$this->metadata = $metadata ?: $database->get_metadata()->get_table_metadata($name);
	}

	public function fetch_tree($grpcol, $idcol = null)
	{
		if ($idcol === true) {
			$idcol = $this->metadata->get_pk_column_name();
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
		$referenced_by = $this->metadata->get_referenced_by();
		if (!isset($referenced_by[$table])) {
			throw new DatabaseException(sprintf('Table “%s” not referenced from table “%s”', $this->name, $table));
		}
		$references = $referenced_by[$table];
		if (!$column) {
			if (count($references) > 1) {
				throw new DatabaseException(sprintf('Ambiguous refererences from table “%s” to “%s”', $table, $this->name));
			}
			$column = array_keys($references);
			$column = reset($column);
		} elseif (!isset($references[$column])) {
			throw new DatabaseException(sprintf('Column “%s” not found in table “%s”', $column, $this->name));
		}

		return $this->db->get_table($table)->filter(array(
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
		return $this->filter(array($this->get_metadata()->get_pk_column_name() => $id))->fetch_row();
	}

	/**
	 * Insert a new row into the table. Return its newly assigned primary key, true or false.
	 * @param array $row
	 * @return integer|boolean
	 */
	public function insert(array $row)
	{
		$empty_row = $this->metadata->get_empty_row();
		$row = array_diff_assoc(array_intersect_key($row, $empty_row), $empty_row);

		$sql = 'INSERT INTO ' . $this->db->escape_column($this->name) . ' ' . $this->db->escape_column(array_keys($row))
			. ' VALUES ' . $this->db->escape_value($row);

		if ($this->db->execute($sql)) {
			return mysqli_insert_id($this->db->get_connection()) ?: true;
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
			$pk = $row[$this->metadata->get_pk_column_name()];
			unset($row[$this->metadata->get_pk_column_name()]);
		}

		$sql = 'UPDATE ' . $this->db->escape_column($this->name) . ' SET ';
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
		$sql = 'DELETE FROM ' . $this->db->escape_column($this->name) . $this->where($where);
		return $this->db->execute($sql)
			? $this->db->get_connection()->affected_rows
			: false;
	}

	/**
	 * Query the table using a WHERE clause.
	 * @param array $where
	 * @return TableQuery
	 */
	public function filter(array $where)
	{
		if (!$where) {
			// No filter. Why filter?
			return $this;
		}
		return new TableQuery($this, $where);
	}

	public function __toString()
	{
		$str = parent::__toString();
		if (!is_string($str)) {
			return (string) $str;
		}
		return $str;
	}
}
