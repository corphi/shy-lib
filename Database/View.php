<?php

namespace Shy\Database;



/**
 * A view from the database. Not a quite table but close.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class View extends Query
{
	public function __construct(Database $db, $name)
	{
		$this->name = $name;
		$this->escaped_name = $db->escape_column($name);

		parent::__construct($db, 'SELECT * FROM ' . $this->escaped_name);
	}

	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @var string
	 */
	protected $escaped_name;
	/**
	 * Return the name of the view or table.
	 * @return string
	 */
	public function get_name()
	{
		return $this->name;
	}

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
	 * Query the table using a filter (i.e. a WHERE clause).
	 * @param array $where
	 * @return Query
	 */
	public function filter(array $where = array())
	{
		if (!$where) {
			// No filter. Why filter?
			return $this;
		}
		return $this->db->query($this->query . $this->where($where));
	}

	/**
	 * Describe the table.
	 * @return array
	 */
	public function describe()
	{
		return $this->db->query('DESCRIBE TABLE ' . $this->escaped_name)->fetch_array();
	}
}
