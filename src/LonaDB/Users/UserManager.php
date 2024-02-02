<?php

namespace LonaDB\Users;

define('AES_256_CBC', 'aes-256-cbc');

require 'vendor/autoload.php';
use LonaDB\LonaDB;

class UserManager{
    private LonaDB $LonaDB;
    private array $Users;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
        $this->Tables = array();

        if(!is_dir("data/")) mkdir("data/");

        file_put_contents("data/Users.lona", file_get_contents("data/Users.lona"));
        if(file_get_contents("data/Users.lona") === "") {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
            $save = array();

            $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
            file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
        }

        $parts = explode(':', file_get_contents("./data/Users.lona"));
        $this->Users = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);
    }

    public function CheckPassword(string $name, string $password){
        $this->LonaDB->Logger->User("Trying to check password for user '" . $name . "'");
        if($name === "root" && $password === $this->LonaDB->config["root"]) return true;

        if(!$this->Users[$name]) {
            $this->LonaDB->Logger->User("User '".$name."'doesn't exist");
            return false;
        }

        if($this->Users[$name]["password"] !== $password) return false;

        return true;
    }

    public function CreateUser(string $name, string $password){
        if($name === "root") return;
        $this->LonaDB->Logger->User("Trying to create user '" . $name . "'");
        if($this->Users[$name]) {
            $this->LonaDB->Logger->Error("User '" . $name . "' already exists");
            return false;
        }

        $this->Users[$name] = array(
            "role" => "user",
            "password" => $password,
            "permissions" => []
        );

        $this->LonaDB->Logger->User("User '" . $name . "' has been created");

        $this->Save();
    }

    public function DeleteUser(string $name){
        if($name === "root") return;
        $this->LonaDB->Logger->User("Trying to delete user '" . $name . "'");

        if(!$this->Users[$name]) {
            $this->LonaDB->Logger->Error("User '" . $name . "' doesn't exist");
            return;
        }

        unset($this->Users[$name]);
        $this->LonaDB->Logger->User("Deleted user '" . $name . "'");
        $this->Save();
    }

    public function CheckPermission(string $name){
        return true;
    }

    public function Save(){
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));

        $encrypted = openssl_encrypt(json_encode($this->Users), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
    }
}