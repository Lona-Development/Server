<?php

namespace LonaDB\Functions;

define('AES_256_CBC', 'aes-256-cbc');

use LonaDB\LonaDB;

class LonaFunction{
    private string $file;
    public string $Name;
    private array $data;

    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB, string $name){
        $this->LonaDB = $lonaDB;
    
        $this->LonaDB->Logger->Load("Trying to load function '".$name."'");

        $parts = explode(':', file_get_contents("./data/functions/".$name));
        $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);

        $this->file = substr($name, 0, -5); 
        $this->Name = $this->file;

        $function = "\$this->data['" . $this->Name . "'] = new class {\n";
        $function .= "public function run(\$LonaDB, \$data, \$server, \$fd) {\n";
        $function .= $temp;
        $function .= "\n} \n};";

        eval($function);

        $this->LonaDB->Logger->Load("Function '" . $this->Name . "' has been loaded");
    }

    public function Execute(LonaDB $LonaDB, array $data, $server, $fd) : mixed {
        return $this->data[$this->Name]->run($LonaDB, $data, $server, $fd);
    }
}