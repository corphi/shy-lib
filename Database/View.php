<?php

namespace Shy\Database;



/**
 * A view from the database. Not a table but quite.
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

		$sql = $this->query;
		foreach ($where as $column => $value) {
			$where[$column] = $this->db->escape_column($column) . ' = ' . $this->db->escape_value($value);
		}
		$sql .= ' WHERE' . implode(' AND ', $where);

		return $this->db->query($sql);
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
