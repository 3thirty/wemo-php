<?

set_include_path(__DIR__ . "/../");
require_once("WemoMeta.class.php");
require_once("Wemo.class.php");
require_once("Debug.class.php");
require_once("Checkin.class.php");

$debug = new Debug(false);

switch ($_GET["function"]){
    case "get":
        $i = 0;
        foreach (WemoMeta::getAllIps() as $ip){
            $meta = new WemoMeta ($ip, false, $debug);

            $ret[$i]["ip"] = $meta->get("ip");
            $ret[$i]["friendlyName"] = $meta->get("friendlyName");
            $ret[$i]["state"] = $meta->get("state");
            $ret[$i]["pendingState"] = $meta->get("pendingState");

            $i++;
        }

        echo json_encode($ret);
        break;
    case "set":
        $meta = new WemoMeta ($_GET["ip"], true, $debug);
        $meta->set("pendingState", (int) $_GET["state"]);
        echo json_encode("ok");
        break;
    case "reset":
        foreach (WemoMeta::getAllIps() as $ip){
            $meta = new WemoMeta ($ip, true, $debug);
            $meta->set("pendingState", -1);
        }
        echo json_encode("ok");
        break;
    case "checkin":
        if ($_GET["user"] != "")
            Checkin::in($_GET["user"]);
        break;
    case "checkin":
        if ($_GET["user"] != "")
            Checkin::out($_GET["user"]);
        break;
    default:
        echo json_encode("error!");
}

?>
