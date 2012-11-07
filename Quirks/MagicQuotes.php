<?php

namespace Shy\Quirks;



/**
 * A quirk to automagically remove Magic Quotes.
 * Query whether it was necessary by calling self::needed().
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class MagicQuotes
{
	public function __construct()
	{
		self::needed();
	}

	/**
	 * Removes Magic Quotes; returns whether it was necessary.
	 * @link http://php.net/security.magicquotes.disabling#example-333 Source and inspiration
	 * @return boolean
	 */
	public static function needed()
	{
		static $retval = null;
		if ($retval !== null) {
			return $retval;
		}

		if (!function_exists('get_magic_quotes_gpc') || !get_magic_quotes_gpc()) {
			return $retval = false;
		}

		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				} else {
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
		return $retval = true;
	}
}
