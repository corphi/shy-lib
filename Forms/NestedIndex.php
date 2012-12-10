<?php

namespace Shy\Forms;



/**
 * A nested array index.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class NestedIndex implements Index
{
	/**
	 * @var array
	 */
	protected $index;

	/**
	 * The array shouldn’t contain false values.
	 * @param array $index
	 */
	public function __construct(array $index)
	{
		if (!$index) {
			throw new \Exception('The index may not be empty.');
		}
		$this->index = $index;
	}

	public function lookup(array $in)
	{
		$arr = &$in;
		foreach ($this->index as $index) {
			$arr = &$arr[$index];
		}
		return $arr;
	}

	public function get_html_name()
	{
		$name = reset($this->index);
		while (($part = next($this->index)) !== false) {
			$name .= '[' . $part . ']';
		}
		return $name;
	}
}
