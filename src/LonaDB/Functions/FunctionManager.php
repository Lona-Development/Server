<?php

namespace LonaDB\Functions;

define('AES_256_CBC', 'aes-256-cbc');

require 'vendor/autoload.php';
use LonaDB\LonaDB;
use LonaDB\Functions\LonaFunction;

class FunctionManager{
    private LonaDB $LonaDB;
    private array $Functions;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
        $this->Functions = array();

        if(!is_dir("data/")) mkdir("data/");
        if(!is_dir("data/functions/")) mkdir("data/functions/");

        foreach (new \DirectoryIterator('data/functions') as $fileInfo) {
            if(str_ends_with($fileInfo->getFilename(), ".lona")){
                $this->Functions[substr($fileInfo->getFilename(), 0, -5)] = new LonaFunction($this->LonaDB, $fileInfo->getFilename());
            }
        }
    }

    public function GetFunction(string $name) : mixed {
        if(!$this->Functions[$name]) return false;

        return $this->Functions[$name];
    }

    public function Create(string $name, string $content) : bool {
        if($this->Functions[$name]) return false;

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));

        $encrypted = openssl_encrypt(json_encode($content), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        file_put_contents("./data/functions/".$name.".lona", $encrypted.":".base64_encode($iv));

        $this->Functions[$name] = new LonaFunction($this->LonaDB, $name . ".lona");

        return true;
    }

    public function Delete(string $name) : bool {
        if(!$this->Functions[$name]) return false;

        unset($this->Functions[$name]);
        unlink("./data/functions/".$name.".lona");
        return true;
    }
}