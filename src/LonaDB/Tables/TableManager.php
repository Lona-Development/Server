<?php

namespace LonaDB\Tables;

require '../../vendor/autoload.php';

use DirectoryIterator;
use LonaDB\Enums\Permission;
use LonaDB\Enums\Role;
use LonaDB\LonaDB;

class TableManager
{

    private LonaDB $lonaDB;
    /* @var Table[] $tables */
    private array $tables;

    /**
     * Constructor for the TableManager class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        $this->tables = array();

        $path = "data/tables/";
        $temp = "";
        foreach(split("/", $path) as $dir) {
            $temp = $temp . $dir . "/";
            if (!is_dir($dir)) {
                mkdir($dir);
            }
        }

        $counter = 0;
        foreach (new DirectoryIterator('data/tables') as $fileInfo) {
            if (str_ends_with($fileInfo->getFilename(), ".lona")) {
                $this->tables[substr($fileInfo->getFilename(), 0, -5)] = new Table($this->lonaDB, false,
                    $fileInfo->getFilename());
                $this->lonaDB->getLogger()->load("Table loaded: " . substr($fileInfo->getFilename(), 0, -5));
                $counter = $counter + 1;
            }
        }

        //No table files exist
        if ($counter == 0) {
            //Create default table
            $this->createTable("Default", "root");
        }
    }

    /**
     * Retrieves a table by name.
     *
     * @param  string  $name  The name of the table.
     * @return ?Table The table instance if found, false otherwise.
     */
    public function getTable(string $name): ?Table
    {
        return $this->tables[$name] ?? null;
    }

    /**
     * Lists all tables, optionally filtered by user permissions.
     *
     * @param  string  $user  The user to filter tables by (optional).
     * @return array The list of table names.
     */
    public function listTables(string $user = ""): array
    {
        $count = 0;
        /* @var Table $table */
        foreach ($this->tables as $table) {
            if ($user == "" || $table->hasAnyPermission($user, [Permission::WRITE, Permission::READ])) {
                $tables[$count] = $table->name;
                $count++;
            }
        }
        return $tables ?? [];
    }

    /**
     * Creates a new table.
     *
     * @param  string  $name  The name of the table.
     * @param  string  $owner  The owner of the table.
     * @return bool Returns true if the table is created successfully, false otherwise.
     */
    public function createTable(string $name, string $owner): bool
    {
        if (!$this->getTable($name)) {
            $this->tables[$name] = new Table($this->lonaDB, true, $name, $owner);
            return true;
        }
        return false;
    }

    /**
     * Deletes a table.
     *
     * @param  string  $name  The name of the table.
     * @param  string  $user  The user executing the action.
     * @return bool Returns true if the table is deleted successfully, false otherwise.
     */
    public function deleteTable(string $name, string $user): bool
    {
        if ($this->getTable($name)) {
            $role = $this->lonaDB->getUserManager()->getRole($user);
            if ($user === $this->tables[$name]->getOwner() && $role->isIn([Role::ADMIN, Role::SUPERUSER])) {
                unlink("data/tables/".$name.".lona");
                unset($this->tables[$name]);
                return true;
            }
        }
        return false;
    }
}
