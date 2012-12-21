<?php

namespace Shy;



/**
 * Return the type or the class name of the given parameter.
 * @param mixed $var
 * @return string
 */
function gettype_ex($var)
{
	return is_object($var) ? get_class($var) : gettype($var);
}


/**
 * Send an HTTP header and the XML declaration if applicable.
 */
function send_headers()
{
	if (strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false) {
		header('Content-Type: application/xhtml+xml; charset=UTF-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>
';
	} else {
		header('Content-Type: text/html; charset=UTF-8');
	}
}


/**
 * Check whether it’s a valid email address.
 * @param string $string
 * @return bool
 */
function is_valid_email($string)
{
	$atIndex = strrpos($string, '@');
	if (is_bool($atIndex) && !$atIndex) {
		return false; // no @
	}

	$domain = substr($string, $atIndex + 1);
	$local = substr($string, 0, $atIndex);
	$localLen = strlen($local);
	$domainLen = strlen($domain);

	if ($localLen == 0 || $localLen > 64) {
		// local part length exceeded
		return false;
	}
	if ($domainLen == 0 || $domainLen > 255) {
		// domain part length exceeded
		return false;
	}
	if ($local[0] == '.' || $local[$localLen-1] == '.') {
		// local part starts or ends with ‘.’
		return false;
	}
	if (strpos($local, '..') !== false) {
		// local part has 2 consecutive dots
		return false;
	}
	if (strpos($domain, '..') !== false) {
		// domain part has 2 consecutive dots
		return false;
	}
	if (!preg_match('/^[A-Za-z0-9\-.]+$/', $domain)) {
		// character not valid in domain part
		return false;
	}
	$local = str_replace("\\\\", '', $local);
	if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', $local)) {
		// character not valid in local part unless local part is quoted
		if (!preg_match('/^"(\\\\"|[^"])+"$/', $local)) {
			return false;
		}
	}

	// Needs PHP 5.2 on Windows systems
	if (function_exists('checkdnsrr')) {
		if (!(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))) {
			// domain not found in DNS
			return false;
		}
	}

	return true;
}

/**
 * Prüft, ob das Argument ausschließlich aus Ziffern besteht, also nichtnegative Ganzzahl ist.
 * @param mixed $str
 * @return boolean
 */
function ctype_digit_ex($str)
{
	return (is_string($str) || is_int($str) || is_float($str)) && ctype_digit((string) $str);
}

/**
 * Macht aus einem Ding einen csv-String mit positiven Ganzzahlen; oder false.
 * @param mixed $ids
 * @return string
 */
function implode_ids($ids)
{
	if (is_array($ids)) {
		foreach ($ids as $k => $v) {
			if (!ctype_digit_ex($v)) {
				unset($ids[$k]);
			}
		}
	} elseif (!ctype_digit_ex($ids)) {
		return false;
	}

	if (empty($ids)) {
		return false;
	}

	return is_array($ids) ? implode(', ', $ids) : $ids;
}

/**
 * Return a position from an array.
 * @param array $arr
 * @param string|integer $at
 * @return mixed
 */
function arr_at($arr, $at)
{
	return $arr[$at];
}

/**
 * Return '0' if the parameter is empty; the parameter otherwise.
 * @param mixed $arg
 * @return mixed
 */
function zero_if_empty($arg)
{
	return empty($arg) ? '0' : $arg;
}


/**
 * Send an email encoded as UTF-8.
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $header
 * @param string $from
 * @return boolean
 */
function mail_utf8($to, $subject = '(No subject)', $message = '',
	$header = '', $from = null)
{
	if (is_null($from)) {
		$from = 'webmaster@' . $_SERVER['SERVER_NAME'];
	}
	$header_ = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: $from";
	return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $header_ . $header);
}


/**
 * Generiert ein neues, hinreichend zufällig aussehendes Kennwort.
 * Gleiche Startwerte liefern gleiche Kennwörter.
 * @param string $startwert
 * @return string
 */
function kennwort_generieren($startwert = null)
{
	if (is_null($startwert)) {
		return substr(base64_encode(sha1(time(), true)), 3, 10);
	}
	return substr(base64_encode(sha1($startwert, true)), 3, 10);
}


/**
 * Determines the current url and returns it.
 * @param boolean $host_only
 * @return string
 */
function current_url($host_only = false)
{
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$here = 'https://' . $_SERVER['HTTP_HOST'];
		if ($_SERVER['SERVER_PORT'] != '443') {
			$here .= ':' . $_SERVER['SERVER_PORT'];
		}
	} else {
		$here = 'http://' . $_SERVER['HTTP_HOST'];
		if ($_SERVER['SERVER_PORT'] != '80') {
			$here .= ':' . $_SERVER['SERVER_PORT'];
		}
	}
	if (!$host_only) {
		$here .= $_SERVER['REQUEST_URI'];
	}
	return $here;
}


/**
 * Resolve an address relative to another.
 *
 * Based on the buggy resolve_href() from http://php.net/realpath.
 * Can’t do paths that start with “//” (Windows shares).
 * @param string $base
 * @param string $path
 * @return string
 */
function resolve_href($base, $href)
{
	if (!$href) {
		// Don’t change
		return $base;
	}

	$href_parsed = parse_url($href);
	if (isset($href_parsed['scheme'])) {
		// Already is an absolute address
		return $href;
	}

	$base_parsed = parse_url("$base ");
	if (!isset($base_parsed['path'])) {
		// Make sure there’s a path (use “/” if it’s missing)
		$base_parsed['path'] = '/';
	}

	$path = $href[0] === '/'
		? $href // swap path…
		: dirname($base_parsed['path']) . "/$href"; // …or append it

	$path = str_replace('/./', '/', $path);

	// Resolve “..”
	$parts = array();
	foreach (explode('/', preg_replace('@/+@', '/', $path)) as $part) {
		if ($part === '..') {
			array_pop($parts);
		} else {
			$parts[] = $part;
		}
	}

	if (isset($base_parsed['scheme'])) {
		return $base_parsed['scheme'] . '://' . $base_parsed['host'] . implode('/', $parts);
	}

	return implode('/', $parts);
}


/**
 * Wraps a string into a data URI.
 * @param string $string
 * @param string $type
 * @return string
 */
function data_uri_from_string($string, $mimetype = 'text/plain')
{
	return 'data:' . $mimetype . ',' . urlencode($string);
}


/**
 * Read and cache a file via readfile().
 *
 * Cache it, read it from cache if it’s new or can’t be read.
 * Files should only be returned if they are sufficiently small.
 * @param string $url
 * @param string $extension
 * @param boolean $return
 * @return string|integer|false
 */
function read_or_cache($url, $extension = null, $return = false)
{
	$tmp_filename = ini_get('session.save_path') . '/' . md5($url) . ( $extension ?: '' );

	// Retrieve file from cache
	if (file_exists($tmp_filename) && time() - filemtime($tmp_filename) < 3600) {
		if ($return) {
			return file_get_contents($tmp_filename);
		}
		return @readfile($tmp_filename);
	}

	// Read and cache it
	if ($return) {
		$contents = @file_get_contents($url);
		if ($contents === false) {
			// Something went wrong
			if (file_exists($tmp_filename)) {
				return file_get_contents($tmp_filename);
			}
			return false;
		}
		file_put_contents($tmp_filename, $contents);
		return $contents;
	}

	// Don’t return
	$file = @fopen($url, 'r');
	if (!$file) {
		if (file_exists($tmp_filename)) {
			// Something went wrong; use cached file instead
			return @readfile($tmp_filename);
		}
		return false;
	}

	// Read in chunks of 16 KiB.
	$len = 0;
	$tmp_file = @fopen($tmp_filename, 'w');
	if ($tmp_file) {
		while (!feof($file) && $str = fread($file, 16384)) {
			$len += strlen($str);
			fwrite($tmp_file, $str);
			echo $str;
		}
		fclose($tmp_file);
	} else {
		while ($str = fread($file, 16384)) {
			$len += strlen($str);
			echo $str;
		}
	}
	fclose($file);
	return $len;
}

/**
 * Sometimes substr() just isn’t enough.
 * @param string $string
 * @param integer $start
 * @param integer $end
 * @return string
 */
function substring($string, $start, $end)
{
	return substr($string, $start, $end - $start);
}

/**
 * Flatten an array.
 * @param array $arr
 * @return array
 */
function array_flatten(array $arr)
{
	$flat = array();
	array_walk_recursive($arr, function ($v) use (&$flat) {
		$flat[] = $v;
	});
	return $flat;
}
