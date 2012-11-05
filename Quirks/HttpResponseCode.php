<?php

namespace Shy\Quirks {

class HttpResponseCode
{
	public static function quirk()
	{
	}
}

}


namespace {

if (!function_exists('http_response_code')) {
	/**
	 * Replacement for the PHP 5.4 function with the same name.
	 * @param int $new_status
	 * @return bool
	 */
	function http_response_code($new_status = null)
	{
		static $old_status = null;
		if (is_null($old_status)) {
			$old_status = isset($_SERVER['REDIRECT_STATUS'])
			? (int) $_SERVER['REDIRECT_STATUS']
			: 200;
		}

		if (is_null($new_status)) {
			return $old_status;
		}

		static $desc = array(
			100 => 'Continue', 'Switching Protocols',
			200 => 'OK', 'Created', 'Accepted', 'Non-Authorative Information',
				'No Content', 'Reset Content', 'Partial Content',
			300 => 'Multiple Choices', 'Moved Permanently', 'Found',
				'See Other', 'Not Modified', 'Use Proxy',
				307 => 'Temporary Redirect',
			400 => 'Bad Request', 'Unauthorized', 'Payment Required',
				'Forbidden', 'Not Found', 'Method Not Allowed',
				'Not Acceptable', 'Proxy Authentication Required',
				'Request Timeout', 'Conflict', 'Gone', 'Length Required',
				'Precondition Failed', 'Request Entity Too Large',
				'Request-URI Too Long', 'Unsupported Media Type',
				'Requested Range Not Satisfiable', 'Expectation Failed',
			500 => 'Internal Server Error', 'Not Implemented', 'Bad Gateway',
				'Service Unavailable', 'Gateway Timeout',
				'HTTP Version Not Supported'
		);

		$new_status = (int) $new_status;
		if (!isset($desc[$new_status])) {
			return false;
		}

		if (isset($_SERVER['FCGI_ROLE'])) {
			header('Status: ' . $new_status);
		} else {
			// Non-FastCGI flavour
			header($_SERVER['SERVER_PROTOCOL'] . " $new_status " . $desc[$new_status]);
		}
		$old_status = $new_status;
		return true;
	}
}

}
