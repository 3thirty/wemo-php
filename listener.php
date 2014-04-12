#!/usr/bin/php
<?

require_once("config.inc.php");
require_once("Wemo.class.php");
require_once("WemoMeta.class.php");

//$ips = WemoMeta::getAllIps();
$debug = new Debug();

// prime the cache
echo "Priming cache\n";
foreach ($ips as $ip){
    echo $ip . "... ";

    $meta = WemoMeta::init($ip);
    $wemo = new Wemo ($ip, true, $meta);

    echo "friendlyname, ";
    $wemo->getFriendlyName();

    echo "state ";
    $wemo->getState();

    echo "[DONE]\n";

    $meta->reset("pendingState");
    $meta->writeToCache();
}

while (true){
    foreach ($ips as $ip){
        if ($ip == "")
            next;

        $meta = WemoMeta::init($ip, true);
        $pendingState = $meta->get("pendingState");
        if ($pendingState != WemoMeta::KEY_NOT_SET){
            echo "setting " . $meta->get("friendlyName") . " (" . $ip . ") to " . $meta->get("pendingState") . "... ";
            $wemo = new Wemo($ip, true, $meta);
            if ($wemo->setBinaryState($pendingState)){
                $meta->reset("pendingState");
                $meta->set("state", $pendingState);
                $meta->writeToCache();
                echo "done\n";
            } else {
                echo "didn't work\n";
            }
        }
        sleep (2);
    }
}

?>
