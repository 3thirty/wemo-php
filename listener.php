#!/usr/bin/php
<?

/**
 * Daemon (sort of) that listens for any changes to pendingState in the meta
 * cache
 *
 * When a change is detected the devices is switched into the pendingState and
 * the pendingState value is unset
 *
 * This should be started in the background, e.g.:
 *      ./listener.php > /dev/null 2>&1 &
 *
 */

require_once("config.inc.php");
require_once("Wemo.class.php");
require_once("WemoMeta.class.php");
require_once("Debug.class.php");

$debug = new Debug();

function resetCache (
    $ips
){
    echo "Priming cache\n";
    foreach ($ips as $ip){
        echo $ip . "... ";

        $meta = WemoMeta::init($ip, true, $debug);
        $wemo = new Wemo($ip, true, $meta);

        echo "friendlyname, ";
        $wemo->getFriendlyName();

        echo "state ";
        $wemo->getState();

        echo "[DONE]\n";

        $meta->reset("pendingState");
    }
}

// prime the cache on startup - this can take a little while
resetCache($ips);

// hotloop. Constantly read the cache for any pendingStates
while (true){
    foreach ($ips as $ip){
        if ($ip == "")
            next;

        $meta = WemoMeta::init($ip, true, $debug);
        $pendingState = $meta->get("pendingState");

        if ((int) $pendingState == -1){
            $debug->log("explicitly resetting cache");
            resetCache($ips);
        } elseif ($pendingState != WemoMeta::KEY_NOT_SET){
            $debug->log("setting " . $meta->get("friendlyName") . " (" . $ip . ") to " . $meta->get("pendingState") . "... ");
            $wemo = new Wemo($ip, true, $meta);
            if ($wemo->setBinaryState($pendingState)){
                $meta->reset("pendingState");
                $meta->set("state", $pendingState);
                $debug->log("successfully switched state for "
                    . $meta->get("friendlyName") . " (" . $ip . ") to "
                    . $meta->get("pendingState"));
            } else {
                $debug->log("failed to switch state for "
                    . $meta->get("friendlyName") . " (" . $ip . ") to "
                    . $meta->get("pendingState"));
            }
        }
    }
}

?>
