<?php

namespace Shy\Database\Metadata;



/**
 * Base interface for general metadata classes.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
interface Metadata
{
	/**
	 * Get metadata for a table, or null if there is no such table.
	 * @param string $table_name
	 * @return TableMetadata|null
	 */
	function get_table_metadata($table_name);
}
