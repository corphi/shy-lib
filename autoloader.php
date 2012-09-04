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

	$name = substr($name, 3);
	include __DIR__ . str_replace('\\', '/', $name) . '.php';
}

spl_autoload_register('Shy\autoloader');
