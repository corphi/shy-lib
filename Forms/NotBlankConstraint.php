<?php

namespace Shy\Forms;



/**
 * A constraint to enforce a value.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class NotBlankConstraint implements Constraint
{
	public function check_against($value)
	{
		return $value || strlen($value);
	}

	public function get_html_attributes()
	{
		return parent::get_html_attributes() + array('required' => 'required');
	}

	public function get_reason()
	{
		return 'That needs to contain a value.';
	}
}
