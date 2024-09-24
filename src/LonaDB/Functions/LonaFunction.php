<?php

namespace LonaDB\Functions;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

//Load Main file
use LonaDB\LonaDB;

class LonaFunction{
    //Create all variables
    private string $file;
    public string $Name;
    private array $functions;

    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB, string $name){
        $this->LonaDB = $lonaDB;
    
        $this->LonaDB->Logger->Load("Loading function '".$name."'");

        //Split encrypted function from IV
        $parts = explode(':', file_get_contents("./data/functions/".$name));
        //Decrypt function
        $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);

        //Get function name
        $this->file = substr($name, 0, -5); 
        $this->Name = $this->file;

        //Create a eval script to add the function to the functions array
        //
        //We are using an array because overwriting/defining a function inside eval didn't work for us
        //Our workaround is creating a class instance with a run function which is our Lona function 
        $function = "\$this->functions['" . $this->Name . "'] = new class {\n";
        $function .= "public function run(\$LonaDB, \$data, \$server, \$fd) {\n";
        $function .= $temp;
        $function .= "\n} \n};";

        //Add the function to the array
        eval($function);

        $this->LonaDB->Logger->Load("Function '" . $this->Name . "' has been loaded");
    }

    public function Execute(LonaDB $LonaDB, array $data, $server, $fd) : mixed {
        //Run function
        return $this->functions[$this->Name]->run($LonaDB, $data, $server, $fd);
    }
}