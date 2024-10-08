<?php

namespace LonaDB\Users;

//Encryption/decryption 
define('AES_256_CBC', 'aes-256-cbc');

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file
use LonaDB\LonaDB;

class UserManager{
    //Create all variables
    private array $Users;
    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
        //Create Array for users
        $this->Users = array();

        //Create folder data if it doesn't exist
        if(!is_dir("data/")) mkdir("data/");

        //Create an empty Users.lona file if it doesn't exist
        file_put_contents("data/Users.lona", file_get_contents("data/Users.lona"));
        //Check if the Users.lona file didn't exist before
        if(file_get_contents("data/Users.lona") === "") {
            //Create an IV for encryption
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
            //Create an empty Array
            $save = array();

            //Convert and decrypt the array
            $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
            //Save the array
            file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
        }

        //Split the decrypted Users.lona from the IV
        $parts = explode(':', file_get_contents("./data/Users.lona"));
        //Load the Users
        $this->Users = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, base64_decode($parts[1])), true);
    }

    public function CheckPassword(string $name = "", string $password = "") : bool {
        //If username is root, check for the root password
        if($name === "root" && $password === $this->LonaDB->config["root"]) return true;
        //Check if the user exists
        if(!$this->CheckUser($name)) return false;
        //Check if the password is correct
        if($this->Users[$name]["password"] !== $password) return false;
        //All checks successfull
        return true;
    }

    public function CheckUser(string $name) : bool {
        //Check if username is root
        if($name === "root") return true;
        //Check if the user exists
        if(!$this->Users[$name]) return false;
        //User does exist
        return true;
    }

    public function ListUsers() : array {
        //Empty array for users & roles
        $users = [];
        //Loop through all Users
        foreach($this->Users as $name => $user){
            //Username => Role
            //Example: Hymmel => Administrator
            $users[$name] = $this->GetRole($name);
        }
        return $users;
    }

    public function CreateUser(string $name, string $password) : bool {
        //If username is root, abort
        if($name === "root") return false;
        $this->LonaDB->Logger->User("Trying to create user '" . $name . "'");
        //Check if there already is a user with that name
        if($this->CheckUser($name)) {
            $this->LonaDB->Logger->Error("User '" . $name . "' already exists");
            return false;
        }
        //Add user to the users array
        $this->Users[$name] = array(
            "role" => "User",
            "password" => $password,
            "permissions" => [
                "default" => true
            ]
        );
        $this->LonaDB->Logger->User("User '" . $name . "' has been created");
        //Save users array to Users.lona
        $this->Save();
        return true;
    }

    public function DeleteUser(string $name) : bool {
        //If username is root, abort
        if($name === "root") return false;
        $this->LonaDB->Logger->User("Trying to delete user '" . $name . "'");
        //Check if user exists
        if(!$this->CheckUser($name)) {
            $this->LonaDB->Logger->Error("User '" . $name . "' doesn't exist");
            return false;
        }
        //Delete user from users array
        unset($this->Users[$name]);
        $this->LonaDB->Logger->User("Deleted user '" . $name . "'");
        //Save users array to Users.lona
        $this->Save();
        return true;
    }

    public function SetRole(string $name, string $role) : bool {
        //If username is root or desired role is Superuser, abort
        if($name === "root") return false;
        if($role === "Superuser") return false;
        //Check if user exists
        if(!$this->CheckUser($name)) return false;
        //Set user role
        $this->Users[$name]['role'] = $role;
        //Save users array to Users.lona
        $this->Save();
        return true;
    }

    public function GetRole(string $name) : mixed {
        //Root is superuser
        if($name === "root") return "Superuser";
        //Check if user exists
        if(!$this->CheckUser($name)) return false;
        //Deny normal users from being a Superuser -> Only root should be an Superuser
        if($this->Users[$name]['role'] === "Superuser") $this->Users[$name]['role'] = "User";
        //Return user role
        return $this->Users[$name]['role'];
    }

    public function CheckPermission(string $name, string $permission, string $user = "") : bool {
        //Check if user exists
        if(!$this->CheckUser($name)) return false;
        //If user is an Administrator or Superuser, they are allowed to do anything
        if($this->GetRole($name) === "Administrator" || $this->GetRole($name) === "Superuser") return true;
        //Return user permission
        if(!$this->Users[$name]['permissions'][$permission]) return false;
        return true;
    }

    public function GetPermissions(string $name) : array {
        //If username is root, return an empty array -> Root is allowed to do anything
        if($name === "root") return [];
        //Return the desires user's permissions as an array
        return $this->Users[$name]['permissions'];
    }

    public function AddPermission(string $name, string $permission) : bool {
        //Check if username is root -> You cannot add permissions to root
        if($name === "root") return false;
        //Check if user exists
        if(!$this->CheckUser($name)) return false;
        //Add the permission to the user
        $this->Users[$name]['permissions'][$permission] = true;
        $this->LonaDB->Logger->User("Added permission '" . $permission . "' to user '" . $name . "'");
        //Save Users.lona
        $this->Save();
        return true;
    }

    public function RemovePermission(string $name, string $permission) : bool {
        //Check if username is root -> You cannot remove permissions from root
        if($name === "root") return false;
        //Check if user exists
        if(!$this->CheckUser($name)) return false;
        //Remove the permission from the user
        unset($this->Users[$name]['permissions'][$permission]);
        $this->LonaDB->Logger->User("Removed permission '" . $permission . "' from user '" . $name . "'");
        //Save Users.lona
        $this->Save();
        return true;
    }

    public function Save() : void {
        //Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        //Encrypt Users array
        $encrypted = openssl_encrypt(json_encode($this->Users), AES_256_CBC, $this->LonaDB->config["encryptionKey"], 0, $iv);
        //Save the encrypted data + the IV to Users.lona
        file_put_contents("./data/Users.lona", $encrypted.":".base64_encode($iv));
    }
}
