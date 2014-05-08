<?

class Debug {
    private $fh;    // file handle to write errors to
    private $on = true;
    private $logFile;

    private function openFileHandle (){
        if (!is_resource($this->fh))
            $this->fh = fopen ($this->logFile, "a");
    }

    private function closeFileHandle (){
        if (!is_resource($this->fh))
            $this->fh = fclose ($this->logFile);
    }

    /**
     * Constructor. Open file handle to write data to
     *
     * @param   on  If set to true, enable debugging. If false, disable debugging
     */
    public function __construct (
        $on = true,
        $logFile = "/tmp/log"
    ){
        date_default_timezone_set("America/Los_Angeles");

        if ($on !== true){
            $this->on = false;
            return;
        }
        $this->logFile = $logFile;
        $this->log("Debug::__construct()");
    }

    public function __destruct (){
        $this->log("Debug::__destruct()");
        if ($this->on)
            fclose ($this->fh);
    }

    /**
     * Write a log message
     * @param   data    The data to write out. If a string, just write out,
     *                  otherwise, var_dump it
     * @return  void
     */
    public function log (
        $data
    ){
        if ($this->on === false)
            return;

        if (!is_array($data))
            $data = array($data);

        $out = "";
        foreach ($data as $d){
            if (is_string($d)){
                $out .= $d;
            } else {
                ob_start();
                var_dump($d);
                $out .= ob_get_contents();
                ob_end_clean();
            }
        }

        $out = rtrim ($out);
        $out = date("Y-m-d H:i:s") . "\t" . $out . "\n";

        $this->openFileHandle();
        fwrite($this->fh, $out);
        $this->closeFileHandle();
    }
}

/**
 * Standalone function to provide stack traces. Not really used much, but
 * useful for diagnosing specific issues
 */
function stacktrace (
	$errno = null,
	$errstr = null,
	$errfile = null,
	$errline = null
){
    echo "stacktrace:\n";
	var_dump(debug_backtrace());
}

?>
