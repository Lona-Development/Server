<?php

namespace LonaDB\Functions;

use DirectoryIterator;
use LonaDB\LonaDB;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class FunctionManager extends ThreadSafe
{
    private LonaDB $lonaDB;
    private ThreadSafeArray $functions;

    /**
     * Constructor for the FunctionManager class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        $this->functions = new ThreadSafeArray();

        //Check if the directory "data/functions/" exists, create if it doesn't
        if (!is_dir("data/")) {
            mkdir("data/");
        }
        if (!is_dir("data/functions/")) {
            mkdir("data/functions/");
        }

        //Loop through all files and folders in "data/functions/"
        foreach (new DirectoryIterator('data/functions') as $fileInfo) {
            //Check if the file extension is ".lona"
            if (str_ends_with($fileInfo->getFilename(), ".lona")) {
                //Initialize function instance
                $this->functions[substr($fileInfo->getFilename(), 0, -5)] = new LonaFunction($this->lonaDB,
                    $fileInfo->getFilename());
            }
        }
    }

    /**
     * Retrieves a function by name.
     *
     * @param  string  $name  The name of the function.
     * @return mixed The function instance if found, false otherwise.
     */
    public function getFunction(string $name): mixed
    {
        return $this->functions[$name] ?? false;
    }

    /**
     * Creates a new function.
     *
     * @param  string  $name  The name of the function.
     * @param  string  $content  The content of the function.
     * @return bool Returns true if the function is created successfully, false otherwise.
     */
    public function create(string $name, string $content): bool
    {
        //Check if a function with that name exists
        if ($this->functions[$name]) {
            return false;
        }
        //Save
        $encrypted = LonaDB::encrypt($content, $this->lonaDB->getEncryptionKey());
        file_put_contents("./data/functions/".$name.".lona", $encrypted);
        //Initialize function instance
        $this->functions[$name] = new LonaFunction($this->lonaDB, $name.".lona");
        return true;
    }

    /**
     * Deletes a function by name.
     *
     * @param  string  $name  The name of the function.
     * @return bool Returns true if the function is deleted successfully, false otherwise.
     */
    public function delete(string $name): bool
    {
        //Check if function exists
        if (!$this->functions[$name]) {
            return false;
        }
        //Delete function file and instance
        unset($this->functions[$name]);
        unlink("./data/functions/".$name.".lona");
        return true;
    }
}
