<?

require_once("Cache.class.php");
require_once("Debug.class.php");

/**
 * Class to cache wemo metadata in a flat file
 *
 * This class will be serialized and cached
 */
class WemoMeta {
    private $ip;
    private $port;
    private $friendlyName;
    private $state;
    private $pendingState;  // this is the requested state. This will be NULL once the state has been changed
    private $writeToCache = false;

    private $debug;

    const KEY_NOT_SET = -65000;

    /**
     * Initialize
     *
     * @param   ip              The IP address of the wemo device that we're storing
     *                          data about
     * @param   writeToCache    If true, DO write the contents to the cache on destroy
     *
     * @return  A cached metadata object for this ip, or a new (empty) metadata
     *          object for this ip
     */
    public function __construct (
        $ip,
        $writeToCache = false,
        Debug &$debug = null
    ){
        if (get_class($debug) != "Debug" || $debug == NULL)
            $debug = new Debug();

        $this->ip = $ip;

        // load data from the cache
        $cache = FlatFileCache::get($ip);
        $cachedObject = unserialize($cache);
        if ($cachedObject !== FALSE){
            foreach ($cachedObject as $key => $val){
                $this->$key = $val;
            }
        }

        // overwrite any cached debug or writeToCache values with new ones
        $this->debug = $debug;
        $this->writeToCache = $writeToCache;
    }

    public static function getAllIps (){
        return array_keys(FlatFileCache::getAll());
    }

    /**
     * @deprecated
     */
/*
    public static function init (
        $ip,
        $writeToCache = false,
        Debug &$debug = null
    ){
        return new WemoMeta ($ip, $writeToCache, $debug);
    }
*/

    /**
     * get the value of private member variables
     * @param   key The member variable name
     * @return  the value of $this->{$key}
     */
    public function get (
        $key
    ){
        if (isset($this->$key)){
            $this->debug->log(array("got from meta: " . $key . " = ", $this->$key));
            $ret = $this->$key;
        } else {
            $ret = self::KEY_NOT_SET;
        }

        return $ret;
    }
    
    /**
     * set the value of private member variables
     * @param   key     The member variable name
     * @param   value   The value to set the member variable to
     * @return  true if the member variable exists, false otherwise
     */
    public function set (
        $key,
        $value
    ){
        if (property_exists($this, $key)){
            $this->debug->log("writing to meta: " . $key . " = " . $value);
            $this->$key = $value;
            return true;
        } else {
            $this->debug->log("failed to write to meta: " . $key . " = " . $value);
            return false;
        }
    }

    public function reset (
        $key
    ){
        $this->debug->log("WemoMeta::__reset()");
        unset ($this->$key);
    }

    /**
     * Write the current state to the cache
     */
    public function writeToCache (){
        $this->debug->log("writing to cache: " . $this->serialize());
        FlatFileCache::set($this->ip, $this->serialize());
    }

    /**
     * When this object disappears, write the current state to the cache
     */
     public function __destruct (){
        if ($this->writeToCache == true){
            $this->debug->log("WemoMeta::__destruct()");

            $this->writeToCache();
        }
    }

    /**
     * Serialize this object
     */
    public function serialize (){
        $serializeObject = clone $this;
        unset($serializeObject->debug);
        unset($serializeObject->writeToCache);

        return serialize($serializeObject);
    }
}

?>
