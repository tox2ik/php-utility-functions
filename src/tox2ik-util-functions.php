<?php

/**
 * Convert a submitted checkbox to a boolean.
 */
function checkbox_boolean($parameter) {
    if (is_bool($parameter)) return $parameter;
    if (is_object($parameter)) return count(get_object_vars($parameter)) !== 0;
    if (is_array($parameter)) return count($parameter) !== 0;
    if (is_numeric($parameter)) { return (boolean) $parameter; }
    $p = is_string($parameter) ? strtolower($parameter) : $parameter;

    switch ($p) {
        case 'yes';
        case 'on';
        case 'true';
            return true;
            break;

        case null;
        case 'no';
        case 'off';
        case 'false';
            return false;
            break;
    }
    return false;
}

/**
 * Reindex all values on a key from same array.
 * array_reindex({0: {a.i:x}, 1: {a.i:y}, 2: {a.i:z} }, 'i') => {x: {a.i:x}, y: {a.i:y}, z: {a.i:z} }
*/
function array_reindex($input, $keyName)
{
	$i = null;
	$iso = false;
	$isa = false;
	$out = array();
	foreach ($input as $e) {
		if ($isa or is_array($e)) { $i = $e[$keyName]; $isa = true; }
		if ($iso or is_object($e)) { $i = $e->{$keyName}; $iso = true; }
		$out[$i] = $e;
	}
	return $out;

}
