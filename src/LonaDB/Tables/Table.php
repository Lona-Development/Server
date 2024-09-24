<?php

namespace LonaDB\Tables;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

//Load Main file
use LonaDB\LonaDB;

class Table{
    //Create all variables
    private string $file;
    private array $data;
    private array $permissions;
    private string $Owner;
    public string $Name;

    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB, bool $create, string $name, string $owner = ""){
        $this->LonaDB = $lonaDB;
        
        //Check if this instance is used to create the table
        if(!$create){
            $this->LonaDB->Logger->Load("Trying to load table '".$name."'");

            //Split encrypted file content and IV
            $parts = explode(':', file_get_contents("./data/tables/".$name));
            //Decrypt table data and load it as a JSON object
            $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);

            //Load table informations
            $this->file = substr($name, 0, -5);
            $this->data = $temp["data"];
            $this->permissions = $temp["permissions"];
            $this->Owner = $temp["owner"];
        }
        //Instance is used to create the table
        else {
            $this->LonaDB->Logger->Table("Trying to generate table '".$name."'");

            //Load table informations and create empty data and permissions array
            $this->file = $name;
            $this->data = array();
            $this->permissions = array();
            $this->Owner = $owner;

            //Save the empty table
            $this->Save();
        }

        $this->Name = $this->file;
    }

    //Return an array of all variables in the table
    public function GetData() : array { return $this->data; }

    //Return the table owner's name
    public function GetOwner(string $user = "") : string { return $this->Owner; }

    public function SetOwner(string $name, string $user) : bool {
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' is trying to change the owner to '".$name."'");
        //Check if the executing user is either root or the owner of the table
        if($user !== "root" && $user !== $this->Owner) return false;

        //Change the owner and save
        $this->Owner = $name;
        $this->Save();
        return true;
    }

    public function Set(string $name, mixed $value, string $user) : bool {
        //Check if the executing user has write permissions on this table
        if(!$this->CheckPermission($user, "write")) return false;

        //Set the variable and save
        $this->data[$name] = $value;
        $this->Save();
        return true;
    }

    public function Delete(string $name, string $user) : bool {
        //Check if the executing user has write permissions on this table
        if(!$this->CheckPermission($user, "write")) return false;

        //Delete the variable and save
        unset($this->data[$name]);
        $this->Save();
        return true;
    }

    public function Get(string $name, string $user) : mixed {
        //Check if the executing user has read permissions on this table
        if(!$this->CheckPermission($user, "read")) return null;

        //Return variable's value
        return $this->data[$name];
    }

    public function CheckPermission(string $user, string $permission) : bool {
        //Check if the user is the table owner
        if($user === $this->Owner) return true;
        //Check if the user is an Administrator or Superuser
        if($this->LonaDB->UserManager->GetRole($user) === "Administrator" || $this->LonaDB->UserManager->GetRole($user) === "Superuser") return true;
        //Check if the user is an table Administrator
        if($this->permissions[$user]["admin"]) return true;
        //Check if the user doesn't have the permission
        if(!$this->permissions[$user][$permission]) return false;
        //All checks have been run and the user has the permission
        //Return true
        return true;
    }

    public function CheckVariable(string $name, string $user) : bool {
        //Check if the executing user has read permissions on this table
        if(!$this->CheckPermission($user, 'read')) return false;
        //Check if variable exists and return state
        if(!$this->data[$name]) return false;
        return true;
    }

    public function AddPermission(string $name, string $permission, string $user) : bool {
        //Check if user is table owner/administrator, global administrator or superuser
        if($user !== $this->Owner && !$this->permissions[$user]["admin"] && $this->LonaDB->UserManager->GetRole($user) !== "Administrator" && $this->LonaDB->UserManager->GetRole($user) !== "Superuser") return false;

        //Add permission and save
        $this->permissions[$name][$permission] = true;
        $this->Save();
        return true;
    }

    public function RemovePermission(string $name, string $permission, string $user) : bool {
        //Check if user is table owner/administrator, global administrator or superuser
        if($user !== $this->Owner && !$this->permissions[$user]["admin"] && $this->LonaDB->UserManager->GetRole($user) !== "Administrator" && $this->LonaDB->UserManager->GetRole($user) !== "Superuser") return false;

        //Remove permission and save
        unset($this->permissions[$name][$permission]);
        if($this->permissions[$name] === array()) unset($this->permissions[$name]);
        $this->Save();
    }

    private function Save() : void {
        //Generate IV and array to save
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        $save = array(
            "data" => $this->data,
            "permissions" => $this->permissions,
            "owner" => $this->Owner
        );

        //Encrypt array using IV
        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        //Save encrypted array and IV
        file_put_contents("./data/tables/".$this->file.".lona", $encrypted.":".base64_encode($iv));
    }
}