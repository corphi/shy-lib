<?php

namespace Shy\Database;



/**
 * A Literal value to use inside a database statement. Will not be escaped.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Literal
{
	/**
	 * @var string
	 */
	protected $string;
	/**
	 * @param string $string
	 */
	public function __construct($string)
	{
		$this->string = $string;
	}
	public function __toString()
	{
		return $this->string;
	}
}
