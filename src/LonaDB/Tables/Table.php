<?php

namespace LonaDB\Tables;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

use LonaDB\LonaDB;

class Table
{
    private string $file;
    private array $data;
    private array $permissions;
    private string $owner;
    public string $name;

    private LonaDB $lonaDB;

    public function __construct(LonaDB $lonaDB, bool $create, string $name, string $owner = "")
    {
        $this->lonaDB = $lonaDB;

        //Check if this instance is used to create the table
        if (!$create) {
            $this->lonaDB->logger->Load("Trying to load table '".$name."'");

            //Split encrypted file content and IV
            $parts = explode(':', file_get_contents("./data/tables/".$name));
            //Decrypt table data and load it as a JSON object
            $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0,
                base64_decode($parts[1])), true);

            //Load table information
            $this->file = substr($name, 0, -5);
            $this->data = $temp["data"];
            $this->permissions = $temp["permissions"];
            $this->owner = $temp["owner"];
        } //Instance is used to create the table
        else {
            $this->lonaDB->logger->table("Trying to generate table '".$name."'");

            //Load table information and create an empty data and permissions array
            $this->file = $name;
            $this->data = array();
            $this->permissions = array();
            $this->owner = $owner;

            //Save the empty table
            $this->save();
        }

        $this->name = $this->file;
    }

    //Return an array of all variables in the table
    public function getData(): array
    {
        return $this->data;
    }

    //Return the table owner's name
    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $name, string $user): bool
    {
        $this->lonaDB->logger->table("(".$this->file.") User '".$user."' is trying to change the owner to '".$name."'");
        //Check if the executing user is either root or the owner of the table
        if ($user !== "root" && $user !== $this->owner) {
            return false;
        }

        //Change the owner and save
        $this->owner = $name;
        $this->save();
        return true;
    }

    public function set(string $name, $value, string $user): bool
    {
        //Check if the executing user has write permissions on this table
        if (!$this->checkPermission($user, "write")) {
            return false;
        }

        //Set the variable and save
        $this->data[$name] = $value;
        $this->save();
        return true;
    }

    public function delete(string $name, string $user): bool
    {
        //Check if the executing user has write permissions on this table
        if (!$this->checkPermission($user, "write")) {
            return false;
        }

        //Delete the variable and save
        unset($this->data[$name]);
        $this->save();
        return true;
    }

    public function get(string $name, string $user)
    {
        //Check if the executing user has read permissions on this table
        if (!$this->checkPermission($user, "read")) {
            return null;
        }

        //Return variable's value
        return $this->data[$name];
    }

    public function checkPermission(string $user, string $permission): bool
    {
        //Check if the user is the table owner
        if ($user === $this->owner) {
            return true;
        }
        //Check if the user is an Administrator or Superuser
        if ($this->lonaDB->userManager->getRole($user) === "Administrator" || $this->lonaDB->userManager->getRole($user) === "Superuser") {
            return true;
        }
        //Check if the user is a table Administrator
        if ($this->permissions[$user]["admin"]) {
            return true;
        }
        //Check if the user doesn't have the permission
        if (!$this->permissions[$user][$permission]) {
            return false;
        }
        //All checks have been run, and the user has the permission
        return true;
    }

    public function checkVariable(string $name, string $user): bool
    {
        //Check if the executing user has read permissions on this table
        if (!$this->checkPermission($user, 'read')) {
            return false;
        }
        //Check if a variable exists and return state
        if (!$this->data[$name]) {
            return false;
        }
        return true;
    }

    public function addPermission(string $name, string $permission, string $user): bool
    {
        //Check if the user is table owner/administrator, global administrator or superuser
        if ($user !== $this->owner && !$this->checkPermission($user,
                "admin") && $this->lonaDB->userManager->getRole($user) !== "Administrator" && $this->lonaDB->userManager->getRole($user) !== "Superuser") {
            return false;
        }

        //Add permission and save
        $this->permissions[$name][$permission] = true;
        $this->save();
        return true;
    }

    public function removePermission(string $name, string $permission, string $user): bool
    {
        //Check if the user is table owner/administrator, global administrator or superuser
        if ($user !== $this->owner && !$this->checkPermission($user,
                "admin") && $this->lonaDB->userManager->getRole($user) !== "Administrator" && $this->lonaDB->userManager->getRole($user) !== "Superuser") {
            return false;
        }

        //Remove permission and save
        unset($this->permissions[$name][$permission]);
        if ($this->permissions[$name] === array()) {
            unset($this->permissions[$name]);
        }
        $this->save();
        return true;
    }

    private function save(): void
    {
        //Generate IV and array to save
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        $save = array(
            "data" => $this->data,
            "permissions" => $this->permissions,
            "owner" => $this->owner
        );

        //Encrypt array using IV
        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0, $iv);
        //Save encrypted array and IV
        file_put_contents("./data/tables/".$this->file.".lona", $encrypted.":".base64_encode($iv));
    }
}