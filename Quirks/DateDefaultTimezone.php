<?php

namespace Shy\Quirks;



class DateDefaultTimezone
{
	/**
	 * Make sure that date/time settings are valid.
	 */
	public static function quirk()
	{
		$error_reporting = error_reporting(0);
		// Awesome semantics!
		date_default_timezone_set(date_default_timezone_get());
		error_reporting($error_reporting);
	}
}

DateDefaultTimezone::quirk();
