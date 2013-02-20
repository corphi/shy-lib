<?php

namespace Shy\Database\Metadata;

use Shy\Database\Database;



/**
 * Metadata class that queries the database.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class LiveMetadata implements Metadata
{
	/**
	 * @var Database
	 */
	protected $db;

	/**
	 * Create a new instance for the given database.
	 * @param Database $database
	 */
	public function __construct(Database $database)
	{
		$this->db = $database;
	}

	public function get_table_metadata($table_name)
	{
		$type = $this->db
			->query('SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl')
			->set_params(array('db' => $this->db->get_name(), 'tbl' => $table_name))
			->fetch_value();

		if ($type !== 'BASE TABLE') {
			return null;
		}
		return new LiveTableMetadata($this->db, $table_name);
	}
}
