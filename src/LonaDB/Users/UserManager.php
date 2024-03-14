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

    public function CheckPassword(string $name = "", string $password = "") : bool {
        if($name === "root" && $password === $this->LonaDB->config["root"]) return true;

        if(!$this->Users[$name]) {
            return false;
        }

        if($this->Users[$name]["password"] !== $password) return false;

        return true;
    }

    public function CheckUser(string $name) : bool {
        if($name === "root") return true;
        if(!$this->Users[$name]) return false;
        return true;
    }

    public function ListUsers() : array {
        $users = [];

        foreach($this->Users as $name => $user){
            $users[$name] = $this->GetRole($name);
        }

        return $users;
    }

    public function CreateUser(string $name, string $password) : bool {
        if($name === "root") return false;
        $this->LonaDB->Logger->User("Trying to create user '" . $name . "'");
        if($this->Users[$name]) {
            $this->LonaDB->Logger->Error("User '" . $name . "' already exists");
            return false;
        }

        $this->Users[$name] = array(
            "role" => "User",
            "password" => $password,
            "permissions" => [
                "default" => true
            ]
        );

        $this->LonaDB->Logger->User("User '" . $name . "' has been created");

        $this->Save();
        return true;
    }

    public function DeleteUser(string $name) : bool {
        if($name === "root") return false;
        $this->LonaDB->Logger->User("Trying to delete user '" . $name . "'");

        if(!$this->Users[$name]) {
            $this->LonaDB->Logger->Error("User '" . $name . "' doesn't exist");
            return false;
        }

        unset($this->Users[$name]);
        $this->LonaDB->Logger->User("Deleted user '" . $name . "'");
        $this->Save();
        return true;
    }

    public function SetRole(string $name, string $role) : bool {
        if($name === "root") return false;
        if($role === "Superuser") return false;

        if(!$this->CheckUser($name)) return false;
        
        $this->Users[$name]['role'] = $role;
        $this->Save();
        return true;
    }

    public function GetRole(string $name) : string {
        if($name === "root") return "Superuser";
        if(!$this->CheckUser($name)) return "";

        if($this->Users[$name]['role'] === "Superuser") $this->Users[$name]['role'] = "User";
        
        return $this->Users[$name]['role'];
    }

    public function CheckPermission(string $name, string $permission, string $user = "") : bool {
        if(!$this->CheckUser($name)) return false;
        if($this->GetRole($name) === "Administrator" || $this->GetRole($name) === "Superuser") return true;

        if(!$this->Users[$name]['permissions'][$permission]) return false;

        return true;
    }

    public function GetPermissions(string $name) : array {
        if($name === "root") return [];
        return $this->Users[$name]['permissions'];
    }

    public function AddPermission(string $name, string $permission) : bool {
        if($name === "root") return false;
        if(!$this->CheckUser($name)) return false;

        $this->Users[$name]['permissions'][$permission] = true;
        $this->LonaDB->Logger->User("Added permission '" . $permission . "' to user '" . $name . "'");
        
        $this->Save();
        return true;
    }

    public function RemovePermission(string $name, string $permission) : bool {
        if($name === "root") return false;
        if(!$this->CheckUser($name)) return false;

        unset($this->Users[$name]['permissions'][$permission]);
        $this->LonaDB->Logger->User("Removed permission '" . $permission . "' from user '" . $name . "'");
        
        $this->Save();
        return true;
    }

    public function Save() : void {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));

        $encrypted = openssl_encrypt(json_encode($this->Users), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
    }
}
