<?

/**
 * Class to hold details of who is currently in the house
 */
class Checkin {
    const   CACHE_FILE = "/tmp/checkin-cache";

    private static function load (){
        return unserialize(FlatFileCache::get(self::CACHE_FILE, "in"));
    }

    private static function save (){
        return unserialize(FlatFileCache::get(self::CACHE_FILE, "in"));
    }

    public function in (
        $user
    ){
        $in = self::loadCache();
        $in[$user] = true;
        self::save ($in);
    }

    public static function out (
        $user
    ){
        $in = self::loadCache();
        unset ($this->in[$user]);
        self::save ($in);
    }

    public static function anyoneHome (){
        $in = self::loadCache();
        return (count($in) > 0);
    }
}

?>
