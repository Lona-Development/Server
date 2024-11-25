<?php

namespace LonaDB\Functions;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

use LonaDB\LonaDB;

class LonaFunction{
    private string $file;
    public string $name;
    private array $functions;

    private LonaDB $lonaDB;

    public function __construct(LonaDB $lonaDB, string $name){
        $this->lonaDB = $lonaDB;
    
        $this->lonaDB->logger->Load("Loading function '".$name."'");

        //Split encrypted function from IV
        $parts = explode(':', file_get_contents("./data/functions/".$name));
        //Decrypt function
        $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);

        //Get function name
        $this->file = substr($name, 0, -5); 
        $this->name = $this->file;

        //Create an eval script to add the function to the function array
        //
        //We are using an array.
        //Overwriting/defining a function inside eval didn't work for us
        //Our workaround is creating a class instance with a run function which is our Lona function 
        $function = "\$this->functions['" . $this->name . "'] = new class {\n";
        $function .= "public function run(\$LonaDB, \$data) {\n";
        $function .= $temp;
        $function .= "\n} \n};";

        //Add the function to the array
        eval($function);

        $this->lonaDB->logger->Load("Function '" . $this->name . "' has been loaded");
    }

    public function execute(LonaDB $lonaDB, array $data, $client) {
        //Run function
        return $this->functions[$this->name]->run($lonaDB, $data);
    }
}