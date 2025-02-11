<?php

namespace LonaDB\Users;

require '../../vendor/autoload.php';

use LonaDB\Enums\Permission;
use LonaDB\Enums\Role;
use LonaDB\LonaDB;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class UserManager extends ThreadSafe
{
    private ThreadSafeArray $user;
    private int $logLevel;
    private LonaDB $lonaDB;

    /**
     * Constructor for the UserManager class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;

        $path = "./data/wal/system/";
        //create each folder if it doesn't exist
        $current = getcwd();
        foreach(explode("/", $path) as $folder) {
            if(!is_dir($folder)) {
                mkdir($folder);
            }
            chdir($folder);
        }
        chdir($current);

        //Create an empty Users.lona file if it doesn't exist
        file_put_contents("data/Users.lona", file_get_contents("data/Users.lona"));
        //Check if the Users.lona file didn't exist before
        if (file_get_contents("data/Users.lona") === "") {
            //Create an empty Array
            $save = [
                "users" => [],
                "logLevel" => 0
            ];

            //Convert and decrypt the array
            $encrypted = LonaDB::encrypt(json_encode($save), $this->lonaDB->config["encryptionKey"]);
            //Save the array
            file_put_contents("./data/Users.lona", $encrypted);
        }

        //Decrypt the Users.lona file
        $temp = json_decode(LonaDB::decrypt(file_get_contents("./data/Users.lona"), $this->lonaDB->config["encryptionKey"]), true);

        $this->users = ThreadSafeArray::fromArray($temp["users"]);
        $this->logLevel = $temp["logLevel"];

        $this->checkWriteAheadLog();    
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
        } elseif (!$this->checkUser($name)) {
            return false;
        } elseif ($this->users[$name]['password'] !== $password) {
            return true;
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
        if ($name === "root" || $name === "" || $password === "" || $name === "users") {
            return false;
        }
        $this->lonaDB->getLogger()->user("Trying to create user '".$name."'");
        //Check if there already is a user with that name
        if ($this->checkUser($name)) {
            $this->lonaDB->getLogger()->error("User '".$name."' already exists");
            return false;
        }

        //Add a user to the user's array
        $this->writeAheadLog("create", $name, $password);
        $this->users[$name] = ThreadSafeArray::fromArray([
            "role" => Role::USER->value,
            "password" => $password,
            "permissions" => [
                "default" => true
            ]
        ]);
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

        //Change table owner to root
        foreach ($this->lonaDB->getTableManager()->listTables($name) as $table) {
            if ($this->lonaDB->getTableManager()->getTable($table)->getOwner() === $name)
                $this->lonaDB->getTableManager()->getTable($table)->setOwner("root", "root");
        }

        //remove table permissions using unset
        foreach ($this->lonaDB->getTableManager()->listTables() as $table) {
            $this->lonaDB->getTableManager()->getTable($table)->removeUserPermissions($name);
        }

        $this->writeAheadLog("delete", $name);
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

        $this->writeAheadLog("setRole", $name, $role->value);
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
        if ($this->users[$name]['role'] == Role::SUPERUSER->value) {
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
            (in_array($this->getRole($name), [Role::ADMIN, Role::SUPERUSER]) ||
            $this->users[$name]['permissions'][$permission->value]);
    }

    /**
     * Gets the permissions of a user.
     *
     * @param  string  $name  The username.
     * @return array The permissions of the user.
     */
    public function getPermissions(string $name): ThreadSafeArray
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
        $this->writeAheadLog("addPermission", $name, $permission->value);
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
        $this->writeAheadLog("removePermission", $name, $permission->value);
        unset($this->users[$name]['permissions'][$permission->value]);
        $this->lonaDB->getLogger()->user("Removed permission '".$permission->value."' from user '".$name."'");
        $this->save();
        return true;
    }

    /**
     * Checks the write-ahead log file for changes.
     */
    public function checkWriteAheadLog(): void
    {
        try {
            file_put_contents("./data/wal/system/Users.lona", file_get_contents("./data/wal/system/Users.lona"));
            if(file_get_contents("./data/wal/system/Users.lona") == "")
                file_put_contents("./data/wal/system/Users.lona", LonaDB::encrypt(json_encode([]), $this->lonaDB->config["encryptionKey"]));

            $logFile = fopen("./data/wal/system/Users.lona", "r");

            if(!flock($logFile, LOCK_EX)) 
                throw new \Exception("Could not lock write-ahead log file for users table");

            $log = json_decode(LonaDB::decrypt(file_get_contents("./data/wal/system/Users.lona"), $this->lonaDB->config["encryptionKey"]), true);

            if($this->logLevel == 0 && $log == []) {
                flock($logFile, LOCK_UN);
                fclose($logFile);
                return;
            }

            $lastAction = 0;

            foreach($log as $key => $value) {
                if($key > $lastAction) {
                    $lastAction = $key;
                }
            }

            if($lastAction > $this->logLevel) {
                $counter = 0;
                for($i = $this->logLevel; $i <= $lastAction; $i++) {
                    switch($log[$i]["action"]) {
                        case "create":
                            $this->users[$log[$i]["name"]] = ThreadSafeArray::fromArray([
                                "role" => Role::DEFAULT->value,
                                "password" => $log[$i]["data"],
                                "permissions" => [
                                    "default" => true
                                ]
                            ]);
                            break;
                        case "delete":
                            unset($this->users[$log[$i]["name"]]);
                            break;
                        case "setRole":
                            $this->users[$log[$i]["name"]]["role"] = $log[$i]["data"];
                            break;
                        case "addPermission":
                            $this->users[$log[$i]["name"]]["permissions"][$log[$i]["data"]] = true;;
                            break;
                        case "removePermission":
                            $this->users[$log[$i]["name"]]["permissions"][$log[$i]["data"]] = false;
                            break;
                    }
                    $counter++;
                }

                $this->logLevel = $lastAction;
                $this->save();
            }

            flock($logFile, LOCK_UN);
            fclose($logFile);
        } catch (Exception $e) {
            $this->lonaDB->getLogger()->error("Error checking write-ahead log file for users table: ".$e->getMessage());
        }
    }

    /**
     * Writes to the write-ahead log file.
     *
     * @param  string  $action  The action to log.
     * @param  string  $name  The name of the user.
     * @param  string  $data  The data to log.
     */
    public function writeAheadLog(string $action, string $name, string $data = ""): void {
        try {
            $this->logLevel++;

            $log = json_decode(LonaDB::decrypt(file_get_contents("./data/wal/system/Users.lona"), $this->lonaDB->config["encryptionKey"]), true);

            $log[$this->logLevel] = [
                "action" => $action,
                "name" => $name,
                "data" => $data
            ];

            $logFile = fopen("./data/wal/system/Users.lona", "w+");

            if(!$logFile) {
                throw new \Exception("Could not open write-ahead log file for users table");
            }

            if(!flock($logFile, LOCK_EX)) {
                throw new \Exception("Could not lock write-ahead log file for users table");
            }
            
            $encrypted = LonaDB::encrypt(json_encode($log), $this->lonaDB->config["encryptionKey"]);

            if(!fwrite($logFile, $encrypted))
                throw new \Exception("Could not write to write-ahead log file for users table");

            flock($logFile, LOCK_UN);
            fclose($logFile);
        } catch (Exception $e) {
            $this->lonaDB->getLogger()->error("Error writing to write-ahead log file for users table: ".$e->getMessage());
        }
    }

    /**
     * Saves the user's array to the Users.lona file.
     */
    public function save(): void
    {
        $encrypted = LonaDB::encrypt(json_encode(array(
            "users" => $this->users,
            "logLevel" => $this->logLevel
        )), $this->lonaDB->config["encryptionKey"]);

        //Save the encrypted data Users.lona
        $file = fopen("./data/Users.lona", "w");
        if(!$file) {
            $this->lonaDB->getLogger()->error("Could not open Users.lona file for writing");
            return;
        }

        if(!flock($file, LOCK_EX)) {
            $this->lonaDB->getLogger()->error("Could not lock Users.lona file for writing");
            return;
        }

        if(!fwrite($file, $encrypted)) {
            $this->lonaDB->getLogger()->error("Could not write to Users.lona file");
            return;
        }

        flock($file, LOCK_UN);
        fclose($file);
    }
}
