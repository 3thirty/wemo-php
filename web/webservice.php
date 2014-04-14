<?

set_include_path(__DIR__ . "/../");
require_once("config.inc.php");
require_once("WemoMeta.class.php");
require_once("Wemo.class.php");
require_once("Debug.class.php");

switch ($_GET["function"]){
    case "get":
        $i = 0;
        foreach ($ips as $ip){
            $meta = WemoMeta::init($ip, false, new Debug(false));

            $ret[$i]["ip"] = $meta->get("ip");
            $ret[$i]["friendlyName"] = $meta->get("friendlyName");
            $ret[$i]["state"] = $meta->get("state");
            $ret[$i]["pendingState"] = $meta->get("pendingState");

            $i++;
        }

        echo json_encode($ret);
        break;
    case "set":
        $meta = WemoMeta::init($_GET["ip"], true, new Debug);
        $meta->set("pendingState", (int) $_GET["state"]);
        echo json_encode("ok");
        break;
    case "reset":
        foreach (WemoMeta::getAllIps() as $ip){
            $meta = WemoMeta::init($ip, true, new Debug);
            $meta->set("pendingState", -1);
        }
        echo json_encode("ok");
        break;
    default:
        echo json_encode("error!");
}

?>
