<?php

namespace Shy\Database\Metadata;



/**
 * A interface describing methods to query table metadata.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
interface TableMetadata
{
	/**
	 * Return an array describing the table (like DESCRIBE).
	 * @return array
	 */
	public function describe();

	/**
	 * Get the name of the primary key column.
	 * @return string
	 */
	function get_pk_column_name();

	/**
	 * Get a new row containing default values for the given database table.
	 * @return array
	 */
	function get_empty_row();

	/**
	 * Return which tables (and columns) reference this table (and column) as a two-dimensional array.
	 * $array['table']['column'] = 'referenced_column'
	 * @return array
	 */
	function get_referenced_by();
}
