<?php

if (!function_exists('checkbox_boolean')) {
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
}

if (!function_exists('array_reindex')) {
    /**
     * Reindex all values on a key from same array.
     * array_reindex({0: {a.i:x}, 1: {a.i:y}, 2: {a.i:z} }, 'i') => {x: {a.i:x}, y: {a.i:y}, z: {a.i:z} }
     *
     * @See array_column
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
}


if (! function_exists('array_pluck')) { 
    /**
     * extract a single property.
     *
     * This is intended to replace "boring" code like this:
     *
     *    $remoteGroupIds = [];
     *    foreach ($gg->data as $e) {
     *        $remoteGroupIds[$e->code] = $e->id;
     *    }
     *
     * @param array|Traversable $input collection
     * @param string $extractProperty
     * @param null $indexWith us another property as key in the resulting array (or 0, 1, 2, ...)
     * @return array
     *
     */
    function array_pluck($input, $extractProperty = 'id', $indexWith = null) {
        $i = null;
        $iso = false;
        $isa = false;
        $out = array();
        foreach ($input as $e) {
            if ($isa or is_array($e)) { $i = $e[$extractProperty]; $isa = true; }
            if ($iso or is_object($e)) { $i = $e->{$extractProperty}; $iso = true; }
            if ($indexWith) {
                $out[$isa ? $e[$indexWith] : $e->{$indexWith}] = $i;
            } else {
                $out[] = $i;
            }
        }
        return $out;
    }
}

/**
 * Parse variations of ISO8601 strings.
 * @param $isoDate string time and date and timezone
 * @param null $dtZone create date with this timezone. Fall back to default if not specified.
 * @return bool|DateTime false if unable to parse.
 */
function createDateTimeFromIso8601Format($isoDate, $dtZone = null, $additionalFormats = []) {
    static $formats = array(
        'Y-m-d\TH:i:s.uT', // js: (new Date).toISOString()
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s',
        DATE_ATOM,
        \DateTime::ISO8601,
        'Y-m-d\TH:i:s.ue',
        'Y-m-d\TH:i:s.uO',
        'Y-m-d\TH:i:s.uP',
    );
    static $defaultZone;
    if  (is_array($additionalFormats)) {
        $formats = array_merge($formats, $additionalFormats);
    }
    if ($dtZone == null) {
        if ($defaultZone == null) {
            $defaultZone = new \DateTimeZone(date_default_timezone_get());
        }
        $dtZone = $defaultZone;
    }
    $date = false;
    foreach($formats as $fmt ) {
        $clearstring = trim(filter_var($isoDate, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));
        $date = \DateTime::createFromFormat($fmt, $clearstring, $dtZone);
        if ($date) {
            break;
        }
    }
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
        if (is_array($e)) { foreach($e as $ii => $ee) expectRequestParameters($ee); }
        else { if (!isset($_REQUEST[$e])) $_REQUEST[$e] = null; }
    }
}


function expectGetParameters() {
    foreach (func_get_args() as $e) {
        if (is_array($e)) { foreach($e as $ii => $ee) expectGetParameters($ee); }
        else { if (!isset($_GET[$e])) $_GET[$e] = null; }
    }
}

function expectPostParameters() {
    foreach (func_get_args() as $e) {
        if (is_array($e)) { foreach($e as $ii => $ee) expectPostParameters($ee); }
        else { if (!isset($_POST[$e])) $_POST[$e] = null; }
    }
}

function expectSessionParameters() {
    foreach (func_get_args() as $e) {
        if (is_array($e)) { foreach($e as $ii => $ee) expectSessionParameters($ee); }
        else { if (!isset($_SESSION[$e])) $_SESSION[$e] = null; }
    }
}


if (! function_exists('jsonDecode')) {
    /**
     * @param string $json
     * @param array $errors populated with errors if detected
     * @param bool $assoc [optional] return as array/dictionary
     * @param int $depth [optional] User specified recursion depth.
     * @param int $options See json_decode (JSON_BIGINT_AS_STRING)
     *
     * @return mixed
     */
    function jsonDecode($json, & $errors = [], $assoc = false, $depth = 512, $options = 0) {
        $result = json_decode($json, $assoc, $depth, $options);
        if (!empty($json) && $result === null && json_last_error() != JSON_ERROR_NONE) {
            error_log(sprintf( '%s: Error while decoding; %s', __FUNCTION__, json_last_error_msg()));
            $errors[] = json_last_error_msg();
        }
        return $result;
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



function assert2($assertion, $expectation) {
    if (version_compare(phpversion(), '5.4.8', '>=')) {
        return assert($assertion, $expectation);
    }
    return assert($assertion);
}

/**
 * Execute an OS command and write to error log if it is not happy.
 * @param $shellCommand string is run as is. You can add stderr (2>&1) redirection if needed.
 * @param $reportIfSuccess boolean write to the log even if exit status is 0.
 */
function execWithReport($shellCommand, $reportIfSuccess = false) {

    $output = array();
    exec($shellCommand, $output, $exitCode);
    if ($exitCode != 0 || $reportIfSuccess) {
        $outLines = implode("\n", $output);
        $failed = $exitCode == 0 ? '' : ' failed';
        $out = "Command$failed: $shellCommand";
        if ($outLines) {
            $out .= "\nOutput;\n$outLines";
        }
        error_log($out);
    }
    return implode("\n", $output);
}

/**
 * replace the first occurance of $needle with $replacement in $haystack
 * @return string replaced or original string.
 */
if (! function_exists('str_replace_first')) {
    function str_replace_first($replacement, $needle, $haystack) {
        return (($pos = strpos($haystack, $needle)) === false)
            ? $haystack
            : substr_replace($haystack, $replacement, $pos, strlen($needle));
    }
}

/**
 * Parse ini bytes / kilobytes / gigabytes
 * @return int
 */
function ini_size_as_bytes($ini_v) {
   $ini_v = trim($ini_v);
   $s = [ /* 'y' => 1<<80, 'z' => 1<<70, */
       'e' => 1<<60, 'p' => 1<<50, 't' => 1<<40,
       'g' => 1<<30, 'm' => 1<<20, 'k' => 1<<10, 'b' => 1<<0
   ];
   $mul = isset($s[strtolower(substr($ini_v, -1))]) ? $s[strtolower(substr($ini_v,-1))] : false;
   return intval($ini_v) * ($mul ?: 1);
}

/**
 * get the actual max files size (as configured)
 * @return int bytes
 */
function ini_get_upload_size() {
    $maxPost = ini_size_as_bytes(ini_get('post_max_size'));
    $maxUpload = ini_size_as_bytes(ini_get('upload_max_filesize'));
    return min($maxPost, $maxUpload) ?: max($maxUpload, $maxPost);
}


/**
 * @param string $path e.g /foo/bar
 * @param bool $protocolrelative exclude https*:
 * @return string the name of this host.
 *
 */
function serverName($path, $protocolrelative = false) {
    return serverProtocolHost($protocolrelative) . $path;
}

/**
 * tested on Apache
 *
 * @param bool $protocolRelative exclude https*: from the url
 * @return string e.g. http://tidsrapportering.no
 */
function serverProtocolHost($protocolRelative = false) {
    $p = null;
    switch (@$_SESSION['SERVER_SOFTWARE']) {
        case 'Apache': $p = @$_SERVER['REQUEST_SCHEME']; break;
        case 'LiteSpeed': $p = substr($_SERVER['SCRIPT_URI'], 0, strpos($_SERVER['SCRIPT_URI'], ':')); break;
    }

    $p = isset($p) ? $p : 'http';
    $host = @$_SERVER['HTTP_HOST'];
    $proto = $protocolRelative ? '//' : "$p://";
    return "$proto$host";
}

/**
 * @param mixed $e any object.
 * @param Callable $cb invoke for each leaf. signature is ($currentElement, $index, $parentObject) (similar to js:Array.foreach())
 * @param mixed $i index/property name of $e in the parent structure (node above)
 * @param null $parent
 */
function traverseRecursively($e, $cb, $i = null, $parent=null) {
    $children = [];
    if (is_scalar($e)) {
        $cb($e, $i, $parent);
    }
    elseif (is_array($e)) {
        $children = $e;
    }
    elseif (is_object($e)) {
        $children = get_object_vars($e);
    }

    foreach ($children as $i => $ee) {
        traverseRecursively($ee, $cb, $i, $e);
    }
}


if (! function_exists('startsWith')) {
    function startsWith($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (! function_exists('endsWith')) {
    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }
}

if (! function_exists('pbin')) {
    function pbin($v) {
        return str_pad(decbin($v), 32, 0, STR_PAD_LEFT);
    }
}
