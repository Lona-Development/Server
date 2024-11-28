<?php

namespace LonaDB\Users;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

require 'vendor/autoload.php';

use LonaDB\LonaDB;

class UserManager
{
    private array $users = [];
    private LonaDB $lonaDB;

    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;

        //Create folder data if it doesn't exist
        if (!is_dir("data/")) {
            mkdir("data/");
        }

        //Create an empty Users.lona file if it doesn't exist
        file_put_contents("data/Users.lona", file_get_contents("data/Users.lona"));
        //Check if the Users.lona file didn't exist before
        if (file_get_contents("data/Users.lona") == "") {
            //Create an IV for encryption
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
            //Create an empty Array
            $save = array();

            //Convert and decrypt the array
            $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0,
                $iv);
            //Save the array
            file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
        }

        //Split the decrypted Users.lona from the IV
        $parts = explode(':', file_get_contents("./data/Users.lona"));
        //Load the Users
        $this->users = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0,
            base64_decode($parts[1])), true);
    }

    public function checkPassword(string $name = "", string $password = ""): bool
    {
        //If the username is root, check for the root password
        if ($name == "root" && $password == $this->lonaDB->config["root"]) {
            return true;
        }
        //Check if the user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Check if the password is correct
        if ($this->users[$name]["password"] != $password) {
            return false;
        }
        //All checks successfully
        return true;
    }

    public function checkUser(string $name): bool
    {
        //Check if the username is root
        if ($name == "root") {
            return true;
        }
        //Check if the user exists
        if (!$this->users[$name]) {
            return false;
        }
        //User does exist
        return true;
    }

    public function listUsers(): array
    {
        //Empty array for users & roles
        $users = [];
        //Loop through all Users
        foreach ($this->users as $name => $user) {
            //Username => Role
            //Example: Hymmel => Administrator
            $users[$name] = $this->getRole($name);
        }
        return $users;
    }

    public function createUser(string $name, string $password): bool
    {
        //If username is root, abort
        if ($name == "root") {
            return false;
        }
        $this->lonaDB->logger->user("Trying to create user '".$name."'");
        //Check if there already is a user with that name
        if ($this->checkUser($name)) {
            $this->lonaDB->logger->error("User '".$name."' already exists");
            return false;
        }
        //Add a user to the user's array
        $this->users[$name] = array(
            "role" => "User",
            "password" => $password,
            "permissions" => [
                "default" => true
            ]
        );
        $this->lonaDB->logger->user("User '".$name."' has been created");
        //Save users' array to Users.lona
        $this->save();
        return true;
    }

    public function deleteUser(string $name): bool
    {
        //If username is root, abort
        if ($name == "root") {
            return false;
        }
        $this->lonaDB->logger->user("Trying to delete user '".$name."'");
        //Check if a user exists
        if (!$this->checkUser($name)) {
            $this->lonaDB->logger->error("User '".$name."' doesn't exist");
            return false;
        }
        //Delete user from users array
        unset($this->users[$name]);
        $this->lonaDB->logger->user("Deleted user '".$name."'");
        //Save users' array to Users.lona
        $this->Save();
        return true;
    }

    public function setRole(string $name, string $role): bool
    {
        //If the username is root or the desired role is Superuser, abort
        if ($name == "root") {
            return false;
        }
        if ($role == "Superuser") {
            return false;
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Set user role
        $this->users[$name]['role'] = $role;
        //Save users' array to Users.
        $this->save();
        return true;
    }

    public function getRole(string $name)
    {
        //Root is a superuser
        if ($name == "root") {
            return "Superuser";
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Deny normal users from being a Superuser -> Only root should be a Superuser
        if ($this->users[$name]['role'] == "Superuser") {
            $this->users[$name]['role'] = "User";
        }
        //Return user role
        return $this->users[$name]['role'];
    }

    public function checkPermission(string $name, string $permission, string $user = ""): bool
    {
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //If the user is an Administrator or Superuser, they are allowed to do anything
        if ($this->getRole($name) == "Administrator" || $this->getRole($name) == "Superuser") {
            return true;
        }
        //Return user permission
        if (!$this->users[$name]['permissions'][$permission]) {
            return false;
        }
        return true;
    }

    public function getPermissions(string $name): array
    {
        //If the username is root, return an empty array -> Root is allowed to do anything
        if ($name == "root") {
            return [];
        }
        //Return the desire user's permissions as an array
        return $this->users[$name]['permissions'];
    }

    public function addPermission(string $name, string $permission): bool
    {
        //Check if the username is root -> You cannot add permissions to root
        if ($name == "root") {
            return false;
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Add the permission to the user
        $this->users[$name]['permissions'][$permission] = true;
        $this->lonaDB->logger->user("Added permission '".$permission."' to user '".$name."'");
        //Save Users.lona
        $this->Save();
        return true;
    }

    public function removePermission(string $name, string $permission): bool
    {
        //Check if the username is root -> You cannot remove permissions from the root
        if ($name == "root") {
            return false;
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Remove the permission from the user
        unset($this->users[$name]['permissions'][$permission]);
        $this->lonaDB->logger->User("Removed permission '".$permission."' from user '".$name."'");
        //Save Users.lona
        $this->save();
        return true;
    }

    public function save(): void
    {
        //Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        //Encrypt Users array
        $encrypted = openssl_encrypt(json_encode($this->users), AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0,
            $iv);
        //Save the encrypted data + the IV to Users.lona
        file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
    }
}
