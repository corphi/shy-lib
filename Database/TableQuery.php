<?php

namespace Shy\Database;



/**
 * A query that returns information from a single table.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class TableQuery extends Query
{
	/**
	 * Generate a WHERE clause from the given array.
	 * @param array $where
	 * @return string
	 */
	protected function where(array $where = array())
	{
		foreach ($where as $column => $value) {
			if (is_array($value)) {
				if ($value) {
					$where[$column] = $this->db->escape_column($column) . ' IN ' . $this->db->escape_value($value);
				} else {
					unset($where[$column]);
				}
			} elseif ($value === null) {
				$where[$column] = $this->db->escape_column($column) . ' IS NULL';
			} else {
				$where[$column] = $this->db->escape_column($column) . ' = ' . $this->db->escape_value($value);
			}
		}
		if (!$where) {
			return '';
		}
		return ' WHERE ' . implode(' AND ', $where);
	}

	/**
	 * @var Table
	 */
	protected $table;

	/**
	 * @param Table $table
	 * @param array $where
	 */
	public function __construct(Table $table, array $where = array())
	{
		$this->table = $table;

		parent::__construct(
			$table->get_database(),
			(string) $table
		);

		if ($where) {
			$this->query .= $this->where($where);
		}
	}

	/**
	 * Fetch a single row as result object.
	 * @return Row|null
	 */
	public function fetch_object()
	{
		$data = $this->fetch_row();
		return $data ? new Row($this->table, $data) : null;
	}
}
