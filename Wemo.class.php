<?

require_once("Debug.class.php");
require_once("WemoMeta.class.php");

/**
 * Class to control a wemo device
 */
class Wemo {
    private $ip;
    private $port;
    private $state;
    private $name;
    private $retryCount = 0;

    private $meta;

    private $debug;

    const MIN_PORT = 49152;
    const MAX_PORT = 49155;
    const MAX_RETRIES = 10000;  // try for a LOOONG time
    const RETRY_SLEEP = 2;
    const REQUEST_TIMEOUT = 10; // how long to wait for a response from the
                                // device (in seconds) before trying again

    const DEVICE_FAULT = 98;    // we got a fault response from the device. This
                                // seems like a harder error
    const DEVICE_ERROR = 99;    // we got an error response from the device. This
                                // looks to happen when a device is already in
                                // the requested state

    /**
     * Loop through some known ports that wemo uses and find out which one is
     * active RIGHT NOW
     * 
     * @return the active port numver
     */
    private function findPort (){
        $this->debug->log("findPort()");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->meta->get("port") !== WemoMeta::KEY_NOT_SET)
            $port = $this->meta->get("port");
        else
            $port = self::MIN_PORT - 1;

        while (true){   // hotloop. Should probably add a BIG max timeout here
            if ($port >= self::MAX_PORT){
                $port = self::MIN_PORT;
                sleep(self::RETRY_SLEEP);
            }

            $this->debug->log("trying " . $this->ip . ":" . $port);
            curl_setopt($ch, CURLOPT_URL, "http://" . $this->ip . ":" . $port);

            $res = curl_exec($ch);

            $this->debug->log($res);

            if ($res){
                $this->debug->log("found port " . $port);
                $this->meta->set("port", $port);

                return $port;
            }

            $port++;
        }

        return false;
    }

    private function constructRequest (
        $action,
        $body
    ){
        $data = '<?xml version="1.0" encoding="utf-8"?>'
            . '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<s:Body><u:' . $action . ' xmlns:u="urn:Belkin:service:basicevent:1">' . $body . '</u:' . $action . '></s:Body></s:Envelope>';

        return $data;
    }

    private function getGeneric (
        $action
    ){
        $this->debug->log("getGeneric(" . $action . ")");

        if (strtoupper(substr($action, 0, 3)) == "GET"){
            $tag = substr($action,3);
        } else {
            $tag = $action;
            $action = "Get" . $action;
        }

        $res = $this->sendRequest($action);
        $ret = self::parseResponse($res, $tag);

        return $ret;
    }

    /**
     * Turn the switch on or off
     * @param   on  1 for on, 0 for off
     * @return  true if the state in the response from the wemo matches the
     *          requested state (i.e. the switch worked), false otherwise
     */
    public function setBinaryState (
        $on
    ){
        $this->debug->log ("setBinaryState(" . $on . ")");

        $res = $this->sendRequest("SetBinaryState", "<BinaryState>" . $on . "</BinaryState>");
        $parsedResponse = self::parseResponse($res, "BinaryState");
        $this->debug->log("got response: " . $parsedResponse);
        $ret = ($parsedResponse == $on);

        if ($ret == true || $ret == self::DEVICE_ERROR)
            $this->meta->set("state", $on);

        return $ret;
    }

    /**
     * Make a SOAP request to this wemo device
     *
     * This method will keep trying until it receives a valid SOAP response (up
     * to self::MAX_RETRIES times)
     *
     * @param   action  The action to send. If body is not specified, this will
     *                  also be used to construct the body
     * @param   body    The SOAP body to send
     *
     * @return  The SOAP response from the device, or boolean false on error
     */
    private function sendRequest (
        $action,
        $body = null
    ){
        $this->debug->log("sendRequest()");
        $this->port = $this->findPort();

        $header = "SOAPACTION: \"urn:Belkin:service:basicevent:1#" . $action . "\"";

        if ($body === null)
            $body = "<" . $action . ">0</" . $action. ">";
        $data = $this->constructRequest($action, $body);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_USERAGENT, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, "http://" . $this->ip . ":" . $this->port . "/upnp/control/basicevent1");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array (
            "Accept: ",
            "Content-type: text/xml; charset=\"utf-8\"",
            $header
        ));

        $this->debug->log("Sending: " . $data);

        $ret = curl_exec($ch);

        $this->debug->log("Received: " . $ret);

        if ($ret === false && $this->retries++ < self::MAX_RETRIES){
            sleep(self::RETRY_SLEEP);
            return $this->sendRequest($action, $body);
        }

        return $ret;
    }
    
    /**
     * Strip out the contents of with $tag
     *
     * @param   xml The XML response
     * @param   tag The XML tag to return the contents of
     */
    private static function parseResponse (
        $xml,
        $tag
    ){
        if (strpos($xml, "<faultstring>") !== FALSE)
            return self::DEVICE_FAULT;

        preg_match("/<" . $tag . ">(.*)<\/" . $tag . ">/", $xml, $matches);

        $ret = false;

        if ($matches[1])
            $ret = (string)$matches[1];

        if ($ret == "Error")
            $ret = self::DEVICE_ERROR;

        return $ret;
    }

    public function __construct (
        $ip,
        $debug = false,
        WemoMeta &$meta
    ){
        $this->debug = new Debug($debug);
        $this->meta = $meta;

        $this->debug->log("meta data is:");
        $this->debug->log($this->meta);

        $this->ip = $ip;
    }

    public function on (){
        return $this->setBinaryState(1);
    }

    public function off (){
        return $this->setBinaryState(0);
    }

    public function getSignalStrength (){
        $signalStrength = $this->getGeneric("GetSignalStrength");
        $this->meta->set("signalStrength", $signalStrength);

        return $signalStrength;
    }

    public function getFriendlyName (){
        $friendlyName = $this->getGeneric("GetFriendlyName");
        $this->meta->set("friendlyName", $friendlyName);

        return $friendlyName;
    }

    /**
     * A wrapper for getBinaryState, but returns human-readable state (on or off)
     * @return  The response value from the Device converted to human-readable format (on or off)
     */
    public function getState (){
        $this->debug->log("getState()");
        $res = $this->getBinaryState();

        if ($res == "1")
            return "on";
        else
            return "off";
    }

    /**
     * Get the binary state of the wemo (on or off)
     * @return  The response value from the Device. This is: 0 if the device is off, 1 if the device is on
     */
    public function getBinaryState (){
        $this->debug->log("getBinaryState()");

        $state = $this->getGeneric("GetBinaryState");

        $this->debug->log($state);

        $this->meta->set("state", $state);
        // TODO: this should be handled by the listener
        $this->meta->set("pendingState", null);

        return $state;
    }
}

?>
