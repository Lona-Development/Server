<?php

namespace LonaDB\Users;

//Encryption/decryption
define('AES_256_CBC', 'aes-256-cbc');

require '../../vendor/autoload.php';

use LonaDB\Enums\Permission;
use LonaDB\Enums\Role;
use LonaDB\LonaDB;

class UserManager
{
    private array $users = [];
    private LonaDB $lonaDB;

    /**
     * Constructor for the UserManager class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;

        if (!is_dir("data/")) {
            mkdir("data/");
        }

        //Create an empty Users.lona file if it doesn't exist
        file_put_contents("data/Users.lona", file_get_contents("data/Users.lona"));
        //Check if the Users.lona file didn't exist before
        if (file_get_contents("data/Users.lona") === "") {
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

    /**
     * Checks if the provided password matches the user's password.
     *
     * @param  string  $name  The username.
     * @param  string  $password  The password to check.
     * @return bool Returns true if the password is correct, false otherwise.
     */
    public function checkPassword(string $name = "", string $password = ""): bool
    {
        if ($name === "root" && $password === $this->lonaDB->config["root"]) {
            return true;
        } elseif (!$this->checkUser($name) || $this->users[$name]["password"] !== $password) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a user exists.
     *
     * @param  string  $name  The username to check.
     * @return bool Returns true if the user exists, false otherwise.
     */
    public function checkUser(string $name): bool
    {
        return $name === "root" || $this->users[$name];
    }

    /**
     * Lists all users and their roles.
     *
     * @return array An array of usernames and their roles.
     */
    public function listUsers(): array
    {
        $users = [];
        foreach ($this->users as $name => $user) {
            //Username => Role
            //Example: Hymmel => Administrator
            $users[$name] = $this->getRole($name)->value;
        }
        return $users;
    }

    /**
     * Creates a new user.
     *
     * @param  string  $name  The username.
     * @param  string  $password  The password for the user.
     * @return bool Returns true if the user is created successfully, false otherwise.
     */
    public function createUser(string $name, string $password): bool
    {
        //If username is root, abort
        if ($name === "root") {
            return false;
        }
        $this->lonaDB->getLogger()->user("Trying to create user '".$name."'");
        //Check if there already is a user with that name
        if ($this->checkUser($name)) {
            $this->lonaDB->getLogger()->error("User '".$name."' already exists");
            return false;
        }
        //Add a user to the user's array
        $this->users[$name] = array(
            "role" => Role::USER->value,
            "password" => $password,
            "permissions" => [
                "default" => true
            ]
        );
        $this->lonaDB->getLogger()->user("User '".$name."' has been created");
        $this->save();
        return true;
    }

    /**
     * Deletes a user.
     *
     * @param  string  $name  The username to delete.
     * @return bool Returns true if the user is deleted successfully, false otherwise.
     */
    public function deleteUser(string $name): bool
    {
        //If username is root, abort
        if ($name === "root") {
            return false;
        }
        $this->lonaDB->getLogger()->user("Trying to delete user '".$name."'");
        //Check if a user exists
        if (!$this->checkUser($name)) {
            $this->lonaDB->getLogger()->error("User '".$name."' doesn't exist");
            return false;
        }
        unset($this->users[$name]);
        $this->lonaDB->getLogger()->user("Deleted user '".$name."'");
        $this->save();
        return true;
    }

    /**
     * Sets the role of a user.
     *
     * @param  string  $name  The username.
     * @param  Role  $role  The role to set.
     * @return bool Returns true if the role is set successfully, false otherwise.
     */
    public function setRole(string $name, Role $role): bool
    {
        //If the username is root or the desired role is Superuser, abort
        if ($name === "root") {
            return false;
        }
        if ($role === Role::SUPERUSER) {
            return false;
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        $this->users[$name]['role'] = $role->value;
        $this->save();
        return true;
    }

    /**
     * Gets the role of a user.
     *
     * @param  string  $name  The username.
     * @return ?Role The role of the user, or false if the user does not exist.
     */
    public function getRole(string $name): ?Role
    {
        if ($name === "root") {
            return Role::SUPERUSER;
        }
        if (!$this->checkUser($name)) {
            return null;
        }
        if ($this->users[$name]['role'] === Role::SUPERUSER->value) {
            $this->users[$name]['role'] = Role::USER->value;
        }
        return Role::find($this->users[$name]['role']);
    }

    /**
     * Checks if a user has a specific permission.
     *
     * @param  string  $name  The username.
     * @param  string  $permission  The permission to check.
     * @return bool Returns true if the user has the permission, false otherwise.
     */
    public function checkPermission(string $name, Permission $permission): bool
    {
        return $this->checkUser($name) &&
            $this->getRole($name)->isIn([Role::ADMIN, Role::SUPERUSER]) ||
            $this->users[$name]['permissions'][$permission->value];
    }

    /**
     * Gets the permissions of a user.
     *
     * @param  string  $name  The username.
     * @return array The permissions of the user.
     */
    public function getPermissions(string $name): array
    {
        //If the username is root, return an empty array -> Root is allowed to do anything
        if ($name === "root") {
            return [];
        }
        //Return the desire user's permissions as an array
        return $this->users[$name]['permissions'];
    }

    /**
     * Adds a permission to a user.
     *
     * @param  string  $name  The username.
     * @param  string  $permission  The permission to add.
     * @return bool Returns true if the permission is added successfully, false otherwise.
     */
    public function addPermission(string $name, Permission $permission): bool
    {
        //Check if the username is root -> You cannot add permissions to root
        if ($name === "root") {
            return false;
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Add the permission to the user
        $this->users[$name]['permissions'][$permission->value] = true;
        $this->lonaDB->getLogger()->user("Added permission '".$permission->value."' to user '".$name."'");
        $this->save();
        return true;
    }

    /**
     * Removes a permission from a user.
     *
     * @param  string  $name  The username.
     * @param  Permission  $permission  The permission to remove.
     * @return bool Returns true if the permission is removed successfully, false otherwise.
     */
    public function removePermission(string $name, Permission $permission): bool
    {
        //Check if the username is root -> You cannot remove permissions from the root
        if ($name === "root") {
            return false;
        }
        //Check if a user exists
        if (!$this->checkUser($name)) {
            return false;
        }
        //Remove the permission from the user
        unset($this->users[$name]['permissions'][$permission->value]);
        $this->lonaDB->getLogger()->user("Removed permission '".$permission->value."' from user '".$name."'");
        $this->save();
        return true;
    }

    /**
     * Saves the user's array to the Users.lona file.
     */
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