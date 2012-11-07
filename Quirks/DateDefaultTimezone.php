<?php

namespace Shy\Quirks;



/**
 * A quirk around lazy server timezone settings.
 * 
 * It makes sure that the default timezone is set appropriately.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class DateDefaultTimezone
{
	public function __construct()
	{
		$error_reporting = error_reporting(0);
		// Awesome semantics!
		date_default_timezone_set(date_default_timezone_get());
		error_reporting($error_reporting);
	}
}
