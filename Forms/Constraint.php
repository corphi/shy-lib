<?php

namespace Shy\Forms;



/**
 * Base interface for validation constraints.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
interface Constraint
{
	/**
	 * Check this constraint against the given value.
	 * @return boolean
	 */
	function check_against($value);

	/**
	 * Attributes on an input element to enforce this constraint on client side.
	 * @return array
	 */
	function get_html_attributes();

	/**
	 * Why this constraint failed.
	 * @return string
	 */
	function get_reason();
}
