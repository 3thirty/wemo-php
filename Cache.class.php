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

    public static function getAll(
        $key
    ){
        return false;
    }

    public static function set(
        $key,
        $value
    ){
        return false;
    }

    public static function getInstance (){
        return self;
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

    public static function getInstance (){
        return self;
    }

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
    public static function getAll(
        $key
    ){
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

    public static function set(
        $key,
        $value
    ){
        //echo "FlatFileCache::set($key, $value)\n";

        self::createCacheFile();

        $out = $key . self::DELIMITER . $value . "\n";

        foreach (file(self::CACHE_FILE) as $line){
            $pieces = explode(self::DELIMITER, $line);
            $cacheKey = $pieces[0];
            $cacheValue = $pieces[1];

            if ($cacheKey == $key)
                continue;
            else
                $out .= $line;
        }

        $res = file_put_contents(self::CACHE_FILE, $out);

        if ($res > 0)
            return true;
        else
            return false;
    }
}

?>
