<?php

namespace Shy;



/**
 * An Iterator boilerplate that makes implementations cause less pain.
 * No keys. No rewind.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
abstract class LazyIterator implements \Iterator
{
	protected $value;
	public function current()
	{
		return $this->value;
	}
	public function key()
	{
		return 0;
	}
	public function rewind()
	{
	}
	public function valid()
	{
		return $this->value !== null;
	}
}
