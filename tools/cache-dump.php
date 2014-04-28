#!/usr/bin/php
<?

/**
 * Utility script to dump out the current contents of the cache (after being
 * loaded into WemoMeta). Useful for debugging
 */

set_include_path(__DIR__ . "/../");
require_once("config.inc.php");
require_once("WemoMeta.class.php");

foreach (WemoMeta::getAllIps() as $ip){
    var_dump(new WemoMeta ($ip, false));
}

?>
