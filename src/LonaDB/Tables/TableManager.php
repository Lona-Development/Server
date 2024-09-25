<?php

namespace LonaDB\Tables;

//Load autoload from composer
require 'vendor/autoload.php';

//Load Main file
use LonaDB\LonaDB;

class TableManager{
    //Create all variables
    private LonaDB $LonaDB;
    private array $Tables;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
        //Initialize tables array
        $this->Tables = array();

        //Check if directory "data/tables/" exists, create if it doesn't
        if(!is_dir("data/")) mkdir("data/");
        if(!is_dir("data/tables/")) mkdir("data/tables/");

        //Counter variable for counting tables and creating the Default table if none exist
        $counter = 0;

        //For all files and folders in "data/tables/"
        foreach (new \DirectoryIterator('data/tables') as $fileInfo) {
            //Check if file extension is ".lona"
            if(str_ends_with($fileInfo->getFilename(), ".lona")){
                //Initialize table instance
                $this->Tables[substr($fileInfo->getFilename(), 0, -5)] = new Table($this->LonaDB, false, $fileInfo->getFilename());
                //Count up
                $counter = $counter + 1;
            }
        }

        //No table files exist
        if($counter === 0){
            //Create default table
            $this->CreateTable("Default", "root");
        }
    }

    public function GetTable(string $name) : mixed {
        //Check if table exists
        if(!$this->Tables[$name]) return false;
        //Return table instance
        return $this->Tables[$name];
    }

    public function ListTables(string $user = "") : array {
        //Temporary array of tables to return
        $tables = array();

        //Check if there is a certain user we want the tables for
        if($user !== ""){
            //Loop through all tables
            foreach($this->Tables as $table){
                //Check if the user has read or write permissions on the table => Push the table to the array
                if($table->CheckPermission($user, "write")) array_push($tables, $table->Name);
                else if($table->CheckPermission($user, "read")) array_push($tables, $table->Name);
            }
        }
        else{
            //Loop through all tables
            foreach($this->Tables as $table){
                //Add the table to the array
                array_push($tables, $table->Name);
            }
        }

        //Return temporary tables array
        return $tables;
    }

    public function CreateTable(string $name, string $owner) : bool {
        //Check if there already is a table with the exact same name
        if($this->GetTable($name)) return false;

        //Create table instance
        $this->Tables[$name] = new Table($this->LonaDB, true, $name, $owner);
        return true;
    }

    public function DeleteTable(string $name, string $user) : bool {
        //Check if the table exists
        if(!$this->GetTable($name)) return false;

        //Check if deleting user is the table owner, a global administrator or superuser
        if($user !== $this->Tables[$name]->GetOwner() && $this->LonaDB->UserManager->GetRole($user) !== "Administrator" && $this->LonaDB->UserManager->GetRole($user) !== "Superuser") return false;

        //Delete table file and instance from the tables array
        unlink("data/tables/".$name.".lona");
        unset($this->Tables[$name]);
        return true;
    }
}