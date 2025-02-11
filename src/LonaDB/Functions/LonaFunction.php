<?php

namespace LonaDB\Functions;

use LonaDB\LonaDB;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class LonaFunction extends ThreadSafe
{
    private string $file;
    public string $name;
    private ThreadSafeArray $functions;

    private LonaDB $lonaDB;

    /**
     * Constructor for the LonaFunction class.
     *
     * @param LonaDB $lonaDB The LonaDB instance.
     * @param string $name The name of the function.
     */
    public function __construct(LonaDB $lonaDB, string $name){
        $this->lonaDB = $lonaDB;
        $this->functions = new ThreadSafeArray();

        $this->lonaDB->getLogger()->load("Loading function '".$name."'");

        $temp = json_decode(LonaDB::decrypt(file_get_contents("./data/functions/".$name), $this->lonaDB->config["encryptionKey"]), true);

        $this->file = substr($name, 0, -5);
        $this->name = $this->file;

        // Create an eval script to add the function to the function array
        //
        // We are using an array.
        // Overwriting/defining a function inside eval didn't work for us
        // Our workaround is creating a class instance with a run function which is our Lona function
        $function = "\$this->functions['" . $this->name . "'] = new class {\n";
        $function .= "public function run(\$LonaDB, \$data) {\n";
        $function .= $temp;
        $function .= "\n} \n};";

        // Add the function to the array
        eval($function);

        $this->lonaDB->getLogger()->load("Function '" . $this->name . "' has been loaded");
    }

    /**
     * Executes the function with the provided data.
     *
     * @param LonaDB $lonaDB The LonaDB instance.
     * @param array $data The data to be passed to the function.
     * @param mixed $client The client to send the response to.
     * @return mixed The result of the function execution.
     */
    public function execute(LonaDB $lonaDB, array $data, mixed $client) {
        return $this->functions[$this->name]->run($lonaDB, $data);
    }
}
