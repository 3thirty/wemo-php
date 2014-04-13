<?

class Debug {
    private $fh;    // file handle to write errors to
    private $on = true;
    const ERROR_LOG = "/tmp/log";

    private function openFileHandle (){
        if (!is_resource($this->fh))
            $this->fh = fopen (self::ERROR_LOG, "a");
    }

    private function closeFileHandle (){
        if (!is_resource($this->fh))
            $this->fh = fclose (self::ERROR_LOG);
    }

    /**
     * Constructor. Open file handle to write data to
     *
     * @param   on  If set to true, enable debugging. If false, disable debugging
     */
    public function __construct (
        $on = true
    ){
        if ($on !== true){
            $this->on = false;
            return;
        }
        $this->log("Debug::__construct()");

        $this->openFileHandle();
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

        if (is_string($data)){
            $out = $data . "\n";
        } else {
            ob_start();
            var_dump($data);
            $out = ob_get_contents();
            ob_end_clean();
        }

        $this->openFileHandle();
        fwrite($this->fh, $out);
        $this->closeFileHandle();
    }
}

?>
