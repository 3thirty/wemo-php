<?

// only read from the cache
require_once("config.inc.php");
require_once("WemoMeta.class.php");
require_once("Wemo.class.php");
require_once("Debug.class.php");

if ($_GET["pendingState"] == "1" || $_GET["pendingState"] == "0"){
    // TODO: this should be done via ajax
    $meta = WemoMeta::init($_GET["ip"]);
    $meta->set("pendingState", (int) $_GET["pendingState"]);
    $meta->writeToCache();

    header("Location: web.php");
}

$stateToString = array (
    0 => "off",
    1 => "on"
);

foreach ($ips as $ip){
    $meta = WemoMeta::init($ip);

    $state = $meta->get("state");
    if ($state == 1){
        $switch = 0;
    } else {
        $switch = 1;
    }

    echo $ip . " - " . $meta->get("friendlyName") . " is <a href='?ip=$ip&pendingState=$switch'>" . $stateToString[$state];
    if ($meta->get("pendingState") != WemoMeta::KEY_NOT_SET){ 
        echo " -> " . $stateToString[$meta->get("pendingState")];
    }
    echo "</a><br>";
}

?>
