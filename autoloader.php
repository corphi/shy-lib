<?php

namespace Shy;



/**
 * Try to load a Shy class.
 * @param string $name
 * @return boolean
 */
function autoloader($name)
{
	if (substr($name, 0, 4) !== 'Shy\\') {
		return false;
	}

	$name = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, substr($name, 3)) . '.php';
	return is_file($name) && include($name);
}

spl_autoload_register('Shy\\autoloader');
