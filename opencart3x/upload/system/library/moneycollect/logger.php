<?php
namespace Moneycollect;

class Logger
{
    var $dir_system;
    var $is_logger;
    var $file;
    private $handle;

    function __construct($is_logger = '1'){
        $this->dir_system = DIR_SYSTEM;
        $this->is_logger = $is_logger;
    }

    function addLog($type,$message){
        if( $this->is_logger == '0' ){
            return;
        }
        if( is_array($message) ){
            $message = json_encode($message);
        }

        $message = '['.$type.']: '.$message;

        $this->file = 'log';
        $this->openFile();
        $this->write($message);
    }

    function webhook($message){
        if( $this->is_logger == '0' ){
            return;
        }

        $this->file = 'webhook';
        $this->openFile();
        $this->write($message);
    }

    function addBug($message){
        $this->file = 'bug';
        $this->openFile();
        $this->write($message);
    }

    function openFile(){
        $this->handle = fopen($this->logPath() . $this->file.'-'.date('Y-m-d').'.log', 'a');
    }

    function mkDir(){
        if(!is_dir($this->logPath()) ){
            @mkdir($this->logPath(),0777);
        }
    }

    function logPath(){
        return $this->dir_system.'storage/logs/moneycollect/';
    }

    function write($message){
        if( $this->handle ) {
            fwrite($this->handle, date('Y-m-d H:i:s') . ' - ' . print_r($message, true) . "\n");
            fclose($this->handle);
        }
    }

}
