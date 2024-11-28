<?php

namespace LonaDB\Tables;

require 'vendor/autoload.php';

use DirectoryIterator;
use LonaDB\LonaDB;

class TableManager
{

    private LonaDB $lonaDB;
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

        //Check if the directory "data/tables/" exists, create if it doesn't
        if (!is_dir("data/")) {
            mkdir("data/");
        }
        if (!is_dir("data/tables/")) {
            mkdir("data/tables/");
        }

        //Counter variable for counting tables and creating the Default table if none exist
        $counter = 0;

        //For all files and folders in "data/tables/"
        foreach (new DirectoryIterator('data/tables') as $fileInfo) {
            //Check if the file extension is ".lona"
            if (str_ends_with($fileInfo->getFilename(), ".lona")) {
                //Initialize table instance
                $this->tables[substr($fileInfo->getFilename(), 0, -5)] = new Table($this->lonaDB, false,
                    $fileInfo->getFilename());
                $counter = $counter + 1;
            }
        }

        if ($counter === 0) {
            $this->createTable("Default", "root");
        }
    }

    /**
     * Retrieves a table by name.
     *
     * @param  string  $name  The name of the table.
     * @return Table|false The table instance if found, false otherwise.
     */
    public function getTable(string $name): false|Table
    {
        if (!$this->tables[$name]) {
            return false;
        }
        return $this->tables[$name];
    }

    /**
     * Lists all tables, optionally filtered by user permissions.
     *
     * @param  string  $user  The user to filter tables by (optional).
     * @return array The list of table names.
     */
    public function listTables(string $user = ""): array
    {
        $tables = array();

        //Check if there is a certain user we want the tables for
        if ($user !== "") {
            foreach ($this->tables as $table) {
                //Check if the user has read or write permissions on the table => Push the table to the array
                if ($table->checkPermission($user, "write")) {
                    $tables[] = $table->name;
                } else {
                    if ($table->checkPermission($user, "read")) {
                        $tables[] = $table->name;
                    }
                }
            }
        } else {
            foreach ($this->tables as $table) {
                $tables[] = $table->name;
            }
        }
        return $tables;
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
        //Check if there already is a table with the exact same name
        if ($this->getTable($name)) {
            return false;
        }

        $this->tables[$name] = new Table($this->lonaDB, true, $name, $owner);
        return true;
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
        if (!$this->getTable($name)) {
            return false;
        }

        //Check if the deleting user is the table owner, a global administrator or superuser
        if ($user !== $this->tables[$name]->getOwner() && $this->lonaDB->userManager->getRole($user) !== "Administrator" && $this->lonaDB->userManager->getRole($user) !== "Superuser") {
            return false;
        }

        //Delete table file and instance from the table array
        unlink("data/tables/".$name.".lona");
        unset($this->tables[$name]);
        return true;
    }
}