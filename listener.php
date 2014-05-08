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

require_once("Wemo.class.php");
require_once("WemoMeta.class.php");
require_once("Debug.class.php");

$debug = new Debug();
$debug->log("listener.php");

$ip = $_SERVER["argv"][1];
if (preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $ip) !== 1){
    echo "Invalid ip address: " . $ip . "\n";
    exit;
}

/**
 * Query the wemo for metadata and record this in the cache
 *
 * @param   ips     An array of IPs or a single IP to query
 * @return  void
 */
function resetCache (
    $ips,
    &$debug
){
    $debug->log("resetCache()");

    if (!is_array($ips))
        $ips = array($ips);

    echo "Priming cache\n";
    foreach ($ips as $ip){
        echo $ip . "... ";

        $meta = new WemoMeta ($ip, true, $debug);
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
resetCache($ip, $debug);

// hotloop. Constantly read the cache for any pendingStates
while (true){
    $meta = new WemoMeta ($ip, false, $debug);
    $pendingState = $meta->get("pendingState");

    if ((int) $pendingState == -1){
        $debug->log("explicitly resetting cache");
        resetCache($ip, $debug);
    } elseif ($pendingState != WemoMeta::KEY_NOT_SET){
        $debug->log("setting "
            . $meta->get("friendlyName") . " (" . $ip . ") to "
            . $meta->get("pendingState") . "... ");

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

        $meta->writeToCache();
    }

    sleep(1);
}

$debug->log("exiting listener.php");

?>
