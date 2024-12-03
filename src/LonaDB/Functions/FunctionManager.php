<?php

namespace LonaDB\Functions;

//Encryption/decryption
define('AES_256_CBC', 'aes-256-cbc');

require 'vendor/autoload.php';

use DirectoryIterator;
use LonaDB\LonaDB;

class FunctionManager
{
    private LonaDB $lonaDB;
    private array $functions;

    /**
     * Constructor for the FunctionManager class.
     *
     * @param  LonaDB  $lonaDB  The LonaDB instance.
     */
    public function __construct(LonaDB $lonaDB)
    {
        $this->lonaDB = $lonaDB;
        $this->functions = array();

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
        //Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        //Encrypt a function script
        $encrypted = openssl_encrypt(json_encode($content), AES_256_CBC, $this->lonaDB->config["encryptionKey"], 0,
            $iv);
        //Save
        file_put_contents("./data/functions/".$name.".lona", $encrypted.":".base64_encode($iv));
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