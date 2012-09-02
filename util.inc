<?php

namespace Shy;



/**
 * Sucht einen Multi-Index aus einem Array heraus.
 * @param array $arr
 * @param mixed $index
 * @return mixed
 */
function &reference_at(array $arr, $index)
{
	if (!is_array($index)) {
		return $arr[$index];
	}

	do {
		$arr = &$arr[array_pop($index)];
	} while (!is_null($index));

	return $arr;
}
/**
 * Durchsucht ein Array rekursiv nach einem Wert und liefert den mehrdimensionalen Index als Array zurück, oder false. Der zurückgegebene Index ist von innen nach außen sortiert.
 * @param array $haystack
 * @param mixed $needle
 * @return array
 */
function array_find_recursive_strict(array &$haystack, $needle)
{
	foreach ($haystack as $k => &$v) {
		if ($v === $needle) {
			return array($k);
		}
		if (is_array($v)
			&& $match = array_find_recursive_strict($v, $needle)
		) {
			$match[] = $k;
			return $match;
		}
	}
	return false;
}


/**
 * Return the type or the class name of the given parameter.
 * @param $var mixed
 * @return string
 */
function gettype_ex($var)
{
	return is_object($var) ? get_class($var) : gettype($var);
}


/**
 * Sendet die HTTP-Kopfdaten und die ggf. die XML-Deklaration.
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
 * Prüft, ob es sich um eine gültige E-Mail-Adresse handelt.
 * @param $string string
 * @return bool
 */
function is_valid_email($string)
{
	// Eigentlich keine echte Begrenzung, sie steht nur so in der Datenbank.
	if (!is_string($string) || mb_strlen($string, 'UTF-8') >= 256) {
		return false;
	}

	$atIndex = strrpos($string, '@');
	if (is_bool($atIndex) && !$atIndex) {
		return false; // kein @
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

	// Die Funktion gibt es unter Windows erst ab PHP 5.2
	if (function_exists('checkdnsrr')) {
		if (!(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))) {
			// Im Offlinemodus unbedingt auskommentieren, da DNS-Einträge dann nicht abgefragt werden können
			// domain not found in DNS
			return false;
		}
	}

	return true;
}

/**
 * Prüft, ob das Argument ausschließlich aus Ziffern besteht, also nichtnegative Ganzzahl ist.
 * @param $str mixed
 * @return bool
 */
function ctype_digit_ex($str)
{
	return (is_string($str) || is_int($str) || is_float($str)) && ctype_digit((string) $str);
}

/**
 * Macht aus einem Ding einen csv-String mit positiven Ganzzahlen; oder false.
 * @param $ids mixed
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
 * Abkürzung für Faule: htmlspecialchars.
 * @param string $str
 * @return string
 */
function h($str)
{
	return htmlspecialchars($str);
}
/**
 * Encoding für Dummies: nl2br ∘ htmlspecialchars, für mehrzeilige Ausgaben.
 * @param string $str
 * @return string
 */
function h2($str)
{
	return nl2br(htmlspecialchars($str));
}

/**
 * Liefert eine Position in einem Array zurück.
 */
function arr_at($arr, $at)
{
	return $arr[$at];
}

/**
 * Liefert '0' zurück, wenn der Parameter leer ist, sonst den Parameter.
 * @param mixed $arg
 * @return mixed
 */
function zero_if_empty($arg)
{
	return empty($arg) ? '0' : $arg;
}


/**
 * Sendet eine E-Mail, die als UTF-8 kodiert ist.
 * @param $to string
 * @param $subject string
 * @param $message string
 * @param $header string
 * @return bool
 */
function mail_utf8($to, $subject = '(Ohne Betreff)', $message = '',
	$header = '', $from = null)
{
	if (is_null($from)) {
		$from = 'Seitenweit-Katalog <katalog@seitenweit.pfadfinden.de>';
	}
	$header_ = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: $from";
	return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $header_ . $header);
}


/**
 * Generiert ein neues, hinreichend zufällig aussehendes Kennwort.
 * Gleiche Startwerte liefern gleiche Kennwörter.
 * @param $startwert mixed
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
 * @param bool $host_only
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
 * @param $base string
 * @param $path string
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
 */
function data_uri_from_string($string, $mimetype = 'text/plain')
{
	return 'data:' . $mimetype . ',' . urlencode($string);
}


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

/**
 * Read and cache a file via readfile().
 *
 * It will be cached automaticallyCache it, read it from cache if it’s new or can’t be read.
 * Files should only be returned if they are sufficiently small.
 * @param string $url
 * @param string $extension
 * @param boolean $return
 * @return string|integer|boolean
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
