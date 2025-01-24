<?php

namespace LonaDB\Tables;

//Encryption/decryption
define('AES_256_CBC', 'aes-256-cbc');

use LonaDB\Enums\Permission;
use LonaDB\Enums\Role;
use LonaDB\LonaDB;

class Table
{
    private string $file;
    private array $data;
    private int $logLevel;
    private array $permissions;
    private string $owner;
    public string $name;

    private LonaDB $lonaDB;

    /**
     * Constructor for the Table class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     * @param  bool  $create  Indicates if the table is being created.
     * @param  string  $name  The name of the table.
     * @param  string  $owner  The owner of the table.
     */
    public function __construct(LonaDB $lonaDB, bool $create, string $name, string $owner = "")
    {
        $this->lonaDB = $lonaDB;
        if (!$create) {
            $this->lonaDB->getLogger()->load("Trying to load table '".$name."'");

            $temp = json_decode(LonaDB::decrypt(file_get_contents("./data/tables/".$name), $this->lonaDB->config["encryptionKey"]), true);

            $this->file = substr($name, 0, -5);
            $this->data = $temp["data"];
            $this->permissions = $temp["permissions"];
            $this->owner = $temp["owner"];

            if(isset($temp["logLevel"]))
                $this->logLevel = $temp["logLevel"];
            else {
                $this->logLevel = 0;
                $logFile = fopen("./data/wal/".$this->file.".lona", 'w');

                if ($logFile === false) {
                    throw new \Exception("Failed to create write-ahead log file for table '".$this->file."'.");
                }

                if (!flock($logFile, LOCK_EX)) {
                    fclose($logFile);
                    throw new \Exception("Failed to acquire lock on the write-ahead log file for table '".$this->file."'.");
                }

                if (fwrite($logFile, LonaDB::encrypt(json_encode([]), $this->lonaDB->config["encryptionKey"])) === false) {
                    flock($logFile, LOCK_UN);
                    fclose($logFile);
                    throw new \Exception("Failed to write to the write-ahead log file for table '".$this->file."'.");
                }

                flock($logFile, LOCK_UN);
                fclose($logFile);

                $this->lonaDB->getLogger()->table("Created missing write-ahead log file for table '".$this->file."'");
            }

            $this->checkWriteAheadLog();
        } else {
            $this->lonaDB->getLogger()->table("Trying to generate table '".$name."'");
            $this->file = $name;
            $this->data = array();
            $this->permissions = array();
            $this->owner = $owner;
            $this->logLevel = 0;
            $this->save();
        }

        $this->name = $this->file;
    }

    /**
     * Returns an array of all variables in the table.
     *
     * @return array The data in the table.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Returns the table owner's name.
     *
     * @return string The name of the table owner.
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * Sets the owner of the table.
     *
     * @param  string  $name  The new owner's name.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the owner is set successfully, false otherwise.
     */
    public function setOwner(string $name, string $user): bool
    {
        $this->lonaDB->getLogger()->table("(".$this->file.") User '".$user."' is trying to change the owner to '".$name."'");
        if ($user !== "root" && $user !== $this->owner) {
            return false;
        }

        $this->writeAheadLog("setOwner", $name, $user);

        $this->owner = $name;
        $this->save();
        return true;
    }

    /**
     * Sets a variable in the table.
     *
     * @param  string  $name  The name of the variable.
     * @param  mixed  $value  The value of the variable.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the variable is set successfully, false otherwise.
     */
    public function set(string $name, mixed $value, string $user): bool
    {
        if (!$this->checkPermission($user, Permission::WRITE)) {
            return false;
        }

        $this->writeAheadLog("set", $name, $value, $user);

        $this->data[$name] = $value;
        $this->save();
        return true;
    }

    /**
     * Deletes a variable from the table.
     *
     * @param  string  $name  The name of the variable.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the variable is deleted successfully, false otherwise.
     */
    public function delete(string $name, string $user): bool
    {
        if (!$this->checkPermission($user, Permission::WRITE)) {
            return false;
        }

        $this->writeAheadLog("delete", $name, $user);

        unset($this->data[$name]);
        $this->save();
        return true;
    }

    /**
     * Gets a variable from the table.
     *
     * @param  string  $name  The name of the variable.
     * @param  string  $user  The user executing the action.
     * @return mixed The value of the variable if found, null otherwise.
     */
    public function get(string $name, string $user): mixed
    {
        if (!$this->checkPermission($user, Permission::READ)) {
            return null;
        }
        return $this->data[$name];
    }

    /**
     * Checks if a user has a specific permission on the table.
     *
     * @param  string  $user  The user to check.
     * @param  Permission  $permission  The permission to check.
     * @return bool Returns true if the user has the permission, false otherwise.
     */
    public function checkPermission(string $user, Permission $permission): bool
    {
        $role = $this->lonaDB->getUserManager()->getRole($user);
        if (
            $user == $this->owner ||
            $role->isIn([Role::ADMIN, Role::SUPERUSER])
        ) {
            return true;
        }

        return (bool) $this->permissions[$user][$permission->value];
    }

    /**
     * Checks if a user has a specific permission on the table.
     *
     * @param  string  $user  The user to check.
     * @param  Permission[]  $permissions  The permissions to check.
     * @return bool Returns true if the user has the permission, false otherwise.
     */
    public function hasAnyPermission(string $user, array $permissions): bool
    {
        $role = $this->lonaDB->getUserManager()->getRole($user);
        if (
            $user == $this->owner ||
            $role->isIn([Role::ADMIN, Role::SUPERUSER])
        ) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->permissions[$user][$permission->value]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if a variable exists in the table.
     *
     * @param  string  $name  The name of the variable.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the variable exists, false otherwise.
     */
    public function checkVariable(string $name, string $user): bool
    {
        return $this->checkPermission($user, Permission::READ) && $this->data[$name];
    }

    /**
     * Returns the table permissions.
     *
     * @return array The table permissions.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Adds a permission to a user for the table.
     *
     * @param  string  $name  The name of the user.
     * @param  string  $permission  The permission to add.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the permission is added successfully, false otherwise.
     */
    public function addPermission(string $name, Permission $permission, string $user): bool
    {
        $role = $this->lonaDB->getUserManager()->getRole($user);
        if ($user !== $this->owner &&
            $role->isNotIn([Role::ADMIN, Role::SUPERUSER])) {
            return false;
        }

        $this->writeAheadLog("addPermission", $name, $permission, $user);

        $this->permissions[$name][$permission->value] = true;
        $this->save();
        return true;
    }

    /**
     * Removes a permission from a user for the table.
     *
     * @param  string  $name  The name of the user.
     * @param  string  $permission  The permission to remove.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the permission is removed successfully, false otherwise.
     */
    public function removePermission(string $name, Permission $permission, string $user): bool
    {
        $role = $this->lonaDB->getUserManager()->getRole($user);
        if ($user !== $this->owner && $role->isNotIn([
                Role::ADMIN, Role::SUPERUSER
            ])) {
            return false;
        }

        $this->writeAheadLog("removePermission", $name, $permission, $user);

        unset($this->permissions[$name][$permission->value]);
        if ($this->permissions[$name] === array()) {
            unset($this->permissions[$name]);
        }
        $this->save();
        return true;
    }

    /**
     * Removes a user from the table entirely.
     *
     * @param  string  $name  The name of the user.
     */
    public function removeUserPermissions(string $name): void
    {
        $this->writeAheadLog("removeUserPermissions", $name);
        unset($this->permissions[$name]);
        $this->save();
    }

    /**
     * Reads the write-ahead log and applies the actions to the table.
     */
    public function checkWriteAheadLog(): void {
        try {
            $logFile = fopen("./data/wal/".$this->file.".lona", 'r');
            if (!$logFile) {
                throw new \Exception("Failed to open write-ahead log file for table '".$this->file."'.");
            }

            if (!flock($logFile, LOCK_EX)) {
                fclose($logFile);
                throw new \Exception("Failed to acquire lock on the write-ahead log file for table '".$this->file."'.");
            }

            $log = json_decode(LonaDB::decrypt(file_get_contents("./data/wal/".$this->file.".lona"), $this->lonaDB->config["encryptionKey"]), true);

            if($this->logLevel == 0 && $log == []) {
                flock($logFile, LOCK_UN);
                fclose($logFile);
                return;
            }
            
            $lastAction = 0;

            foreach ($log as $key => $value) {
                if ($key > $lastAction) {
                    $lastAction = $key;
                }
            }

            if ($lastAction > $this->logLevel) {
                $counter = 0;
                for ($i = $this->logLevel; $i <= $lastAction; $i++) {
                    foreach ($log[$i] as $action) {
                        switch ($action[1]) {
                            case "set":
                                $this->data[$action[2]] = $action[3];
                                break;
                            case "delete":
                                unset($this->data[$action[2]]);
                                break;
                            case "setOwner":
                                $this->owner = $action[2];
                                break;
                            case "addPermission":
                                $this->permissions[$action[2]][$action[3]] = true;
                                break;
                            case "removePermission":
                                unset($this->permissions[$action[2]][$action[3]]);
                                if ($this->permissions[$action[2]] === array()) {
                                    unset($this->permissions[$action[2]]);
                                }
                                break;
                            case "removeUserPermissions":
                                unset($this->permissions[$action[2]]);
                                break;
                        }
                        $counter++;
                    }
                }

                $this->lonaDB->getLogger()->table("Applied ".$counter." actions to table '".$this->file."' from write-ahead log.");

                $this->logLevel = $lastAction;
                $this->save();
            }

            flock($logFile, LOCK_UN);
            fclose($logFile);
        } catch (\Exception $exception) {
            $this->lonaDB->getLogger()->Error($exception->getMessage());
        }
    }

    /**
     * Adds a action to the write-ahead log.
     */
    private function writeAheadLog(string $action, string $name, mixed $value = "", string $user = ""): void {
        try {
            $this->logLevel++;

            $log = json_decode(LonaDB::decrypt(file_get_contents("./data/wal/".$this->file.".lona"), $this->lonaDB->config["encryptionKey"]), true);

            $log[$this->logLevel] = [time(), $action, $name, $value, $user];

            $logFile = fopen("./data/wal/".$this->file.".lona", 'w');
            
            if (!$logFile) {
                throw new \Exception("Failed to open write-ahead log file for table '".$this->file."'.");
            }

            if(!flock($logFile, LOCK_EX)) {
                fclose($logFile);
                throw new \Exception("Failed to acquire lock on the write-ahead log file for table '".$this->file."'.");
            }

            $encrypted = LonaDB::encrypt(json_encode($log), $this->lonaDB->config["encryptionKey"]);

            if (fwrite($logFile, $encrypted) === false) {
                flock($logFile, LOCK_UN);
                fclose($logFile);
                throw new \Exception("Failed to write to the write-ahead log file for table '".$this->file."'.");
            }

            flock($logFile, LOCK_UN);
            fclose($logFile);
        } catch (\Exception $exception) {
            $this->lonaDB->getLogger()->Error($exception->getMessage());
        }
    }

    /**
     * Saves the table data to a file.
     */
    private function save(): void
    {
        try {            
            $save = [
                "data" => $this->data,
                "permissions" => $this->permissions,
                "owner" => $this->owner,
                "logLevel" => $this->logLevel
            ];
            
            $encrypted =  LonaDB::encrypt(json_encode($save), $this->lonaDB->config["encryptionKey"]);
     
            $filePath = "./data/tables/".$this->file.".lona";
            
            $file = fopen($filePath, 'w');
            if ($file === false) {
                throw new \Exception("Failed to open temporary table file for writing.");
            }
            
            if (!flock($file, LOCK_EX)) {
                fclose($file);
                throw new \Exception("Failed to acquire lock on the temporary table file.");
            }
            
            if (fwrite($file, $encrypted) === false) {
                fclose($file);
                throw new \Exception("Failed to write to the temporary table file.");
            }
            
            flock($file, LOCK_UN);
            fclose($file);
        } catch (\Exception $exception) {
            // Log the error message using the logger
            $this->lonaDB->getLogger()->Error($exception->getMessage());
        }
    }
}
