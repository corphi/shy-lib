<?php

namespace Shy\Forms;



/**
 * A constraint that limits allowed values to a list.
 * If the field is supposed to be required, you need an additional constraint.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class EnumConstraint implements Constraint
{
	/**
	 * The list of allowed values.
	 * @var array
	 */
	protected $values;

	/**
	 * Create a new EnumConstraint from a list of allowed values.
	 * @param array $values Said list.
	 */
	public function __construct(array $values)
	{
		$this->values = array_flip($values);
	}

	public function check_against($value)
	{
		return $value == '' || isset($this->values[$value]);
	}

	public function get_reason()
	{
		return 'That’s not an allowed value.';
	}
}
