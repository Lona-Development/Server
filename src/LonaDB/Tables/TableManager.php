<?php

namespace LonaDB\Tables;

require 'vendor/autoload.php';

use DirectoryIterator;
use LonaDB\LonaDB;

class TableManager
{
    //Create all variables
    private LonaDB $lonaDB;
    private array $tables;

    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        $this->tables = array();

        //Check if the directory "data /tables/" exists, create if it doesn't
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
                //Count up
                $counter = $counter + 1;
            }
        }

        //No table files exist
        if ($counter === 0) {
            //Create default table
            $this->createTable("Default", "root");
        }
    }

    public function getTable(string $name)
    {
        //Check if table exists
        if (!$this->tables[$name]) {
            return false;
        }
        //Return table instance
        return $this->tables[$name];
    }

    public function listTables(string $user = ""): array
    {
        //Temporary array of tables to return
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

        //Return temporary tables array
        return $tables;
    }

    public function createTable(string $name, string $owner): bool
    {
        //Check if there already is a table with the exact same name
        if ($this->getTable($name)) return false;

        //Create a table instance
        $this->tables[$name] = new Table($this->lonaDB, true, $name, $owner);
        return true;
    }

    public function deleteTable(string $name, string $user): bool
    {
        //Check if the table exists
        if (!$this->getTable($name)) {
            return false;
        }

        //Check if the deleting user is the table owner, a global administrator or superuser
        if ($user !== $this->tables[$name]->getOwner() && $this->lonaDB->userManager->getRole($user) !== "Administrator" && $this->lonaDB->userManager->getRole($user) !== "Superuser")
            return false;

        //Delete table file and instance from the table array
        unlink("data/tables/".$name.".lona");
        unset($this->tables[$name]);
        return true;
    }
}