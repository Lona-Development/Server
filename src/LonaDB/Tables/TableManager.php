<?php

namespace LonaDB\Tables;

require 'vendor/autoload.php';
use LonaDB\LonaDB;

class TableManager{
    private LonaDB $LonaDB;
    private array $Tables;

    public function __construct(LonaDB $lonaDB){
        $this->LonaDB = $lonaDB;
        $this->Tables = array();

        if(!is_dir("data/")) mkdir("data/");
        if(!is_dir("data/tables/")) mkdir("data/tables/");

        $counter = 0;

        foreach (new \DirectoryIterator('data/tables') as $fileInfo) {
            if(str_ends_with($fileInfo->getFilename(), ".lona")){
                $this->Tables[substr($fileInfo->getFilename(), 0, -5)] = new Table($this->LonaDB, false, $fileInfo->getFilename());
                $counter = $counter + 1;
            }
        }

        if($counter === 0){
            $this->CreateTable("Default", "root");
        }
    }

    public function GetTable(string $name) : mixed {
        if(!$this->Tables[$name]) return false;
        return $this->Tables[$name];
    }

    public function ListTables(string $user = "") : array {
        $tables = array();

        if($user !== ""){
            foreach($this->Tables as $table){
                if($table->CheckPermission($user, "write")) array_push($tables, $table->Name);
                else if($table->CheckPermission($user, "read")) array_push($tables, $table->Name);
            }
        }
        else{
            foreach($this->Tables as $table){
                array_push($tables, $table->Name);
            }
        }

        return $tables;
    }

    public function CreateTable(string $name, string $owner) : bool {
        $this->LonaDB->Logger->Table("Trying to create table '" . $name . "', owned by user '" . $owner . "'");
        if($this->Tables[$name]) {
            $this->LonaDB->Logger->Error("Table '" . $name . "' already exists");
            return false;
        }
        $this->Tables[$name] = new Table($this->LonaDB, true, $name, $owner);
        $this->LonaDB->Logger->Table("Table '" . $name . "' has been created");
        return true;
    }

    public function DeleteTable(string $name, string $user) : bool {
        $this->LonaDB->Logger->Table("Trying to delete table '" . $name . "', requested by user '" . $user . "'");
        if(!$this->Tables[$name]) {
            $this->LonaDB->Logger->Error("Table '" . $name . "' doesn't exist");
            return false;
        }

        if($user !== $this->Tables[$name]->GetOwner()) {
            $this->LonaDB->Logger->Table("Not the owner! Trying to delete table '" . $name . "', requested by user '" . $user . "'");
            return false;
        }

        unlink("data/tables/".$name.".lona");
        unset($this->Tables[$name]);
        $this->LonaDB->Logger->Table("Deleted table '" . $name . "', requested by user '" . $user . "'");
        return true;
    }
}