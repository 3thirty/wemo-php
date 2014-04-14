<?

/**
 * Generic wemo metadata cache stub class
 * 
 * This does nothing, but can be used to disable caching
 */
class Cache {
    public static function get(
        $key
    ){
        return false;
    }

    public static function getAll(){
        return false;
    }

    public static function set(
        $key,
        $value
    ){
        return false;
    }
}

/**
 * A class to handle caching of values
 *
 * Designed to use a keyvalue store (redis, memcache), but will use a flatfile for now
 */
class FlatFileCache {
    const CACHE_FILE = "/tmp/wemo-cache";
    const DELIMITER = "\t";

    public static function createCacheFile (){
        if (!file_exists(self::CACHE_FILE))
            touch(self::CACHE_FILE);
    }

    /**
     * Get a value for the specified key
     * @param   key     The key to get the value for
     * @return  The value stored in the cache for key
     */
    public static function get(
        $key
    ){
        //echo "FlatFileCache::get($key)\n";
        self::createCacheFile();

        foreach (file(self::CACHE_FILE) as $line){
            $pieces = explode(self::DELIMITER, $line);
            $cacheKey = $pieces[0];
            $cacheValue = $pieces[1];

            if ($cacheKey == $key){
                //echo "Cache hit for " . $key . "\n";
                return $cacheValue;
            }
        }

        return false;
    }

    /**
     * Get a all records from the cache
     * @return  An array with all records in the cache in format array[KEY] = VALUE
     */
    public static function getAll(){
        //echo "FlatFileCache::getAll()\n";
        self::createCacheFile();

        $ret = array();
        foreach (file(self::CACHE_FILE) as $line){
            $pieces = explode(self::DELIMITER, $line);
            $cacheKey = $pieces[0];
            $cacheValue = $pieces[1];

            $ret[$cacheKey] = $cacheValue;
        }

        return $ret;
    }

    /**
     * Set the value for the provided key
     * @param   key     The key to set (must be unique)
     * @param   value   The value to set for key
     *
     * @return  true if the write is successful, false otherwise
     */
    public static function set(
        $key,
        $value
    ){
        $debug = new Debug();
        $debug->log("FlatFileCache::set(" . $key . ", " . $value . ")");

        $out = $key . self::DELIMITER . $value . "\n";

        $readKeys[] = $key;
        foreach (file(self::CACHE_FILE) as $line){
            $pieces = explode(self::DELIMITER, $line);
            $cacheKey = $pieces[0];
            $cacheValue = $pieces[1];

            if (in_array($cacheKey, $readKeys)){
                continue;
            } else {
                $out .= $line;
                $readKeys[] = $cacheKey;
            }
        }

        $debug->log(array("writing to cache:", $out));
        $fh = fopen(self::CACHE_FILE, "w");
        flock($fh, LOCK_EX);
        $res = fwrite($fh, $out);
        flock($fh, LOCK_UN);

        if ($res > 0)
            return true;
        else
            return false;
    }
}

class MemcachedCache {
    const SERVER = "localhost";
    const HOST = 11211;

    private static function connect(){
        $memcache = new Memcache;

        if ($memcache->connect(self::SERVER, self::HOST))
            return $memcache;
        else
            return false;
    }

    public static function get(
        $key
    ){
        if ($memcache = self::connect())
            return $memcache->get($key);
    }

    public static function getAll(){
        foreach ($ips as $ip)   // use config IPs
            $ret[$ip] = self::get($ip);

        return $ret;
    }

    public static function set(
        $key,
        $value
    ){
        if ($memcache = self::connect())
            return $memcache->set($key, $value);
    }
}

?>
