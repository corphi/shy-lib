<?php

namespace Shy\Forms;



/**
 * A form widget.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class Widget
{
	/**
	 * The (relative) name of this widget.
	 * @var string
	 */
	protected $localname;

	/**
	 * @param string $localname
	 */
	public function __construct($localname)
	{
		$this->localname = $localname;
	}

	/**
	 * The parent widget, if any.
	 * @var Widget
	 */
	protected $parent;

	/**
	 * Return the widget’s value from the submitted form.
	 * @return array|string
	 */
	public function &get_value()
	{
		$parent = $this;
		while ($parent->parent) {
			if ($parent->localname) {
				$value = &$this->parent->get_value();
				return $value[$this->localname];
			}
			$parent = $parent->parent;
		}
		return $_POST[$this->localname];
	}

	/**
	 * An array holding the widget’s constraints.
	 * @var array
	 */
	protected $constraints;
	/**
	 * Add constraints to this widgets.
	 * @param Constraint $constraint
	 * @return self
	 */
	public function addConstraint(Constraint $constraint)
	{
		$this->constraints = array_merge($this->constraints, func_get_args());
		return $this;
	}

	/**
	 * Checks this widget’s value against its constraints.
	 * @return boolean
	 */
	public function check()
	{
		$value = $this->get_value();
		$this->failed_constraints = array_filter($this->constraints, function (Constraint $c) use ($value) {
			return !$c->check_against($value);
		});
		return !$this->failed_constraints;
	}

	/**
	 * All constraints that failed during the last call of check().
	 * @var array
	 */
	protected $failed_constraints;
	/**
	 * Render the failed constraints.
	 */
	public function render_failed_constraints()
	{
		if (!isset($this->failed_constraints)) {
			return;
		}
		foreach ($this->failed_constraints as $constraint) {
			echo '<p class="warning">' . $constraint->get_reason() . '</p>';
		}
	}
}
