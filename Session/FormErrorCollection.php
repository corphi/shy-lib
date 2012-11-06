<?php

namespace Shy\Session;



/**
 * A collection of form errors.
 */
class FormErrorCollection
{
	/**
	 * If errors should be removed after rendering them (default: true).
	 * @var boolean
	 */
	public static $remove_errors_after_rendering = true;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Sets the message for a given control.
	 * @param string $control
	 * @param string $text
	 * @return void
	 */
	public function set($control, $text)
	{
		$this->errors[$control] = $text;
	}

	/**
	 * Renders the message for a given control; returns whether it was needed.
	 * @param string $control
	 * @return boolean
	 */
	public function render_for($control)
	{
		if (!isset($this->errors[$control])) {
			return false;
		}
		echo '<p class="warning">' . $this->errors[$control] . '</p>';

		if (self::$remove_errors_after_rendering) {
			unset($this->errors[$control]);
		}
		return true;
	}

	/**
	 * Whether there are error messages.
	 * @return boolean
	 */
	public function has_any()
	{
		return (bool) $this->errors;
	}

	public function __sleep()
	{
		return array('errors');
	}
}
