<?php

namespace Shy\Quirks {


/**
 * A quirk to port http_response_code() to PHP versions below 5.4.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class HttpResponseCode
{
}

}


namespace {

if (!function_exists('http_response_code')) {
	/**
	 * Replacement for the PHP 5.4 function with the same name.
	 * @param integer $new_status
	 * @return boolean
	 */
	function http_response_code($new_status = null)
	{
		static $old_status = null;
		if ($old_status === null) {
			$old_status = isset($_SERVER['REDIRECT_STATUS'])
			? (int) $_SERVER['REDIRECT_STATUS']
			: 200;
		}

		if ($new_status === null) {
			return $old_status;
		}

		header(':', true, (int) $new_status);
		$retval = $old_status;
		$old_status = $new_status;
		return $retval;
	}
}

}
