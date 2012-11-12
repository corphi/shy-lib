<?php

namespace Shy\Database;



/**
* A class to abstract Database transactions.
*
* @author Philipp Cordes
* @license GNU General Public License, version 3
*/
class Transaction
{
	/**
	 * @var Database
	 */
	protected $db;

	public function __construct(Database $db)
	{
		if ($db->execute('START TRANSACTION')) {
			throw new DatabaseException('Couldn’t start transaction.');
		}
		$this->db = $db;
	}

	/**
	 * @return boolean
	 */
	public function commit()
	{
		return $this->db->execute('COMMIT');
	}
	/**
	 * @return boolean
	 */
	public function rollback()
	{
		return $this->db->execute('ROLLBACK');
	}
}
