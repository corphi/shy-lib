<?php

namespace Shy\Database;



/**
 * An entry row in a database table.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Row implements \ArrayAccess, \IteratorAggregate
{
	/**
	 * @var Table
	 */
	protected $table;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * Create a new row for the given table. The data isn’t checked at all - you have to do it yourself.
	 * @param Table $table
	 * @param array $data
	 */
	public function __construct(Table $table, array $data)
	{
		$this->table = $table;
		$this->data = $data;
	}

	/**
	 * Return the primary key value of this row.
	 * @return string
	 */
	public function get_id()
	{
		return $this->data[$this->table->get_metadata()->get_pk_column_name()];
	}

	/**
	 * Get references to other tables.
	 * @param Table|string $table
	 * @param string $column
	 * @return Query
	 */
	public function ref($table, $column = null)
	{
		if ($table instanceof Table) {
			$table = $table->get_name();
		}
		return $this->table->references($this->data, $table, $column);
	}

	/**
	 * Get references from other tables.
	 * @param Table|string $table
	 * @param string $column
	 * @return Query
	 */
	public function ref_by($table, $column = null)
	{
		if ($table instanceof Table) {
			$table = $table->get_name();
		}
		return $this->table->referenced_by($this->data, $table, $column);
	}


	/**
	 * Update a row using values from the given array. Will only send changes for existing indices.
	 * @param array $new_data
	 * @return boolean
	 */
	public function update(array $new_data)
	{
		$new_data = array_intersect_key($new_data, $this->data);
		if ($this->table->update(
				array_diff_assoc($new_data, $this->data),
				$this->data[$this->table->get_metadata()->get_pk_column_name()]
		)) {
			$this->data = $new_data + $this->data;
			return true;
		}
		return false;
	}


	/**
	 * @param string $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
	/**
	 * @param string $offset
	 * @return string
	 */
	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}
	/**
	 * @param string $offset
	 * @param string $value
	 * @throws DatabaseException
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset !== null && !isset($this->data[$offset])) {
			throw new DatabaseException(__CLASS__ . '::' . __METHOD__ . '(): You cannot add columns to a result.');
		}
		$this->data[$offset] = $value;
	}
	/**
	 * @param string $offset
	 * @throws DatabaseException
	 */
	public function offsetUnset($offset)
	{
		throw new DatabaseException(__CLASS__ . '::' . __METHOD__ . '(): You cannot remove columns from a result.');
	}

	/**
	 * @return \Iterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->data);
	}
}
