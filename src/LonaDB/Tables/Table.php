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
            $parts = explode(':', file_get_contents("./data/tables/".$name));
            $temp = json_decode(openssl_decrypt($parts[0], AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0,
                base64_decode($parts[1])), true);
            $this->file = substr($name, 0, -5);
            $this->data = $temp["data"];
            $this->permissions = $temp["permissions"];
            $this->owner = $temp["owner"];
        } else {
            $this->lonaDB->getLogger()->table("Trying to generate table '".$name."'");
            $this->file = $name;
            $this->data = array();
            $this->permissions = array();
            $this->owner = $owner;
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
            $role->isIn([Role::ADMIN, Role::SUPERUSER]) ||
            $this->permissions[$user][Permission::ADMIN->value]
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
            $role->isIn([Role::ADMIN, Role::SUPERUSER]) ||
            $this->permissions[$user][Permission::ADMIN->value]
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
        if ($user !== $this->owner && !$this->checkPermission($user, Permission::ADMIN) &&
            $role->isNotIn([Role::ADMIN, Role::SUPERUSER])) {
            return false;
        }

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
        if ($user !== $this->owner && !$this->checkPermission($user, Permission::ADMIN) && $role->isNotIn([
                Role::ADMIN, Role::SUPERUSER
            ])) {
            return false;
        }

        unset($this->permissions[$name][$permission->value]);
        if ($this->permissions[$name] === array()) {
            unset($this->permissions[$name]);
        }
        $this->save();
        return true;
    }

    /**
     * Saves the table data to a file.
     */
    private function save(): void
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        $save = [
            "data" => $this->data,
            "permissions" => $this->permissions,
            "owner" => $this->owner
        ];

        $encrypted = openssl_encrypt(json_encode($save), AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0, $iv);
        file_put_contents("./data/tables/".$this->file.".lona", $encrypted.":".base64_encode($iv));
    }
}