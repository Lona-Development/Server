<?php

namespace LonaDB\Functions;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file and LonaFunction class
use LonaDB\LonaDB;
use LonaDB\Functions\LonaFunction;

class FunctionManager{
    //Create all variables
    private LonaDB $LonaDB;
    private array $Functions;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
        //Initialize functions array
        $this->Functions = array();

        //Check if directory "data/functions/" exists, create if it doesn't
        if(!is_dir("data/")) mkdir("data/");
        if(!is_dir("data/functions/")) mkdir("data/functions/");

        //Loop through all files and folders in "data/functions/"
        foreach (new \DirectoryIterator('data/functions') as $fileInfo) {
            //Check if file extension is ".lona"
            if(str_ends_with($fileInfo->getFilename(), ".lona")){
                //Initialize function instance
                $this->Functions[substr($fileInfo->getFilename(), 0, -5)] = new LonaFunction($this->LonaDB, $fileInfo->getFilename());
            }
        }
    }

    public function GetFunction(string $name) : mixed {
        //Check if a function with that name exists
        if(!$this->Functions[$name]) return false;
        //Return function instance
        return $this->Functions[$name];
    }

    public function Create(string $name, string $content) : bool {
        //Check if a function with that name exists
        if($this->Functions[$name]) return false;
        //Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        //Encrypt function script
        $encrypted = openssl_encrypt(json_encode($content), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        //Save
        file_put_contents("./data/functions/".$name.".lona", $encrypted.":".base64_encode($iv));
        //Initialize function instance
        $this->Functions[$name] = new LonaFunction($this->LonaDB, $name . ".lona");
        return true;
    }

    public function Delete(string $name) : bool {
        //Check if function exists
        if(!$this->Functions[$name]) return false;
        //Delete function file and instance
        unset($this->Functions[$name]);
        unlink("./data/functions/".$name.".lona");
        return true;
    }
}