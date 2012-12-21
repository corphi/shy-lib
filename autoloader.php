<?php

namespace Shy;



/**
 * Try to load a Shy class.
 * @param string $name
 */
function autoloader($name)
{
	if (substr($name, 0, 4) !== 'Shy\\') {
		return false;
	}
	return include(__DIR__ . '/' . str_replace('\\', '/', substr($name, 4)) . '.php');
}

spl_autoload_register('Shy\\autoloader');
