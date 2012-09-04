<?php

namespace Shy\Database;



/**
 *
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Table extends View
{
	protected $columns;
	protected $referenced_by;
	protected $pk_column;

	/**
	 * @param Database $db
	 * @param string $name
	 * @param boolean $load_references
	 */
	public function __construct(Database $db, $name, $load_references = false)
	{
		parent::__construct($db, $name);


		$this->idcol = '';


		$params = array(
			'database' => $db->name(),
			'table' => $name,
		);
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
			'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME'
			. ' FROM information_schema.KEY_COLUMN_USAGE'
			. ' WHERE TABLE_SCHEMA = :database AND REFERENCED_TABLE_SCHEMA = :database AND REFERENCED_TABLE_NAME = :table'
		)->set_params($params)->fetch_custom($fetcher, MYSQLI_USE_RESULT);
		echo '
';
		var_dump($this->referenced_by);
	}

	public function fetch_tree($grpcol, $idcol = null)
	{
		if (!$idcol) {
			$idcol = $this->idcol;
		}
		return parent::fetch_tree($grpcol, $idcol);
	}

	/**
	 * Read references from a row to other rows.
	 * @param array $subject
	 * @param string $table
	 * @param string $property
	 */
	public function references($subject, $table, $property = null)
	{
		return $this->db->table($table)->referenced_by($subject, $this->name, $property);
	}
	/**
	 * Read references to a row from other rows.
	 * @param array $subject
	 * @param string $table
	 * @param string $property
	 * @throws DatabaseException
	 */
	public function referenced_by($subject, $table, $property = null)
	{
		if (!isset($this->referenced_by[$table])) {
			throw new DatabaseException('Table not referenced');
		}
		$references = $this->referenced_by[$table];
		if (!$property) {
			if (count($references) > 1) {
				throw new DatabaseException('Ambiguous property');
			}
			$property = first(array_keys($references));
		} elseif (!isset($references[$property])) {
			throw new DatabaseException('Property not found');
		}

		return $this->db->table($table)->filter(array(
			$property => $subject[$references[$property]],
		));
	}
}
