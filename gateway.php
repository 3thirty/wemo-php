#!/usr/bin/php
<?

/**
 * Commandline interface for wemo
 */

require_once("config.inc.php");
require_once("Wemo.class.php");

// parse commandline parameters
$debug = false;
$skipCache = false;
$ip = null;

foreach ($_SERVER["argv"] as $arg){
    if ($arg == "-v")
        $debug = true;
    elseif ($arg == "-f")
        $skipCache = true;
    elseif (is_callable("Wemo", $arg))
        $function = $arg;
    elseif (preg_match("/[0-9]{1-3}\.[0-9]{1-3}\.[0-9]{1-3}\.[0-9]{1-3}/", $arg) !== FALSE)
        $ip = $arg;
    else
        echo "Unrecognized parameter: " . $arg . "\n";
}

if ($ip !== null){
    $device = new Wemo($ip, $debug, $skipCache);
    $ret = $device->{$function}();
} else {
    foreach ($ips as $ip){
        $device = new Wemo($ip, $debug, WemoMeta::init($ip, true));
        $ret = $device->{$function}();
        echo $ip . "\t" . $ret . "\n";
    }
}

?>
