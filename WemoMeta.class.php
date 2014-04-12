<?

require_once("Cache.class.php");

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
    private $skipCache = false;     // do not write to cache

    const KEY_NOT_SET = -65000;

    /**
     * See init()
     */
    private function __construct (
        $ip,
        $skipCache
    ){
        $this->ip = $ip;
        $this->skipCache = $skipCache;
    }

    public static function getAllIps (){
        return array_keys(FlatFileCache::getAll());
    }

    /**
     * Initialize. Use this instead of the constructor
     * @param   ip          The IP address of the wemo device that we're storing
     *                      data about
     * @param   skipCache   If true, do not WRITE to the cache
     *
     * @return  A cached metadata object for this ip, or a new (empty) metadata
     *          object for this ip
     */
    public static function init (
        $ip,
        $skipCache = false
    ){
        $cache = FlatFileCache::get($ip);
        $cachedObject = unserialize($cache);

        if ($cachedObject !== FALSE){
            $meta = new WemoMeta ($ip, $skipCache);
            foreach ($cachedObject as $key => $val)
                $meta->$key = $val;

            $meta->skipCache = $skipCache;
        } else {
            $meta = new WemoMeta ($ip, $skipCache);
        }

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
            $this->$key = $value;
            return true;
        } else {
            return false;
        }
    }

    public function reset (
        $key
    ){
        unset ($this->$key);
    }

    /**
     * When this object disappears, write the current state to the cache
     */
    public function writeToCache (){
        FlatFileCache::set($this->ip, $this->serialize());
    }

    /**
     * When this object disappears, write the current state to the cache
     */
    public function __destruct (){
        if ($this->skipCache === false){
            $this->writeToCache();
        }
    }

    /**
     * Serialize this object
     */
    public function serialize (){
        $serializeObject = $this;
        return serialize($serializeObject);
    }
}

?>
