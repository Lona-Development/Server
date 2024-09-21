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
        
        if(!$create){
            $this->LonaDB->Logger->Load("Trying to load table '".$name."'");

            $parts = explode(':', file_get_contents("./data/tables/".$name));
            $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);

            $this->file = substr($name, 0, -5);
            $this->data = $temp["data"];
            $this->permissions = $temp["permissions"];
            $this->Owner = $temp["owner"];
        }
        else{
            $this->LonaDB->Logger->Table("Trying to generate table '".$name."'");

            $this->file = $name;
            $this->data = array();
            $this->permissions = array();
            $this->Owner = $owner;

            $this->Save();
        }

        $this->Name = $this->file;
    }

    public function GetData() : array {
        return $this->data;
    }

    public function GetOwner(string $user = "") : string {
        if($user === "") return $this->Owner;

        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' is trying to get the owner name.");

        return $this->Owner;
    }

    public function SetOwner(string $name, string $user) : bool {
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' is trying to change the owner to '".$name."'");
        if($user !== "root" && $user !== $this->Owner) return false;

        $this->Owner = $name;
        $this->Save();
        return true;
    }

    public function Set(string $name, mixed $value, string $user) : bool {
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' is trying to set the variable '".$name."' to '".strval($value)."'");
        if(!$this->CheckPermission($user, "write")) return false;

        $this->data[$name] = $value;
        $this->Save();
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' set the variable '".$name."' to '".strval($value)."'");
        return true;
    }

    public function Delete(string $name, string $user) : bool {
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' is trying to delete the variable '".$name."'");
        if(!$this->CheckPermission($user, "write")) return false;

        unset($this->data[$name]);
        $this->Save();
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' deleted the variable '".$name."'");
        return true;
    }

    public function Get(string $name, string $user) : mixed {
        $this->LonaDB->Logger->Table("(".$this->file.") User '".$user."' is trying to get the variable '".$name."'");
        if(!$this->CheckPermission($user, "read")) return null;

        return $this->data[$name];
    }

    public function CheckPermission(string $user, string $permission) : bool {
        if($user === $this->Owner) return true;
        if($this->LonaDB->UserManager->GetRole($user) === "Administrator" || $this->LonaDB->UserManager->GetRole($user) === "Superuser") return true;
        if($this->permissions[$user]["admin"]) return true;
        if(!$this->permissions[$user][$permission]) return false;

        return true;
    }

    public function CheckVariable(string $name, string $user) : bool {
        if(!$this->CheckPermission($user, 'read')) return false;
        if(!$this->data[$name]) return false;
        return true;
    }

    public function AddPermission(string $name, string $permission, string $user) : bool {
        if($user !== $this->Owner && !$this->permissions[$user]["admin"]) {
            $this->LonaDB->Logger->Table("(".$this->file.") Missing permission! Adding permission '".$permission."' for user '".$name."', requested by '".$user."'");
            return false;
        }
        if($user !== $this->Owner && $permission === "admin") {
            $this->LonaDB->Logger->Table("(".$this->file.") Not the Owner! Adding permission '".$permission."' for user '".$name."', requested by '".$user."'");
            return false;
        }

        $this->LonaDB->Logger->Table("(".$this->file.") Adding permission '".$permission."' for user '".$name."', requested by '".$user."'");

        $this->permissions[$name][$permission] = true;
        $this->Save();
        return true;
    }

    public function RemovePermission(string $name, string $permission, string $user) : bool {
        if($user !== $this->Owner && !$this->permissions[$user]["admin"]) {
            $this->LonaDB->Logger->Table("(".$this->file.") Missing permission! Removing permission '".$permission."' for user '".$name."', requested by '".$user."'");
            return false;
        }

        if($user !== $this->Owner && $permission === "admin") {
            $this->LonaDB->Logger->Table("(".$this->file.") Not the Owner! Removing permission '".$permission."' for user '".$name."', requested by '".$user."'");
            return galse;
        }
        
        $this->LonaDB->Logger->Table("(".$this->file.") Removing permission '".$permission."' for user '".$name."', requested by '".$user."'");

        unset($this->permissions[$name][$permission]);

        if($this->permissions[$name] === array()) unset($this->permissions[$name]);
        $this->Save();
    }

    private function Save() : void {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        $save = array(
            "data" => $this->data,
            "permissions" => $this->permissions,
            "owner" => $this->Owner
        );

        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        file_put_contents("./data/tables/".$this->file.".lona", $encrypted.":".base64_encode($iv));
    }
}