<?php

namespace Shy\Forms;



/**
 * A constraint to enforce an email address.
 * If the field is supposed to be required, you need an additional constraint.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class EmailConstraint implements Constraint
{
	public function check_against($value)
	{
		return ($value != '' && $this->optional)
			|| \Shy\is_valid_email($value);
	}

	public function get_html_attributes()
	{
		return array('type' => 'email');
	}

	public function get_reason()
	{
		return 'That’s not an email address.';
	}
}
