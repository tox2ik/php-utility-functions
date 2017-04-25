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
function array_reindex($input, $keyName = 'id') {
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


/**
 * Parse variations of ISO8601 strings.
 * @param $isoDate string time and date and timezone
 * @param null $dtZone create date with this timezone. Fall back to default if not specified.
 * @return bool|DateTime false if unable to parse.
 */
function createDateTimeFromIso8601Format($isoDate, $dtZone = null) {
	static $defaultZone;
    if ($dtZone == null) {
        if ($defaultZone == null) {
			$defaultZone = new \DateTimeZone(date_default_timezone_get());
		}
		$dtZone = $defaultZone;
    }
    $format = array(
        'Y-m-d\TH:i:s.uP', // js: (new Date).toISOString()
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s',
        DATE_ATOM,
        \DateTime::ISO8601,
    );

    while (! $date = \DateTime::createFromFormat(array_shift($format), $isoDate, $dtZone)) {}
    return $date;
}


/**
 * Set up the global $_REQUEST to avoid undefined index notices.
 *
 * e.g.
 *
 *     expectRequestParameters('do', 'id', 'action');
 */
function expectRequestParameters() {
    foreach (func_get_args() as $e) {
        if (is_array($e)) { expectRequestParameters($e); }
        else { if (!isset($_REQUEST[$e])) $_REQUEST[$e] = null; }
    }
}

function expectGetParameters() {
    foreach (func_get_args() as $e) {
        if (is_array($e)) { expectGetParameters($e); }
        else { if (!isset($_GET[$e])) $_GET[$e] = null; }
    }
}

function expectPostParameters() {
    foreach (func_get_args() as $e) {
        if (is_array($e)) { expectPostParameters($e); }
        else { if (!isset($_POST[$e])) $_POST[$e] = null; }
    }
}


function jPretty($e) {
    $out = null;
    if (version_compare(phpversion(), '5.4', '>=')) {
        $out = json_encode($e, JSON_PRETTY_PRINT);
    } else {
        $out = str_replace('{', "{\n", json_encode($e));
    }
    return $out;

}


/**
 * Append mtime to a file path or a "local" url
 */
function amtime($url) {
	return \Genja\Caching\RevisionResource::modifyUrlWithMtime($url);
}
