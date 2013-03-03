<?php

namespace Shy\Forms;



/**
 * A constraint to enforce a maximum length.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class MaxLengthConstraint implements Constraint
{
	/**
	 * The maximum length.
	 * @var integer
	 */
	protected $length;

	/**
	 * @param integer $length The maximum length.
	 */
	public function __construct($length)
	{
		$this->length = (integer) $length;
	}

	public function check_against($value)
	{
		return strlen($value) <= $this->length;
	}

	public function get_html_attributes()
	{
		return array('maxlength' => $this->length);
	}

	public function get_reason()
	{
		return 'That’s is too long; only ' . $this->length . ' characters are allowed.';
	}
}
