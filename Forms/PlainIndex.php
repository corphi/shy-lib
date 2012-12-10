<?php

namespace Shy\Forms;



/**
 * An ordinary array index.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class PlainIndex implements Index
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = (string) $name;
	}

	public function lookup(array $in)
	{
		return $in[$this->name];
	}

	public function get_html_name()
	{
		return $this->name;
	}
}
