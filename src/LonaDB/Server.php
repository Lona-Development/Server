<?php

namespace LonaDB;

require 'vendor/autoload.php';
use LonaDB\LonaDB;

class Server {
    private array $config;
    private LonaDB $LonaDB;

    public function __construct(LonaDB $lonaDB) {
        $this->LonaDB = $lonaDB;
        $this->config = $lonaDB->config;
    }
}

?>
