#!/usr/bin/php
<?

/**
 * Commandline interface for wemo
 *
 * Usage: ./wemo.php [-v] [on|off|getstate] [IP]
 */
set_include_path(__DIR__ . "/../");
require_once("WemoMeta.class.php");
require_once("Wemo.class.php");

// parse commandline parameters
$debug = false;
$ip = null;

$args = $_SERVER["argv"];

array_shift($args);
while ($arg = array_shift($args)){
    if ($arg == "-v")
        $debug = true;
    elseif (is_callable(array("Wemo", $arg)))
        $function = $arg;
    elseif (preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $arg) === 1)
        $ip = $arg;
    else
        echo "Unrecognized parameter: " . $arg . "\n";
}

if ($ip !== null){
    $device = new Wemo($ip, $debug, new WemoMeta ($ip, true, new Debug($debug, "php://stderr")));
    echo $device->{$function}();
    echo "\n";
} else {
    foreach (WemoMeta::getAllIps() as $ip){
        $device = new Wemo($ip, $debug, new WemoMeta ($ip, true, new Debug ($debug, "php://stderr")));
        $ret = $device->{$function}();
        echo $ip . "\t" . $ret . "\n";
    }
}

?>
