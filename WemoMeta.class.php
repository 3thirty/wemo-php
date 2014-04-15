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
     * See init()
     */
    private function __construct (
        $ip
    ){
        $this->ip = $ip;
    }

    public static function getAllIps (){
        return array_keys(FlatFileCache::getAll());
    }

    /**
     * Initialize. Use this instead of the constructor
     * @param   ip              The IP address of the wemo device that we're storing
     *                          data about
     * @param   writeToCache    If true, DO write the contents to the cache on destroy
     *
     * @return  A cached metadata object for this ip, or a new (empty) metadata
     *          object for this ip
     */
    public static function init (
        $ip,
        $writeToCache = false,
        Debug &$debug = null
    ){
        $cache = FlatFileCache::get($ip);
        $cachedObject = unserialize($cache);

        if ($cachedObject !== FALSE){
            $meta = new WemoMeta ($ip);
            foreach ($cachedObject as $key => $val)
                $meta->$key = $val;
        } else {
            $meta = new WemoMeta ($ip);
        }

        if ($debug == null)
            $debug = new Debug();
        
        $meta->debug = $debug;
        $meta->writeToCache = $writeToCache;

        return $meta;
    }

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
     * When this object disappears, write the current state to the cache
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
        $serializeObject = $this;
        unset($serializeObject->debug);
        unset($serializeObject->writeToCache);
        return serialize($serializeObject);
    }
}

?>
