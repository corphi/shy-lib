<?php

namespace Shy\Forms;



/**
 * A form of widgets.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Form extends Widget implements \ArrayAccess
{
	/**
	 * @param string $localname
	 */
	public function __construct($localname = null)
	{
		parent::__construct($localname);
	}

	/**
	 * @var array
	 */
	protected $widgets;
	/**
	 * @param Widget $widget
	 * @return self
	 */
	public function add(Widget $widget)
	{
		$this->widgets[$widget->localname] = $widget;
		$widget->parent = $this;
		return $this;
	}

	public function check()
	{
		$ok = true;
		foreach ($this->widgets as $widget) {
			if (!$widget->check()) {
				$ok = false;
			}
		}
		return $ok;
	}

	public function offsetExists($offset)
	{
		return isset($this->widgets[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->widgets[$offset];
	}
	public function offsetSet($offset, $value)
	{
		if ($offset !== null) {
			throw new \BadMethodCallException('You cannot set widgets directly. Use ::add() instead.');
		}
		$this->add($value);
	}
	public function offsetUnset($offset)
	{
		if (isset($this->widgets[$offset])) {
			$this->widgets[$offset]->parent = null;
			unset($this->widgets[$offset]);
		}
	}
}
